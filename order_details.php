<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT username, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Получаем информацию о заказе
$stmt = $pdo->prepare("SELECT o.id, o.total_price, o.order_date, o.status, 
                      o.comment, o.delivery_address 
                      FROM orders o 
                      WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Заказ не найден или у вас нет к нему доступа");
}

// Получаем товары в заказе
$stmt = $pdo->prepare("SELECT oi.quantity, oi.price, p.name, p.image 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали заказа #<?= $order['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .order-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .product-image {
            max-width: 80px;
            height: auto;
            border-radius: 5px;
        }
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .status-new {
            color: #0d6efd;
        }
        .status-processing {
            color: #fd7e14;
        }
        .status-completed {
            color: #198754;
        }
        .status-cancelled {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="order-card">
                    <h4 class="mb-4">Мой профиль</h4>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Мои заказы</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile_edit.php">Личные данные</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Выход</a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="order-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Заказ #<?= $order['id'] ?></h4>
                        <a href="profile.php" class="btn btn-outline-secondary">← Назад к заказам</a>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-2"><strong>Дата заказа:</strong> <?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></div>
                            <div class="mb-2"><strong>Статус:</strong> 
                                <?php
                                $status_class = '';
                                switch ($order['status']) {
                                    case 'new':
                                        $status_class = 'status-new';
                                        $status_text = 'Новый';
                                        break;
                                    case 'processing':
                                        $status_class = 'status-processing';
                                        $status_text = 'В обработке';
                                        break;
                                    case 'completed':
                                        $status_class = 'status-completed';
                                        $status_text = 'Завершен';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'status-cancelled';
                                        $status_text = 'Отменен';
                                        break;
                                    default:
                                        $status_class = 'text-muted';
                                        $status_text = $order['status'];
                                }
                                ?>
                                <span class="<?= $status_class ?>"><?= $status_text ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- <div class="mb-2"><strong>Адрес доставки:</strong> <?= htmlspecialchars($order['delivery_address']) ?></div> -->
                            <div class="mb-2"><strong>Адрес доставки:</strong> <?= htmlspecialchars($user['address']) ?></div>
                            <div class="mb-2"><strong>Номер телефона:</strong> <?= htmlspecialchars($user['phone']) ?></div>
                            <!-- <?php if (!empty($order['comment'])): ?>
                                <div class="mb-2"><strong>Комментарий:</strong> <?= htmlspecialchars($order['comment']) ?></div>
                            <?php endif; ?> -->
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Состав заказа:</h5>
                    <?php foreach ($order_items as $item): ?>
                        <div class="row order-item align-items-center">
                            <div class="col-md-2">
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="product-image img-fluid">
                            </div>
                            <div class="col-md-5">
                                <div><?= htmlspecialchars($item['name']) ?></div>
                            </div>
                            <div class="col-md-2">
                                <div><?= $item['quantity'] ?> шт.</div>
                            </div>
                            <div class="col-md-3 text-end">
                                <div><?= $item['price'] * $item['quantity'] ?> ₽</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <div class="bg-light p-3 rounded">
                            <h5>Итого: <?= $order['total_price'] ?> ₽</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>