<?php
require 'db.php';

$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    echo json_encode([]);
    exit;
}

// Получаем отзывы с именами пользователей
$stmt = $pdo->prepare("
    SELECT pr.*, u.username 
    FROM product_reviews pr
    JOIN users u ON pr.user_id = u.id
    WHERE pr.product_id = ?
    ORDER BY pr.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

echo json_encode($reviews);
?>