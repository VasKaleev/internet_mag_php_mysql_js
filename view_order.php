<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();
require 'db_.php';

// Запрос с объединением таблиц
$query = "SELECT 
    o.id AS order_id,
    o.order_date,
    o.total_price AS order_total,
    o.status,
    o.comment,
    o.delivery_address,
    u.id AS user_id,
    u.username,
    u.email,
    u.phone,
    u.address AS user_address,
    oi.id AS order_item_id,
    oi.quantity,
    oi.price AS item_price,
    p.id AS product_id,
    p.name AS product_name,
    p.description AS product_description,
    p.price AS product_price,
    p.quantity AS product_stock,
    p.category,
    p.subcategory,
    p.image AS product_image
FROM 
    orders o
JOIN 
    users u ON o.user_id = u.id
JOIN 
    order_items oi ON o.id = oi.order_id
JOIN 
    products p ON oi.product_id = p.id
ORDER BY 
    o.order_date DESC, o.id, oi.id";

$result = mysqli_query($db, $query);

if (!$result) {
    die("Ошибка запроса: " . mysqli_error($db));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/stylesvo.css">
    <style>
        .order-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
        }
        .order-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .order-items {
            margin-top: 15px;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 5px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 3px;
        }
        .product-info {
            flex-grow: 1;
        }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-completed { color: #28a745; font-weight: bold; }
        .status-cancelled { color: #dc3545; font-weight: bold; }
        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .order-meta div {
            flex: 1;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-link">
        <i class="fas fa-long-arrow-alt-left"></i> На главную
    </a>
    <div class="container">
        <section>
            <h1 class="cat">Заказы товаров</h1>
            
            <?php
            $current_order_id = null;
            $first_row = true;
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Если это новый заказ, выводим его заголовок
                if ($current_order_id !== $row['order_id']) {
                    // Закрываем предыдущий заказ, если это не первая строка
                    if (!$first_row) {
                        echo '</div></div>'; // закрываем order-items и order-container
                    }
                    $first_row = false;
                    
                    $current_order_id = $row['order_id'];
                    
                    echo '<div class="order-container">';
                    echo '<div class="order-header">';
                    echo '<h2>Заказ #' . htmlspecialchars($row['order_id']) . '</h2>';
                    echo '<div class="order-meta">';
                    echo '<div><strong>Дата:</strong> ' . htmlspecialchars($row['order_date']) . '</div>';
                    echo '<div><strong>Статус:</strong> <span class="status-' . 
                         htmlspecialchars(strtolower($row['status'])) . '">' . 
                         htmlspecialchars($row['status']) . '</span></div>';
                    echo '<div><strong>Итого:</strong> ' . 
                         number_format($row['order_total'], 2, '.', ' ') . ' ₽</div>';
                    echo '</div>';
                    
                    echo '<div class="order-meta">';
                    echo '<div><strong>Покупатель:</strong> ' . 
                         htmlspecialchars($row['username']) . ' (ID: ' . 
                         htmlspecialchars($row['user_id']) . ')</div>';
                    echo '<div><strong>Email:</strong> ' . htmlspecialchars($row['email']) . '</div>';
                    echo '<div><strong>Телефон:</strong> ' . 
                         htmlspecialchars($row['phone'] ?? 'не указан') . '</div>';
                    echo '<div><strong>Адрес доставки:</strong> ' . 
                         htmlspecialchars($row['delivery_address'] ?? $row['user_address'] ?? 'не указан') . '</div>';
                    echo '</div>';
                    
                    if (!empty($row['comment'])) {
                        echo '<div><strong>Комментарий:</strong> ' . 
                             htmlspecialchars($row['comment']) . '</div>';
                    }
                    
                    echo '</div>'; // закрываем order-header
                    
                    echo '<div class="order-items">';
                    echo '<h3>Товары в заказе:</h3>';
                }
                
                // Выводим информацию о товаре
                echo '<div class="product-item">';
                echo '<img src="' . htmlspecialchars($row['product_image'] ?: 'images/no-image.jpg') . 
                     '" alt="' . htmlspecialchars($row['product_name']) . '" class="product-image">';
                echo '<div class="product-info">';
                echo '<h4>' . htmlspecialchars($row['product_name']) . '</h4>';
                echo '<p><strong>Категория:</strong> ' . 
                     htmlspecialchars($row['category'] . ($row['subcategory'] ? ' / ' . $row['subcategory'] : '')) . '</p>';
                echo '<p><strong>Цена:</strong> ' . number_format($row['item_price'], 2, '.', ' ') . ' ₽ × ' . 
                     htmlspecialchars($row['quantity']) . ' = ' . 
                     number_format($row['quantity'] * $row['item_price'], 2, '.', ' ') . ' ₽</p>';
                echo '</div>';
                echo '</div>';
            }
            
            // Закрываем последний заказ
            if (!$first_row) {
                echo '</div></div>'; // закрываем order-items и order-container
            } else {
                echo '<p>Заказов не найдено.</p>';
            }
            ?>
        </section>
    </div>
</body>
</html>