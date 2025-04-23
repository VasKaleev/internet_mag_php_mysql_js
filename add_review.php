<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$product_id = $_POST['product_id'] ?? null;
$review_text = trim($_POST['review_text'] ?? '');

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit;
}

if (empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Текст отзыва не может быть пустым']);
    exit;
}

// Проверяем, существует ли товар
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit;
}

// Добавляем отзыв
$stmt = $pdo->prepare("INSERT INTO product_reviews (user_id, product_id, review) VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $product_id, $review_text]);

echo json_encode(['success' => true]);
?>