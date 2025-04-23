<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Для добавления в корзину необходимо авторизоваться']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Некорректный запрос']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$quantity = (int)$_POST['quantity'];

// Получаем полную информацию о товаре
$stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit;
}

// Проверяем запрошенное количество
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Количество должно быть больше 0']);
    exit;
}

// Получаем текущее количество этого товара в корзине пользователя
$stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$in_cart = $stmt->fetchColumn() ?? 0;

// Рассчитываем доступное количество
$available = $product['quantity'] - $in_cart;

if ($quantity > $available) {
    echo json_encode([
        'success' => false, 
        'message' => "Недостаточно товара '{$product['name']}' на складе. Доступно для заказа: $available шт."
    ]);
    exit;
}

// Проверяем, есть ли уже товар в корзине
$stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? LIMIT 1");
$stmt->execute([$user_id, $product_id]);
$existing_item = $stmt->fetch();

if ($existing_item) {
    // Обновляем существующую запись
    $new_quantity = $existing_item['quantity'] + $quantity;
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$new_quantity, $existing_item['id']]);
} else {
    // Добавляем новую запись
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $quantity]);
}

// Получаем общее количество товаров в корзине
$stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart_count = $stmt->fetchColumn() ?? 0;

echo json_encode([
    'success' => true,
    'message' => 'Товар успешно добавлен в корзину',
    'cart_count' => $cart_count,
    'available' => $available - $quantity // Оставшееся доступное количество
]);