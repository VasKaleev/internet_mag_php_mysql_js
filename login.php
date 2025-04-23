<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Проверяем сначала администратора
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['login_success'] = true;
        //header("Location: admin_panel.php");
        echo '<script>window.location.href = "admin_panel.php";</script>';
        exit();
    }

    // Если не администратор, проверяем обычного пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_success'] = true;
        //header("Location: index.php");
        echo '<script>window.location.href = "index.php";</script>';
        exit();
    }

    $_SESSION['login_error'] = "Неверное имя пользователя или пароль";
    //header("Location: login.php");
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - МаркетПлюс</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/stylesl.css?v=1">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Вход в систему</h2>
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['login_error'] ?></div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Имя пользователя:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Войти</button>
            </form>
            <div class="login-links">
                <a href="register.php">Регистрация</a>
                <a href="index.php">На главную</a>
            </div>
        </div>
    </div>
</body>
</html>