-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 14 Ara 2025, 20:30:00
-- Sunucu sürümü: 10.5.26-MariaDB
-- PHP Sürümü: 8.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `yilmazem_qr_menu`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `status`, `created_at`) VALUES
(1, 'Sıcak İçecekler', 1, '2024-12-12 00:35:25'),
(2, 'Soğuk İçecekler', 1, '2024-12-12 16:54:31'),
(4, 'Aparatifler', 1, '2024-12-12 22:34:51'),
(6, 'Tatlılar', 1, '2024-12-12 22:54:19'),
(7, 'Ana Yemekler', 1, '2024-12-13 00:46:14'),
(8, 'Vegan Yemekler', 1, '2024-12-13 00:46:31');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `image`, `status`, `created_at`) VALUES
(8, 1, 'Oralet', 'test', 10.00, '675a3dfadc969.jpeg', 1, '2024-12-12 01:35:54'),
(9, 1, 'Çay', 'rizenin enfes yaylalarından gelen eşsiz çaykur lezzeti ile damakta unutulmaz bir haz bırakan lezzet', 25.00, '675a3e2c168fa.jpg', 1, '2024-12-12 01:36:44'),
(10, 2, 'Limonata', 'Test', 30.00, '675b15876f415.jpg', 1, '2024-12-12 16:55:35'),
(11, 2, 'Gazoz', 'Test', 30.00, '675b36cb12b49.jpg', 1, '2024-12-12 19:17:31'),
(12, 1, 'Kahve', 'Test', 50.00, '675b37311be44.jpeg', 1, '2024-12-12 19:19:13'),
(13, 1, 'Salep', 'Test', 30.00, '675b5eacb72bd.jpg', 1, '2024-12-12 22:07:40'),
(14, 1, 'Ihlamur', 'Test', 25.00, '675b5f0f892f1.jpg', 1, '2024-12-12 22:09:19'),
(19, 6, 'Kadayıf', 'Tewst', 50.00, '', 1, '2024-12-13 01:14:34');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `filename`, `content`, `created_at`) VALUES
(10, 'qr_675b6da3aaa48.png', 'http://localhost/qr-menu/', '2024-12-12 23:11:31'),
(11, 'qr_67689e143b474.png', 'https://yilmazemre.com/qr-menu/', '2024-12-22 23:17:40'),
(12, 'qr_6782ca014aa44.png', 'https://yilmazemre.com/qr-menu/', '2025-01-11 19:44:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `recommended_products`
--

CREATE TABLE `recommended_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `is_new` tinyint(1) DEFAULT 0,
  `is_recommended` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_vegan` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `recommended_products`
--

INSERT INTO `recommended_products` (`id`, `product_id`, `is_new`, `is_recommended`, `created_at`, `is_vegan`) VALUES
(1, 9, 0, 1, '2024-12-12 18:06:44', 0),
(2, 10, 1, 1, '2024-12-12 18:20:56', 0),
(3, 11, 1, 0, '2024-12-12 22:22:35', 0),
(4, 19, 1, 1, '2024-12-14 21:33:58', 0),
(5, 12, 0, 1, '2024-12-14 21:34:05', 0),
(6, 13, 0, 1, '2024-12-14 21:34:08', 0),
(7, 14, 0, 1, '2024-12-14 21:35:03', 0),
(10, 8, 1, 0, '2025-01-03 21:59:06', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `site_title` varchar(100) NOT NULL,
  `site_description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `color_primary` varchar(20) DEFAULT '#34495e',
  `color_secondary` varchar(20) DEFAULT '#e67e22',
  `color_bg` varchar(20) DEFAULT '#f8f9fa',
  `color_text` varchar(20) DEFAULT '#2c3e50',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `site_title`, `site_description`, `logo`, `contact_email`, `contact_phone`, `address`, `footer_text`, `updated_at`) VALUES
(1, 'BlaBla Kafe', 'Lezzetli yemekler, taze içecekler ve eşsiz bir atmosfer...', '675b8870758e9.png', 'bilgi@yilmazemre.tr', '+90 545 657 91 37', 'Giresun', 'Lezzetli yemekler, taze içecekler ve eşsiz bir atmosfer...', '2024-12-13 01:05:52');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `page_views`
--

CREATE TABLE `page_views` (
  `id` int(11) NOT NULL,
  `view_date` date NOT NULL,
  `views` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `remember_token`, `status`, `created_at`) VALUES
(1, 'admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 'Admin User', 'admin@example.com', NULL, 1, '2024-12-11 23:57:33');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_no` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','preparing','completed','cancelled','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `slider_images`
--

CREATE TABLE `slider_images` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `button_text` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `waiter_calls`
--

CREATE TABLE `waiter_calls` (
  `id` int(11) NOT NULL,
  `table_no` varchar(50) NOT NULL,
  `call_type` varchar(50) NOT NULL,
  `status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Tablo için indeksler `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `recommended_products`
--
ALTER TABLE `recommended_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `page_views`
--
ALTER TABLE `page_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`view_date`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `slider_images`
--
ALTER TABLE `slider_images`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `waiter_calls`
--
ALTER TABLE `waiter_calls`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Tablo için AUTO_INCREMENT değeri `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `recommended_products`
--
ALTER TABLE `recommended_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `page_views`
--
ALTER TABLE `page_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `slider_images`
--
ALTER TABLE `slider_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `waiter_calls`
--
ALTER TABLE `waiter_calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `recommended_products`
--
ALTER TABLE `recommended_products`
  ADD CONSTRAINT `recommended_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
