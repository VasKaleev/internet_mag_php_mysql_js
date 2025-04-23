<?php
session_start();
require 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_id'])) {
    //header("Location: login.php");
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}

// Обработка добавления нового товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $category = trim($_POST['category']);
    $subcategory = trim($_POST['subcategory']);
    
    // Обработка загрузки изображения
    $image = 'no-image.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/';
        $uploadFile = $uploadDir . basename($_FILES['image']['name']);
        
        // Проверяем тип файла
        $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $image = basename($_FILES['image']['name']);
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, quantity, category, subcategory, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $quantity, $category, $subcategory, $image]);
    
    $_SESSION['admin_message'] = "Товар успешно добавлен";
    //header("Location: admin_products.php");
    echo '<script>window.location.href = "admin_products.php";</script>';
    exit();
}

// Обработка удаления товара
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['admin_message'] = "Товар успешно удален";
    //header("Location: admin_products.php");
    echo '<script>window.location.href = "admin_products.php";</script>';
    exit();
}

// Получаем список всех товаров
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll();

// Получаем список категорий
$categories = $pdo->query("SELECT DISTINCT category FROM products")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами - МаркетПлюс</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/styles.css?v=1">
    <style>
        /* Стили из admin_panel.php */
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Боковое меню -->
        <div class="admin-sidebar">
            <h2>Панель администратора</h2>
            <p>Вы вошли как: <?= htmlspecialchars($_SESSION['admin_username']) ?></p>
            
            <nav class="admin-nav">
                <a href="admin_panel.php"><i class="fas fa-tachometer-alt"></i> Обзор</a>
                <a href="admin_products.php" class="active"><i class="fas fa-box"></i> Товары</a>
                <a href="admin_users.php"><i class="fas fa-users"></i> Пользователи</a>
                <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a>
                <a href="admin_reviews.php"><i class="fas fa-comments"></i> Отзывы</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </nav>
        </div>

        <!-- Основное содержимое -->
        <div class="admin-content">
            <div class="admin-section">
                <h2><i class="fas fa-box"></i> Управление товарами</h2>
                
                <?php if (isset($_SESSION['admin_message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['admin_message'] ?></div>
                    <?php unset($_SESSION['admin_message']); ?>
                <?php endif; ?>
                
                <h3>Добавить новый товар</h3>
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label>Название:</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Цена:</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Количество:</label>
                        <input type="number" name="quantity" required>
                    </div>
                    <div class="form-group">
                        <label>Категория:</label>
                        <input type="text" name="category" required>
                    </div>
                    <div class="form-group">
                        <label>Подкатегория:</label>
                        <input type="text" name="subcategory">
                    </div>
                    <div class="form-group">
                        <label>Изображение:</label>
                        <input type="file" name="image">
                    </div>
                    <button type="submit" name="add_product" class="btn">Добавить товар</button>
                </form>
            </div>

            <div class="admin-section">
                <h3>Список товаров</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Цена</th>
                            <th>Количество</th>
                            <th>Категория</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= number_format($product['price'], 2, '.', ' ') ?> ₽</td>
                                <td><?= $product['quantity'] ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm">Редактировать</a>
                                    <a href="admin_products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить этот товар?')">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>