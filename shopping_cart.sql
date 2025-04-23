-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Апр 16 2025 г., 09:01
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `shopping_cart`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'administrator', '$2y$10$432WHSrCZjTRl/rOo6Y/Puqu/8s0DNAEehAKmB/3pvTq57wDoUdz6'),
(2, 'vasily', '$2y$10$8P4/PxVfWUWKjwFh2MuxF.vQeSM2zdL7bFt/mBs0CySTQ22YQ.h0K');

-- --------------------------------------------------------

--
-- Структура таблицы `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`) VALUES
(27, 1, 4, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `comment` text NOT NULL,
  `delivery_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `created_at`, `status`, `order_date`, `comment`, `delivery_address`) VALUES
(11, 1, 80.00, '2025-04-15 09:19:30', 'pending', '2025-04-15 09:19:30', '', NULL),
(12, 1, 120.00, '2025-04-15 09:19:35', 'pending', '2025-04-15 09:19:35', '', NULL),
(13, 3, 250.00, '2025-04-15 13:54:38', 'pending', '2025-04-15 13:54:38', '', NULL),
(14, 2, 250.00, '2025-04-15 13:59:45', 'pending', '2025-04-15 13:59:45', '', NULL),
(15, 2, 80.00, '2025-04-15 14:04:43', 'pending', '2025-04-15 14:04:43', '', NULL),
(16, 1, 500.00, '2025-04-16 06:13:16', 'pending', '2025-04-16 06:13:16', '', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(20, 11, 7, 1, 80.00),
(21, 12, 8, 1, 120.00),
(22, 13, 4, 1, 250.00),
(23, 14, 4, 1, 250.00),
(24, 15, 7, 1, 80.00),
(25, 16, 4, 2, 250.00);

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `quantity`, `category`, `subcategory`, `image`) VALUES
(3, 'Intel Core i7', 'Процессор Intel Core i7', 300.00, 10, 'Процессоры', 'Intel', 'images/nout_intel.webp'),
(4, 'AMD Ryzen 5', 'Процессор AMD Ryzen 5', 250.00, 10, 'Процессоры', 'AMD', 'images/razen5_5600.jpg'),
(5, 'NVIDIA RTX 3060', 'Видеокарта NVIDIA RTX 3060', 400.00, 8, 'Видеокарты', 'NVIDIA', 'images/rx600.webp'),
(6, 'AMD Radeon RX 6700', 'Видеокарта AMD Radeon RX 6700', 350.00, 8, 'Видеокарты', 'AMD', 'images/amd6700.webp'),
(7, 'Corsair DDR4', 'Оперативная память DDR4', 80.00, 16, 'Оперативная память', 'DDR4', 'images/cors4.webp'),
(8, 'Corsair DDR5', 'Оперативная память DDR5', 120.00, 9, 'Оперативная память', 'DDR5', 'images/cors600.jpg'),
(9, 'Samsung SSD 500GB', 'SSD накопитель 500GB', 70.00, 24, 'Накопители', 'SSD', 'images/3060.webp'),
(10, 'Seagate HDD 1TB', 'HDD накопитель 1TB', 50.00, 29, 'Накопители', 'HDD', 'images/seg1TB.jpg'),
(11, 'Intel Core i7', 'Процессор Intel Core i7', 300.00, 10, 'Процессоры', 'Intel', 'images/icore7.jpg'),
(12, 'AMD Ryzen 5', 'Процессор AMD Ryzen 5', 250.00, 9, 'Процессоры', 'AMD', 'images/amd_ryzen_5.webp'),
(13, 'NVIDIA RTX 3060', 'Видеокарта NVIDIA RTX 3060', 400.00, 8, 'Видеокарты', 'NVIDIA', 'images/3060.webp'),
(14, 'AMD Radeon RX 6700', 'Видеокарта AMD Radeon RX 6700', 350.00, 8, 'Видеокарты', 'AMD', 'images/amd_r_6700.jpg'),
(15, 'Тест', 'Тестовый товар', 12345.00, 5, 'Оперативная память', 'ddr6', '');

-- --------------------------------------------------------

--
-- Структура таблицы `product_ratings`
--

CREATE TABLE `product_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 10),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `product_ratings`
--

INSERT INTO `product_ratings` (`id`, `user_id`, `product_id`, `rating`, `created_at`) VALUES
(1, 1, 4, 5, '2025-04-14 13:54:09'),
(2, 1, 10, 5, '2025-04-15 06:12:13'),
(3, 1, 12, 5, '2025-04-15 08:10:26'),
(4, 3, 4, 5, '2025-04-15 13:50:45'),
(5, 2, 4, 5, '2025-04-15 13:53:46');

-- --------------------------------------------------------

--
-- Структура таблицы `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `review` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `user_id`, `product_id`, `review`, `created_at`) VALUES
(1, 1, 14, 'Хороший товар', '2025-04-14 13:45:09'),
(2, 1, 6, 'ячсячсяч', '2025-04-14 13:45:28'),
(3, 1, 4, 'wq', '2025-04-14 14:04:58'),
(4, 1, 10, 'Гоод!', '2025-04-15 06:12:26'),
(5, 3, 4, 'Круто!', '2025-04-15 13:51:01'),
(6, 2, 4, 'Классный товар', '2025-04-15 13:54:04');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `address`) VALUES
(1, 'admin', '$2y$10$432WHSrCZjTRl/rOo6Y/Puqu/8s0DNAEehAKmB/3pvTq57wDoUdz6', 'kaleev.fam@mail.ru', '+375 (29) 320-20-86', 'вафываывавы'),
(2, 'test', '$2y$10$432WHSrCZjTRl/rOo6Y/Puqu/8s0DNAEehAKmB/3pvTq57wDoUdz6', 'vkaleev.fam@gmail.com', '+375293202086', 'Рогачев'),
(3, '2', '$2y$10$ifnf8D7qjyb2Mn0tTAv2DOdkYk/sA9tcf3ZKkn4FgjMHzoys.VDOG', 'kaleev1.fam@mail.ru', '+375 (29) 320-20-86', 'Рогачев ул Богатырева 159 65');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Индексы таблицы `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `product_ratings`
--
ALTER TABLE `product_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `product_ratings`
--
ALTER TABLE `product_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ограничения внешнего ключа таблицы `product_ratings`
--
ALTER TABLE `product_ratings`
  ADD CONSTRAINT `product_ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `product_ratings_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ограничения внешнего ключа таблицы `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
