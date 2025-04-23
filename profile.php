<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT username, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Получаем заказы пользователя
$stmt = $pdo->prepare("SELECT id, total_price, order_date, status FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/stylesp.css">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="profile-card">
                    <h4 class="mb-4">Мой профиль</h4>
                    <h5>Заказчик: <?= $user['username'] ?></h5>
                    <h5>Номер телефона: <?= $user['phone'] ?></h5>
                    <h5>Адрес доставки: <?= $user['address'] ?></h5>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">Мои заказы</a>
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
                <div class="profile-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Мои заказы</h4>
                        <a href="index.php" class="btn btn-outline-primary">Вернуться к покупкам</a>
                    </div>
                    
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">У вас пока нет заказов</div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6>Заказ #<?= $order['id'] ?></h6>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></small>
                                    </div>
                                    <div class="col-md-3">
                                        <h6><?= $order['total_price'] ?> ₽</h6>
                                    </div>
                                    <div class="col-md-3">
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
                                        <span class="badge rounded-pill bg-light <?= $status_class ?>"><?= $status_text ?></span>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-secondary">Подробнее</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>