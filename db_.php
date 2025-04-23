<?php
define('DB_HOST', 'localhost'); // сервер БД
define('DB_USER', 'shopping'); // логин БД
define('DB_PASS', ''); // пароль БД
define('DB_NAME', 'shopping_cart'); // имя БД
//Подключение на endels
$db = mysqli_connect("localhost", "shopping", "", "shopping_cart")  OR DIE("Не могу создать соединение2 ");
?>