<?php
session_start();
require 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Добавление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'] ?? null;
    
    // Обработка загрузки изображения
    $imagePath = 'images/no-image.jpg'; // значение по умолчанию
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/';
        $uploadFile = $uploadDir . basename($_FILES['image']['name']);
        
        // Проверяем тип файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            // Генерируем уникальное имя файла
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $uploadFile = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $imagePath = $uploadFile;
            } else {
                $_SESSION['message'] = "Ошибка при загрузке изображения.";
                header("Location: admin_panel.php");
                exit;
            }
        } else {
            $_SESSION['message'] = "Недопустимый тип файла. Разрешены только JPEG, PNG и GIF.";
            header("Location: admin_panel.php");
            exit;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, quantity, category, subcategory, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $quantity, $category, $subcategory, $imagePath]);
    $_SESSION['message'] = "Товар успешно добавлен!";
    header("Location: admin_panel.php");
    exit;
}

// Удаление товара
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $_SESSION['message'] = "Товар удален!";
    //header("Location: admin_panel.php");
    echo '<script>window.location.href = "admin_panel.php";</script>';
    exit;
}

// Получение списка товаров
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll();

// Получение списка категорий
$categories = $pdo->query("SELECT DISTINCT category FROM products")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/stylesap.css?v=1">
    
</head>
<body>
    <a href="index.php" class="home-link">
        <i class="fas fa-long-arrow-alt-left"></i> На главную
    </a>
    <div class="container">
        <div class="admin-header">
            <h1>Админ-панель</h1>
            <!-- <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Выйти</a> -->
            <a href="admin_register.php"><i class="fas fa-user"></i>Зарегистрировать администратора</a>
            <a href="view_order.php"><i class="fas fa-user"></i>Просмотр заказанных товаров</a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message">
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <h2>Добавить товар</h2>
        <!-- <form method="POST" action=""> -->
        <form method="POST" action="" enctype="multipart/form-data">    
            <label>Название:</label>
            <input type="text" name="name" required>
            
            <label>Описание:</label>
            <textarea name="description" rows="4"></textarea>
            
            <label>Цена:</label>
            <input type="number" step="0.01" name="price" required>
            
            <label>Количество:</label>
            <input type="number" name="quantity" required>
            
            <label>Категория:</label>
            <input type="text" name="category" required list="categories">
            <datalist id="categories">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
            </datalist>
            
            <label>Подкатегория (необязательно):</label>
            <input type="text" name="subcategory">
            
            <!-- <label>Изображение (URL):</label>
            <input type="text" name="image" placeholder="images/no-image.jpg"> -->
            <label>Изображение:</label>
            <input type="file" name="image" accept="image/*">
            
            <button type="submit" name="add_product"><i class="fas fa-plus"></i> Добавить товар</button>
        </form>

        <h2>Список товаров</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Категория</th>
                    <th>Подкатегория</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['id']) ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['description']) ?></td>
                        <td><?= htmlspecialchars($product['price']) ?> ₽</td>
                        <td><?= htmlspecialchars($product['quantity']) ?></td>
                        <td><?= htmlspecialchars($product['category']) ?></td>
                        <td><?= htmlspecialchars($product['subcategory'] ?? '-') ?></td>
                        <td>
                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="action-link"><i class="fas fa-edit"></i> Изменить</a>
                            <a href="?delete=<?= $product['id'] ?>" class="action-link" onclick="return confirm('Вы уверены, что хотите удалить этот товар c id=<?= $product['id'];  ?>; Название товара: <?= $product['name'];  ?>; Цена: <?= $product['price'];  ?> ₽; Количество: <?= $product['quantity'];  ?>; Категория: <?= $product['category'];  ?>; Подкатегория: <?= $product['subcategory'];  ?> ?')"><i class="fas fa-trash"></i> Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>