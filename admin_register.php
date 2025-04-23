<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $existing_admin = $stmt->fetch();

        if ($existing_admin) {
            $error = "Администратор с таким именем уже существует!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $password]);
            $success = "Администратор успешно зарегистрирован!";
        }
    } catch (PDOException $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация администратора</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --error-color: #ef233c;
            --success-color: #4cc9f0;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #333333;
            --border-radius: 10px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
        }

        .container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            text-align: center;
        }

        .card h1 {
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border 0.3s;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        .message {
            padding: 12px;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .error {
            background-color: rgba(239, 35, 60, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--primary-hover);
        }

        .back-link i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Регистрация администратора</h1>

            <?php if (isset($error)): ?>
                <div class="message error">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="message success">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn">Зарегистрировать</button>
            </form>

            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Назад
            </a>
        </div>
    </div>
</body>
</html>