<?php
$host = 'localhost';
$dbname = 'f29320ds_intmag';
$username = 'f29320ds_intmag';
$password = 'Lytgh1';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>