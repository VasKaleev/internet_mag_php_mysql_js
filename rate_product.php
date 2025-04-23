<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$product_id = $_POST['product_id'] ?? null;
$rating = $_POST['rating'] ?? null;

if (!$product_id || !$rating) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit;
}

// Проверяем, существует ли товар
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit;
}

// Проверяем, оценивал ли уже пользователь этот товар
$stmt = $pdo->prepare("SELECT id FROM product_ratings WHERE user_id = ? AND product_id = ?");
$stmt->execute([$_SESSION['user_id'], $product_id]);

if ($stmt->fetch()) {
    // Обновляем существующую оценку
    $stmt = $pdo->prepare("UPDATE product_ratings SET rating = ? WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$rating, $_SESSION['user_id'], $product_id]);
} else {
    // Добавляем новую оценку
    $stmt = $pdo->prepare("INSERT INTO product_ratings (user_id, product_id, rating) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $product_id, $rating]);
}

// Получаем новый средний рейтинг
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM product_ratings WHERE product_id = ?");
$stmt->execute([$product_id]);
$avg_rating = $stmt->fetch()['avg_rating'];

echo json_encode([
    'success' => true,
    'avg_rating' => round($avg_rating, 1)
]);
?>