<?php
session_start();
require 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_id'])) {
    // header("Location: admin_login.php");
    echo '<script>window.location.href = "admin_login.php";</script>';
    exit;
}

// Получение ID товара для редактирования
if (!isset($_GET['id'])) {
    //header("Location: admin_panel.php");
    echo '<script>window.location.href = "admin_panel.php";</script>';
    exit;
}

$product_id = $_GET['id'];

// Получение информации о товаре
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['message'] = "Товар не найден!";
    header("Location: admin_panel.php");
    exit;
}

// Получение списка категорий
$categories = $pdo->query("SELECT DISTINCT category FROM products")->fetchAll(PDO::FETCH_COLUMN);

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'] ?? null;
    
    // Обработка загрузки изображения
    $imagePath = $product['image']; // сохраняем текущее изображение по умолчанию
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/';
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        
        // Проверяем размер файла
        if ($_FILES['image']['size'] > $maxFileSize) {
            $_SESSION['message'] = "Файл слишком большой. Максимальный размер: 2MB.";
            // header("Location: edit_product.php?id=" . $product_id);
            echo '<script>window.location.href = "edit_product.php?product_id=' . htmlspecialchars($product_id, ENT_QUOTES) . '";</script>';
            exit;
        }
        
        // Проверяем тип файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            // Генерируем уникальное имя файла
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $uploadFile = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                // Удаляем старое изображение, если это не изображение по умолчанию
                if ($product['image'] && $product['image'] !== 'images/no-image.jpg' && file_exists($product['image'])) {
                    unlink($product['image']);
                }
                $imagePath = $uploadFile;
            } else {
                $_SESSION['message'] = "Ошибка при загрузке изображения.";
                // header("Location: edit_product.php?id=" . $product_id);
                echo '<script>window.location.href = "edit_product.php?product_id=' . htmlspecialchars($product_id, ENT_QUOTES) . '";</script>';
                exit;
            }
        } else {
            $_SESSION['message'] = "Недопустимый тип файла. Разрешены только JPEG, PNG и GIF.";
            //header("Location: edit_product.php?id=" . $product_id);
            echo '<script>window.location.href = "edit_product.php?product_id=' . htmlspecialchars($product_id, ENT_QUOTES) . '";</script>';
            exit;
        }
    }
    
    // Обновляем данные товара
    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, category = ?, subcategory = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $description, $price, $quantity, $category, $subcategory, $imagePath, $product_id]);
    
    $_SESSION['message'] = "Товар успешно обновлен!";
    // header("Location: admin_panel.php");
    echo '<script>window.location.href = "admin_panel.php";</script>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование товара</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/stylesap.css?v=1">
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1>Редактирование товара</h1>
            <a href="admin_panel.php" class="back-btn"><i class="fas fa-arrow-left"></i> Назад</a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message">
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Название:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Описание:</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Цена:</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Количество:</label>
                <input type="number" name="quantity" value="<?= htmlspecialchars($product['quantity']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Категория:</label>
                <input type="text" name="category" value="<?= htmlspecialchars($product['category']) ?>" required list="categories">
                <datalist id="categories">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="form-group">
                <label>Подкатегория (необязательно):</label>
                <input type="text" name="subcategory" value="<?= htmlspecialchars($product['subcategory'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Текущее изображение:</label>
                <?php if ($product['image']): ?>
                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="Текущее изображение" style="max-width: 200px; display: block; margin-bottom: 10px;">
                <?php else: ?>
                    <p>Изображение не установлено</p>
                <?php endif; ?>
                
                <label>Новое изображение:</label>
                <input type="file" name="image" accept="image/*">
                <small>Оставьте пустым, чтобы сохранить текущее изображение</small>
            </div>
            
            <button type="submit" name="update_product" class="btn-primary"><i class="fas fa-save"></i> Сохранить изменения</button>
        </form>
    </div>
</body>
</html>