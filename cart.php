<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Обработка действий с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        // Обновление количества товара
        $cart_id = $_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];
        
        // Получаем информацию о товаре
        $stmt = $pdo->prepare("SELECT p.quantity as stock_quantity, c.quantity as cart_quantity 
                             FROM cart c JOIN products p ON c.product_id = p.id 
                             WHERE c.id = ? AND c.user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        $product = $stmt->fetch();
        
        if ($quantity > $product['stock_quantity']) {
            $_SESSION['error'] = "Недостаточно товара на складе. Максимальное количество: " . $product['stock_quantity'];
            //header("Location: cart.php");
            echo '<script>window.location.href = "cart.php";</script>';
            exit;
        }
        
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
        } else {
            // Если количество 0, удаляем товар
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
        }
    } 
    elseif (isset($_POST['remove_item'])) {
        // Удаление товара из корзины
        $cart_id = $_POST['cart_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
    } 
    elseif (isset($_POST['checkout'])) {
        // Оформление заказа
        $total_price = 0;
        $cart_items = getCartItems($pdo, $user_id);
        
        if (empty($cart_items)) {
            $_SESSION['error'] = "Ваша корзина пуста!";
            //header("Location: cart.php");
            echo '<script>window.location.href = "cart.php";</script>';
            exit;
        }

        // Проверяем наличие всех товаров перед оформлением
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $_SESSION['error'] = "Товар '{$item['name']}' недоступен в количестве {$item['quantity']} шт. (в наличии: {$item['stock_quantity']} шт.)";
                //header("Location: cart.php");
                echo '<script>window.location.href = "cart.php";</script>';
                exit;
            }
        }

        // Создаем заказ
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
        $stmt->execute([$user_id, $total_price]);
        $order_id = $pdo->lastInsertId();

        // Добавляем товары в таблицу order_items
        foreach ($cart_items as $item) {
            $total_price += $item['price'] * $item['quantity'];
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
            
            // Обновляем количество товара на складе
            $new_quantity = $item['stock_quantity'] - $item['quantity'];
            $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $item['id']]);
        }

        // Обновляем общую стоимость заказа
        $stmt = $pdo->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
        $stmt->execute([$total_price, $order_id]);

        // Очищаем корзину
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        //header("Location: order_success.php?order_id=" . $order_id);
        //echo '<script>window.location.href = "order_success.php?order_id=";</script>'. $order_id;
        echo '<script>window.location.href = "order_success.php?order_id=' . htmlspecialchars($order_id, ENT_QUOTES) . '";</script>';
        exit;
    }
    elseif (isset($_POST['checkout_single'])) {
        // Оформление одного товара
        $cart_id = $_POST['cart_id'];
        
        // Получаем информацию о товаре
        $stmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.price, p.quantity as stock_quantity, p.name 
                              FROM cart c JOIN products p ON c.product_id = p.id 
                              WHERE c.id = ? AND c.user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        $item = $stmt->fetch();
        
        if ($item) {
            // Проверяем наличие товара
            if ($item['quantity'] > $item['stock_quantity']) {
                $_SESSION['error'] = "Товар '{$item['name']}' недоступен в количестве {$item['quantity']} шт. (в наличии: {$item['stock_quantity']} шт.)";
                //header("Location: cart.php");
                echo '<script>window.location.href = "cart.php";</script>';
                exit;
            }
            
            $total_price = $item['price'] * $item['quantity'];
            
            // Создаем заказ
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
            $stmt->execute([$user_id, $total_price]);
            $order_id = $pdo->lastInsertId();
            
            // Добавляем товар в order_items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"); 
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            
            // Обновляем количество товара на складе
            $new_quantity = $item['stock_quantity'] - $item['quantity'];
            $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $item['product_id']]);
            
            // Удаляем товар из корзины
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            
            //header("Location: order_success.php?order_id=" . $order_id);
            //echo '<script>window.location.href = "order_success.php?order_id=";</script>'. $order_id;
            echo '<script>window.location.href = "order_success.php?order_id=' . htmlspecialchars($order_id, ENT_QUOTES) . '";</script>';
            exit;
        }
    }
}

