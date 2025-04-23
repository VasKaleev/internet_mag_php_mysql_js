<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    try {
        // Проверка на существующего пользователя
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким именем или email уже существует';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $email, $phone, $address]);
            $success = 'Регистрация успешна! <a href="login.php">Войти в аккаунт</a>';
        }
    } catch (PDOException $e) {
        $error = "Ошибка регистрации: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="./css/stylesr.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6bff;
            --secondary-color: #6c757d;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(74, 107, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(74, 107, 255, 0.1) 0%, transparent 20%);
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .register-container:hover {
            transform: translateY(-5px);
        }
        
        .register-header {
            margin-bottom: 30px;
            position: relative;
        }
        
        .register-header i {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
            background: rgba(74, 107, 255, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .register-header h1 {
            color: var(--dark-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .back-home {
            position: absolute;
            top: 0;
            left: 0;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-home:hover {
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-size: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: var(--light-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
            outline: none;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            background-color: #3a5bef;
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            color: var(--danger-color);
            font-size: 14px;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(231, 76, 60, 0.1);
            border-radius: 6px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .success-message {
            color: var(--success-color);
            font-size: 14px;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 6px;
            display: none;
        }
        
        .success-message.show {
            display: block;
        }
        
        .login-link {
            margin-top: 25px;
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
                margin: 0 15px;
            }
        }
    </style>
</head>
<body>
    <!-- <a href="index.php" class="home-link">
        <i class="fas fa-long-arrow-alt-left"></i> На главную
    </a> -->
    <div class="register-container">
        <div class="register-header">
            <h1>Создать аккаунт</h1>
            <p>Заполните форму для регистрации</p>
        </div>
        
        <div class="error-message <?php echo $error ? 'show' : '' ?>">
            <?php echo $error ?>
        </div>
        
        <div class="success-message <?php echo $success ? 'show' : '' ?>">
            <?php echo $success ?>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Имя пользователя</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Придумайте имя пользователя" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Создайте надежный пароль" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Ваш email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="Номер телефона" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Адрес</label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Ваш адрес" required value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i> Зарегистрироваться
            </button>
        </form>
        
        <div class="login-link">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </div>
    </div>

    <script>
        // Анимация появления сообщений
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.error-message');
            const successMessage = document.querySelector('.success-message');
            
            if (errorMessage.classList.contains('show')) {
                setTimeout(() => {
                    errorMessage.style.opacity = '1';
                }, 100);
            }
            
            if (successMessage.classList.contains('show')) {
                setTimeout(() => {
                    successMessage.style.opacity = '1';
                }, 100);
            }
        });
    </script>
</body>
</html>