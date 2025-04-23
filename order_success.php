<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    //header("Location: login.php");
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Получаем информацию о заказе и пользователе
$stmt = $pdo->prepare("SELECT o.id, o.order_date, o.total_price, u.email, u.username as user_name,
                      COUNT(oi.id) as items_count 
                      FROM orders o 
                      LEFT JOIN order_items oi ON o.id = oi.order_id 
                      LEFT JOIN users u ON o.user_id = u.id
                      WHERE o.id = ? AND o.user_id = ? 
                      GROUP BY o.id");
$stmt->execute([$order_id, $_SESSION['user_id']]);
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

// Функция для отправки email
function sendOrderEmail($to, $order, $order_items)
{
    $subject = "Детали вашего заказа #" . $order['id'];

    // Формируем HTML-содержание письма
    $message = '
    <html>
    <head>
        <title>Детали заказа #' . $order['id'] . '</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .order-header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; }
            .order-item { border-bottom: 1px solid #eee; padding: 10px 0; }
            .product-image { max-width: 80px; height: auto; }
            .order-total { font-weight: bold; font-size: 1.2em; }
        </style>
    </head>
    <body>
        <h2>Здравствуйте, ' . htmlspecialchars($order['user_name']) . '!</h2>
        <p>Благодарим вас за заказ в нашем магазине. Вот детали вашего заказа:</p>
        
        <div class="order-header">
            <p><strong>Номер заказа:</strong> #' . $order['id'] . '</p>
            <p><strong>Дата заказа:</strong> ' . date('d.m.Y H:i', strtotime($order['order_date'])) . '</p>
        </div>
        
        <h3>Состав заказа:</h3>';

    foreach ($order_items as $item) {
        $message .= '
        <div class="order-item">
            <img src="' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" class="product-image">
            <p><strong>' . htmlspecialchars($item['name']) . '</strong></p>
            <p>Количество: ' . $item['quantity'] . ' шт.</p>
            <p>Цена: ' . ($item['price'] * $item['quantity']) . ' ₽</p>
        </div>';
    }

    $message .= '
        <div class="order-total">
            <p>Итого к оплате: ' . $order['total_price'] . ' ₽</p>
        </div>
        
        <p>Вы можете отслеживать статус заказа в <a href="http://вашсайт.ru/profile.php">личном кабинете</a>.</p>
        <p>С уважением,<br>Команда магазина</p>
    </body>
    </html>';

    // Заголовки письма
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Магазин <noreply@вашсайт.ru>\r\n";

    // Отправка письма
    return mail($to, $subject, $message, $headers);
}

// Отправляем письмо администратору (vkaleev.fam@gmail.com) и пользователю
$email_sent = false;
if (!empty($order['email'])) {
    $email_sent = sendOrderEmail('vkaleev.fam@gmail.com', $order, $order_items);
    // Дублируем письмо пользователю, если email указан
    sendOrderEmail($order['email'], $order, $order_items);
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Заказ оформлен</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/stylesos.css?v=1">
</head>

<body>
    <div class="container">
        <div class="success-card text-center">
            <div class="success-icon">✓</div>
            <h1 class="mb-3">Спасибо за ваш заказ!</h1>
            <p class="lead mb-4">Ваш заказ #<?= $order['id'] ?> успешно оформлен.</p>

            <?php if ($email_sent): ?>
                <div class="email-status email-success">
                    Детали заказа отправлены на вашу электронную почту и администратору.
                </div>
            <?php else: ?>
                <div class="email-status email-error">
                    Не удалось отправить письмо с деталями заказа. Пожалуйста, проверьте данные в личном кабинете.
                </div>
            <?php endif; ?>

            <div class="text-start">
                <!-- Остальная часть кода с деталями заказа остается без изменений -->
                <!-- ... -->
            </div>
        </div>
    </div>
</body>

</html>