// Функция для получения товаров в корзине
function getCartItems($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT c.id as cart_id, p.id, p.name, p.price, p.image, p.quantity as stock_quantity, c.quantity 
                          FROM cart c JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

$cart_items = getCartItems($pdo, $user_id);
$total_cart_price = 0;
foreach ($cart_items as $item) {
    $total_cart_price += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .cart-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .product-image {
            max-width: 100px;
            height: auto;
            border-radius: 5px;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
        }
        .summary-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .stock-info {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .out-of-stock {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-6">Ваша корзина</h1>
                <a href="index.php" class="btn btn-outline-primary">← Вернуться к покупкам</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="cart-container">
                    <?php if (empty($cart_items)): ?>
                        <div class="alert alert-info">Ваша корзина пуста</div>
                    <?php else: ?>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="row cart-item align-items-center">
                                <div class="col-md-2">
                                    <img src="<?= htmlspecialchars($item['image'] ?: 'images/no-image.jpg') ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                         class="product-image img-fluid">
                                </div>
                                <div class="col-md-4">
                                    <h5><?= htmlspecialchars($item['name']) ?></h5>
                                    <div class="text-muted"><?= number_format($item['price'], 2, '.', ' ') ?> ₽ за шт.</div>
                                    <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                        <div class="out-of-stock">Недостаточно на складе (в наличии: <?= $item['stock_quantity'] ?> шт.)</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                               min="1" max="<?= $item['stock_quantity'] ?>"
                                               class="form-control quantity-input me-2">
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-secondary">Обновить</button>
                                    </form>
                                    <div class="stock-info">Доступно: <?= $item['stock_quantity'] ?> шт.</div>
                                </div>
                                <div class="col-md-2">
                                    <strong><?= number_format($item['price'] * $item['quantity'], 2, '.', ' ') ?> ₽</strong>
                                </div>
                                <div class="col-md-1">
                                    <form method="POST">
                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                        <button type="submit" name="remove_item" class="btn btn-sm btn-danger">×</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="cart-container summary-card">
                    <h5>Итого</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Товары (<?= count($cart_items) ?>):</span>
                        <span><?= number_format($total_cart_price, 2, '.', ' ') ?> ₽</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Общая сумма:</strong>
                        <strong><?= number_format($total_cart_price, 2, '.', ' ') ?> ₽</strong>
                    </div>
                    
                    <?php if (!empty($cart_items)): ?>
                        <form method="POST">
                            <button type="submit" name="checkout" class="btn btn-primary w-100 mb-2"
                                <?= array_reduce($cart_items, function($carry, $item) {
                                    return $carry || ($item['quantity'] > $item['stock_quantity']);
                                }, false) ? 'disabled title="Некоторые товары недоступны в нужном количестве"' : '' ?>>
                                Оформить весь заказ
                            </button>
                        </form>
                        
                        <p class="text-center text-muted my-3">или</p>
                        
                        <p>Оформить отдельно:</p>
                        <?php foreach ($cart_items as $item): ?>
                            <form method="POST" class="mb-2">
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                <button type="submit" name="checkout_single" class="btn btn-outline-primary w-100"
                                    <?= $item['quantity'] > $item['stock_quantity'] ? 'disabled title="Недостаточно товара на складе"' : '' ?>>
                                    <?= htmlspecialchars($item['name']) ?> - <?= $item['quantity'] ?> шт.
                                </button>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Проверка количества перед отправкой формы
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const quantityInput = this.querySelector('input[name="quantity"]');
                if (quantityInput) {
                    const max = parseInt(quantityInput.max);
                    const value = parseInt(quantityInput.value);
                    
                    if (value > max) {
                        e.preventDefault();
                        alert(`Недостаточно товара на складе. Максимальное количество: ${max}`);
                    }
                }
            });
        });
    </script>
</body>
</html>