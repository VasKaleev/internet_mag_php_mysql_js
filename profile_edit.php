<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT username, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');  // Исправлено с name на username
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Валидация
    if (empty($username)) {
         $errors['username'] = 'Введите имя';
     }
    if (empty($email)) {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$username, $email, $phone, $address, $user_id]);
        $_SESSION['success_message'] = 'Данные успешно сохранены';
        header("Location: profile.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование профиля</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="profile-card">
                    <h4 class="mb-4">Мой профиль</h4>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Мои заказы</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile_edit.php">Личные данные</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Выход</a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="profile-card">
                    <h4 class="mb-4">Личные данные</h4>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Имя</label>
                            <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                   id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?= $errors['username'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= $errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Адрес доставки</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>