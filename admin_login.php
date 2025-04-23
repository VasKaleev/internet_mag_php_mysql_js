<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        //header("Location: admin_panel.php");
        echo '<script>window.location.href = "admin_panel.php";</script>';
        exit;
    } else {
        $error = 'Неверное имя пользователя или пароль';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/stylesal.css?v=1">

</head>

<body>
    <a href="index.php" class="home-link">
        <i class="fas fa-long-arrow-alt-left"></i> На главную
    </a>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-lock"></i>
            <h1>Административная панель</h1>
            <p>Введите ваши учетные данные для входа</p>
        </div>

        <div class="error-message <?php echo $error ? 'show' : '' ?>">
            <?php echo $error ?>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Имя пользователя</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Введите имя пользователя" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="input-wrapper">
                    <i class="fas fa-key"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Введите пароль" required>
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>
        </form>

        <div class="footer-links">
            <a href="#"><i class="fas fa-question-circle"></i> Забыли пароль?</a>
        </div>
    </div>

    <script>
        // Анимация появления ошибки
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage.classList.contains('show')) {
                setTimeout(() => {
                    errorMessage.style.opacity = '1';
                }, 100);
            }
        });
    </script>
</body>

</html>