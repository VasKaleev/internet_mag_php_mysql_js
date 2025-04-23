<?php
header("HTTP/1.0 404 Not Found"); // Отправляем HTTP-заголовок 404
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена (404) | Интернет магазин</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f9f9f9;
            color: #333;
        }
        h1 {
            font-size: 50px;
        }
        p {
            font-size: 20px;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Извините, такой страницы в нашем интернет магазине не существует.</p>
    <p><a href="./">Вернуться на главную</a></p>
</body>
</html>