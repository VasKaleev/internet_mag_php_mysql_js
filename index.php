<?php
session_start();
require 'db.php';

// Проверяем, был ли пользователь перенаправлен после входа
if (isset($_SESSION['login_success']) && $_SESSION['login_success']) {
    $username = $_SESSION['username'] ?? 'Пользователь';
    echo "<script>showNotification('Добро пожаловать, $username!', 'success');</script>";
    unset($_SESSION['login_success']); // Удаляем флаг, чтобы сообщение не показывалось повторно
}

// Параметры пагинации
$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Фильтрация по категории и подкатегории
$category = isset($_GET['category']) ? $_GET['category'] : null;
$subcategory = isset($_GET['subcategory']) ? $_GET['subcategory'] : null;

// Фильтрация по цене
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

// Поисковый запрос
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Сортировка
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$order_by = '';
switch ($sort) {
    case 'price_asc':
        $order_by = 'price ASC';
        break;
    case 'price_desc':
        $order_by = 'price DESC';
        break;
    case 'name_asc':
    default:
        $order_by = 'name ASC';
}

// SQL-запрос для получения товаров
$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($category) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}
if ($subcategory) {
    $sql .= " AND subcategory = :subcategory";
    $params[':subcategory'] = $subcategory;
}
if ($search) {
    $sql .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($min_price !== null) {
    $sql .= " AND price >= :min_price";
    $params[':min_price'] = $min_price;
}
if ($max_price !== null) {
    $sql .= " AND price <= :max_price";
    $params[':max_price'] = $max_price;
}

$sql .= " ORDER BY $order_by LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Для каждого продукта получаем средний рейтинг и отзывы
foreach ($products as &$product) {
    // Средний рейтинг
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM product_ratings WHERE product_id = ?");
    $stmt->execute([$product['id']]);
    $rating = $stmt->fetch();
    $product['avg_rating'] = round($rating['avg_rating'], 1) ?? 0;

    // Количество отзывов
    $stmt = $pdo->prepare("SELECT COUNT(*) as review_count FROM product_reviews WHERE product_id = ?");
    $stmt->execute([$product['id']]);
    $review_count = $stmt->fetch();
    $product['review_count'] = $review_count['review_count'] ?? 0;

    // Рейтинг текущего пользователя (если авторизован)
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT rating FROM product_ratings WHERE product_id = ? AND user_id = ?");
        $stmt->execute([$product['id'], $_SESSION['user_id']]);
        $user_rating = $stmt->fetch();
        $product['user_rating'] = $user_rating['rating'] ?? null;
    }
}
unset($product); // Разрываем ссылку

// Получаем общее количество товаров с учетом поиска
$count_sql = "SELECT COUNT(*) AS total FROM products WHERE 1=1";
if ($category) {
    $count_sql .= " AND category = :category";
}
if ($subcategory) {
    $count_sql .= " AND subcategory = :subcategory";
}
if ($search) {
    $count_sql .= " AND (name LIKE :search OR description LIKE :search)";
}
if ($min_price !== null) {
    $count_sql .= " AND price >= :min_price";
}
if ($max_price !== null) {
    $count_sql .= " AND price <= :max_price";
}

$stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    if ($key !== ':limit' && $key !== ':offset') {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$total_items = $stmt->fetch()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Получаем список категорий и подкатегорий
$stmt = $pdo->query("SELECT DISTINCT category FROM products");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$subcategories = [];
if ($category) {
    $stmt = $pdo->prepare("SELECT DISTINCT subcategory FROM products WHERE category = ?");
    $stmt->execute([$category]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Получаем количество товаров в корзине
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn() ?? 0;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>МаркетПлюс - Интернет-магазин</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/styles.css?v=1">
</head>

<body>
    <!-- Обновленная шапка сайта -->
    <header>
        <div class="header-left">
            <div class="logo">
                <div class="logo-icon">
                    <!-- <a href='index.php'><i class="fas fa-shopping-bag"></i></a> -->
                    <a href='./'><i class="fas fa-shopping-bag"></i></a>
                </div>
                <div class="logo-text">
                    <h1>МаркетПлюс</h1>
                    <div class="tagline">Лучшие товары по выгодным ценам</div>
                </div>
            </div></a>

            <a href="https://maps.google.com/?q=г. Москва, ул. Тверская, д. 10" target="_blank" class="header-address">
                <i class="fas fa-map-marker-alt"></i>
                <span>г. Москва, ул. Тверская, д. 10</span>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-greeting" style="margin-left: auto; padding: 0 15px; color: white;">
                    Здравствуйте, <?= htmlspecialchars($_SESSION['username'] ?? 'Пользователь') ?>
                </div>
            <?php endif; ?>

            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php"><i class="fas fa-user"></i> Личный кабинет</a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i> Корзина <span class="cart-count"><?= $cart_count ?></span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Регистрация</a>
                <!-- <a href="admin_login.php"><i class="fas fa-user"></i> Администратор</a> -->
            <?php endif; ?>
        </nav>
    </header>

    <div class="mobile-nav" id="mobileNav">
        <span class="close-mobile-menu" id="closeMobileMenu">&times;</span>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php"><i class="fas fa-user"></i> Личный кабинет</a>
            <a href="cart.php"><i class="fas fa-shopping-cart"></i> Корзина <span class="cart-count"><?= $cart_count ?></span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        <?php else: ?>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
            <a href="register.php"><i class="fas fa-user-plus"></i> Регистрация</a>
            <!-- <a href="admin_login.php"><i class="fas fa-user"></i> Администратор</a> -->
        <?php endif; ?>

        <a href="https://maps.google.com/?q=г. Москва, ул. Тверская, д. 10" target="_blank">
            <i class="fas fa-map-marker-alt"></i> Адрес магазина
        </a>
        <a href="tel:+375 29 456 28 45">
            <i class="fas fa-phone"></i> +375 (29) 456-28-45
        </a>
    </div>

    <!-- Основной контент -->
    <main>
        <!-- Левое меню -->
        <aside>
            <h2>Категории</h2>
            <ul>
                <li><a href="./">Все товары</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="?category=<?= urlencode($cat) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= htmlspecialchars($cat) ?></a>
                        <?php if ($category === $cat && !empty($subcategories)): ?>
                            <ul class="submenu">
                                <?php foreach ($subcategories as $subcat): ?>
                                    <li><a href="?category=<?= urlencode($cat) ?>&subcategory=<?= urlencode($subcat) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= htmlspecialchars($subcat) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h2>Сортировка</h2>
            <ul>
                <li><a href="?sort=name_asc<?= $category ? '&category=' . urlencode($category) : '' ?><?= $subcategory ? '&subcategory=' . urlencode($subcategory) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $min_price !== null ? '&min_price=' . $min_price : '' ?><?= $max_price !== null ? '&max_price=' . $max_price : '' ?>">По алфавиту (A-Z)</a></li>
                <li><a href="?sort=price_asc<?= $category ? '&category=' . urlencode($category) : '' ?><?= $subcategory ? '&subcategory=' . urlencode($subcategory) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $min_price !== null ? '&min_price=' . $min_price : '' ?><?= $max_price !== null ? '&max_price=' . $max_price : '' ?>">По цене (↑)</a></li>
                <li><a href="?sort=price_desc<?= $category ? '&category=' . urlencode($category) : '' ?><?= $subcategory ? '&subcategory=' . urlencode($subcategory) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $min_price !== null ? '&min_price=' . $min_price : '' ?><?= $max_price !== null ? '&max_price=' . $max_price : '' ?>">По цене (↓)</a></li>
            </ul>

            <h2>Поиск по цене</h2>
            <form method="GET" class="price-filter">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category ?? '') ?>">
                <input type="hidden" name="subcategory" value="<?= htmlspecialchars($subcategory ?? '') ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search ?? '') ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort ?? '') ?>">

                <div class="price-inputs">
                    <input type="number" name="min_price" placeholder="От" value="<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '' ?>">
                    <span>-</span>
                    <input type="number" name="max_price" placeholder="До" value="<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '' ?>">
                </div>
                <button type="submit">Применить</button>
                <?php if (isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                    <a href="./<?=
                                http_build_query(array_filter([
                                    'category' => $category,
                                    'subcategory' => $subcategory,
                                    'search' => $search,
                                    'sort' => $sort,
                                    'page' => 1
                                ]))
                                ?>" class="reset-filter">Сбросить</a>
                <?php endif; ?>
            </form>
        </aside>

        <!-- Основное содержимое -->
        <div style="flex: 1;">
            <!-- Поисковая форма -->
            <div class="search-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Поиск товаров..." value="<?= htmlspecialchars($search) ?>">
                    <?php if ($category): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <?php endif; ?>
                    <?php if ($subcategory): ?>
                        <input type="hidden" name="subcategory" value="<?= htmlspecialchars($subcategory) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                    <?php if ($min_price !== null): ?>
                        <input type="hidden" name="min_price" value="<?= htmlspecialchars($min_price) ?>">
                    <?php endif; ?>
                    <?php if ($max_price !== null): ?>
                        <input type="hidden" name="max_price" value="<?= htmlspecialchars($max_price) ?>">
                    <?php endif; ?>
                    <button type="submit"><i class="fas fa-search"></i> Найти</button>
                    <?php if ($search): ?>
                        <!-- <a href="?" class="reset-search"><i class="fas fa-times"></i> Сбросить</a> -->
                        <a href="./" class="reset-search"><i class="fas fa-times"></i> Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($search && empty($products)): ?>
                <div style="background: white; padding: 20px; border-radius: 5px; text-align: center;">
                    <p>По вашему запросу "<?= htmlspecialchars($search) ?>" ничего не найдено.</p>
                    <!-- <a href="?" class="reset-search"><i class="fas fa-times"></i> Показать все товары</a> -->
                    <a href="./" class="reset-search"><i class="fas fa-times"></i> Показать все товары</a>
                </div>
            <?php endif; ?>

            <?php if ($min_price !== null || $max_price !== null): ?>
                <div class="current-price-filter">
                    Фильтр по цене:
                    <?= $min_price !== null ? 'от ' . number_format($min_price, 2, '.', ' ') . ' ₽' : '' ?>
                    <?= $max_price !== null ? 'до ' . number_format($max_price, 2, '.', ' ') . ' ₽' : '' ?>
                    <a href="./<?=
                                http_build_query(array_filter([
                                    'category' => $category,
                                    'subcategory' => $subcategory,
                                    'search' => $search,
                                    'sort' => $sort,
                                    'page' => 1
                                ]))
                                ?>" class="reset-filter"><i class="fas fa-times"></i></a>
                </div>
            <?php endif; ?>

            <!-- Карточки товаров -->
            <div class="products-container">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image" onclick="openModal(<?= htmlspecialchars(json_encode($product)) ?>)">
                            <img src="<?= htmlspecialchars($product['image'] ?: 'images/no-image.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price"><?= number_format($product['price'], 2, '.', ' ') ?> ₽</div>

                            <div class="rating-container">
                                <div class="rating-average"><?= $product['avg_rating'] ?></div>
                                <div class="rating-stars">
                                    <?php
                                    $fullStars = floor($product['avg_rating']);
                                    $hasHalfStar = $product['avg_rating'] - $fullStars >= 0.5;
                                    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

                                    for ($i = 0; $i < $fullStars; $i++) {
                                        echo '<i class="fas fa-star"></i>';
                                    }

                                    if ($hasHalfStar) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    }

                                    for ($i = 0; $i < $emptyStars; $i++) {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <div class="review-count"><?= $product['review_count'] ?> отзывов(а)</div>
                            </div>

                            <div class="product-quantity">Осталось: <?= htmlspecialchars($product['quantity']) ?> шт.</div>

                            <?php if ($product['quantity'] > 0): ?>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form class="add-to-cart-form" method="POST" action="add_to_cart.php">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <div style="display: flex; align-items: center;">
                                            <input type="number" name="quantity" min="1"
                                                max="<?= $product['quantity'] ?>"
                                                value="<?= min(1, $product['quantity']) ?>"
                                                <?= $product['quantity'] <= 0 ? 'disabled' : '' ?>>
                                            <button type="submit" <?= $product['quantity'] <= 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        </div>
                                        <?php if ($product['quantity'] <= 0): ?>
                                            <small class="text-danger">Товар закончился</small>
                                        <?php elseif ($product['quantity'] < 5): ?>
                                            <small class="text-warning">Осталось всего <?= $product['quantity'] ?> шт.</small>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <div class="login-notice" style="margin-top: 10px;">
                                        <a href="login.php" style="color: var(--primary-color); text-decoration: none;">
                                            <i class="fas fa-info-circle"></i> Для покупки войдите в аккаунт
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="out-of-stock">Нет в наличии</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Пагинация -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&sort=<?= $sort ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $subcategory ? '&subcategory=' . urlencode($subcategory) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $min_price !== null ? '&min_price=' . $min_price : '' ?><?= $max_price !== null ? '&max_price=' . $max_price : '' ?>"><i class="fas fa-chevron-left"></i> Назад</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&sort=<?= $sort ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $subcategory ? '&subcategory=' . urlencode($subcategory) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $min_price !== null ? '&min_price=' . $min_price : '' ?><?= $max_price !== null ? '&max_price=' . $max_price : '' ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&sort=<?= $sort ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $subcategory ? '&subcategory=' . urlencode($subcategory) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $min_price !== null ? '&min_price=' . $min_price : '' ?><?= $max_price !== null ? '&max_price=' . $max_price : '' ?>">Вперед <i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Модальное окно -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="modalProductImage" src="" alt="">
                </div>
                <div class="modal-details">
                    <h2 id="modalProductName" class="modal-title"></h2>
                    <div id="modalProductPrice" class="modal-price"></div>

                    <div class="rating-container">
                        <div class="rating-average" id="modalProductRating"></div>
                        <div class="rating-stars" id="modalRatingStars"></div>
                        <div class="review-count" id="modalReviewCount"></div>
                    </div>


                    <p class="custom-hr"></p>
                    <p id="modalProductDescription" class="modal-description"></p>

                    <form id="modalAddToCartForm" method="POST" action="add_to_cart.php" style="margin-top: 20px;">
                        <!-- <input type="hidden" id="modalProductId" name="product_id" value=""> -->
                        <input type="hidden" id="modalProductId" name="product_id" value="<?= $product['id'] ?>">
                        <!-- <div style="display: flex; align-items: center;">
                            <input type="number" name="quantity" min="1" value="1" style="width: 60px; margin-right: 10px;">
                            <button type="submit" style="padding: 8px 15px;"><i class="fas fa-cart-plus"></i> Добавить в корзину</button>
                        </div> -->
                    </form>
                    <!-- <p class="custom-hr"></p> -->
                    <div class="user-rating" id="userRatingContainer" style="display: none;">
                        <p class="custom-hr"></p>
                        <span>Ваше мнение важно!</span>
                        <p> Поставьте оценку от 1 до 5 и напишите отзыв:</p>
                        <div class="stars-container">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="star fas fa-star" data-rating="<?= $i ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="reviews-container" id="reviewsContainer">
                        <h3>Отзывы</h3>
                        <div id="reviewsList"></div>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="review-form">
                                <h4>Оставите отзыв о товаре</h4>
                                <form id="addReviewForm">
                                    <!-- <input type="hidden" id="reviewProductId" name="product_id" value=""> -->
                                    <input type="hidden" id="reviewProductId" name="product_id" value="<?= $product['id'] ?>">
                                    <textarea name="review_text" placeholder="Ваш отзыв..." required></textarea>
                                    <button type="submit">Отправить</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p>Чтобы оставить отзыв, <a href="login.php">войдите</a> в свой аккаунт.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Контейнер для уведомлений -->
    <div class="notification-container"></div>
    <!-- Кнопка "Наверх" -->
    <button class="back-to-top" id="backToTop" aria-label="Наверх">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Обработка кнопки "Наверх"
        const backToTopButton = document.getElementById('backToTop');

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('active');
            } else {
                backToTopButton.classList.remove('active');
            }
        });

        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Обработка бургер-меню
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileNav = document.getElementById('mobileNav');
        const closeMobileMenu = document.getElementById('closeMobileMenu');

        mobileMenuBtn.addEventListener('click', function() {
            mobileNav.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeMobileMenu.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        });

        const mobileLinks = mobileNav.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileNav.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Функции для работы с модальным окном
        function openModal(product) {
            const modal = document.getElementById('productModal');
            document.getElementById('modalProductImage').src = product.image || 'images/no-image.jpg';
            document.getElementById('modalProductName').textContent = product.name;
            document.getElementById('modalProductPrice').textContent = parseFloat(product.price).toFixed(2) + ' ₽';
            document.getElementById('modalProductDescription').textContent = product.description;
            document.getElementById('modalProductId').value = product.id;
            document.getElementById('reviewProductId').value = product.id;

            // Рейтинг товара
            document.getElementById('modalProductRating').textContent = product.avg_rating;
            renderRatingStars(product.avg_rating, 'modalRatingStars');
            document.getElementById('modalReviewCount').textContent = `${product.review_count} отзывов(а)`;

            // Показываем блок оценки, если пользователь авторизован
            const userRatingContainer = document.getElementById('userRatingContainer');
            if (<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
                userRatingContainer.style.display = 'block';

                // Устанавливаем текущую оценку пользователя
                const stars = document.querySelectorAll('.user-rating .star');
                stars.forEach(star => {
                    star.classList.toggle('active', star.dataset.rating <= product.user_rating);
                });
            } else {
                userRatingContainer.style.display = 'none';
            }

            // Загружаем отзывы
            loadReviews(product.id);

            modal.style.display = 'block';
            setTimeout(() => modal.style.opacity = '1', 10);
        }

        function closeModal() {
            const modal = document.getElementById('productModal');
            modal.style.opacity = '0';
            setTimeout(() => modal.style.display = 'none', 300);
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Обработка форм добавления в корзину
        document.querySelectorAll('.add-to-cart-form, #modalAddToCartForm').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const productId = formData.get('product_id');
                const quantity = parseInt(formData.get('quantity'), 10);

                if (quantity > 10) {
                    const confirmed = confirm(`Вы уверены, что хотите добавить ${quantity} единиц товара в корзину?`);
                    if (!confirmed) return;
                }

                fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showNotification(data.message, data.success ? 'success' : 'error');

                        if (data.success) {
                            const quantityElements = document.querySelectorAll(`.product-card input[name="product_id"][value="${productId}"]`);
                            quantityElements.forEach(input => {
                                const productInfo = input.closest('.product-info');
                                if (productInfo) {
                                    const quantityElement = productInfo.querySelector('.product-quantity');
                                    if (quantityElement && data.quantity !== undefined) {
                                        quantityElement.textContent = `Осталось: ${data.quantity} шт.`;

                                        // Обновляем максимальное значение в поле количества
                                        const quantityInput = productInfo.querySelector('input[name="quantity"]');
                                        if (quantityInput) {
                                            quantityInput.max = data.quantity;
                                            if (quantityInput.value > data.quantity) {
                                                quantityInput.value = data.quantity;
                                            }
                                        }

                                        // Если товар закончился, меняем форму
                                        if (data.quantity <= 0) {
                                            const form = productInfo.querySelector('form');
                                            if (form) {
                                                form.innerHTML = '<div class="out-of-stock">Нет в наличии</div>';
                                            }
                                        }
                                    }
                                }
                            });

                            const cartCountElements = document.querySelectorAll('.cart-count');
                            cartCountElements.forEach(el => {
                                el.textContent = data.cart_count;
                            });

                            if (this.id === 'modalAddToCartForm' && data.quantity <= 0) {
                                const form = this;
                                form.innerHTML = '<div class="out-of-stock">Товар закончился</div>';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка:', error);
                        showNotification('Произошла ошибка при добавлении товара', 'error');
                    });
            });
        });

        // Функция показа уведомлений
        function showNotification(message, type = 'success') {
            const container = document.querySelector('.notification-container');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            container.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 10);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Подсветка результатов поиска
        document.addEventListener('DOMContentLoaded', function() {
            const searchTerm = "<?= addslashes($search) ?>";
            if (searchTerm) {
                const regex = new RegExp(searchTerm.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'gi');
                document.querySelectorAll('.product-name, .product-info').forEach(el => {
                    el.innerHTML = el.innerHTML.replace(regex, match => `<span class="highlight">${match}</span>`);
                });
            }
        });

        // Функция для отображения звезд рейтинга
        function renderRatingStars(rating, elementId) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            let starsHtml = '';

            for (let i = 0; i < fullStars; i++) {
                starsHtml += '<i class="fas fa-star"></i>';
            }

            if (hasHalfStar) {
                starsHtml += '<i class="fas fa-star-half-alt"></i>';
            }

            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
            for (let i = 0; i < emptyStars; i++) {
                starsHtml += '<i class="far fa-star"></i>';
            }

            document.getElementById(elementId).innerHTML = starsHtml;
        }

        // Функция для загрузки отзывов
        function loadReviews(productId) {
            fetch(`get_reviews.php?product_id=${productId}`)
                .then(response => response.json())
                .then(reviews => {
                    const reviewsList = document.getElementById('reviewsList');
                    reviewsList.innerHTML = '';

                    if (reviews.length === 0) {
                        reviewsList.innerHTML = '<p>Пока нет отзывов. Будьте первым!</p>';
                        return;
                    }

                    reviews.forEach(review => {
                        const reviewItem = document.createElement('div');
                        reviewItem.className = 'review-item';
                        reviewItem.innerHTML = `
                            <div class="review-author">${review.username}</div>
                            <div class="review-date">${new Date(review.created_at).toLocaleDateString()}</div>
                            <div class="review-text">${review.review}</div>
                        `;
                        reviewsList.appendChild(reviewItem);
                    });
                });
        }

        // Обработка оценки товара
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('star')) {
                const rating = parseInt(e.target.dataset.rating);
                const productId = document.getElementById('modalProductId').value;

                fetch('rate_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}&rating=${rating}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Ваша оценка сохранена', 'success');

                            const stars = document.querySelectorAll('.user-rating .star');
                            stars.forEach(star => {
                                star.classList.toggle('active', star.dataset.rating <= rating);
                            });

                            document.getElementById('modalProductRating').textContent = data.avg_rating;
                            renderRatingStars(data.avg_rating, 'modalRatingStars');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    });
            }
        });

        // Обработка формы отзыва
        document.getElementById('addReviewForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('add_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Ваш отзыв добавлен', 'success');
                        this.reset();
                        loadReviews(document.getElementById('reviewProductId').value);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
        });
    </script>
</body>

</html>