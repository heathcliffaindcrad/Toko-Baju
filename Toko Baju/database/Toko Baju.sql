-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for toko_pakaian
CREATE DATABASE IF NOT EXISTS `toko_pakaian` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `toko_pakaian`;

-- Dumping structure for table toko_pakaian.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table toko_pakaian.categories: ~3 rows (approximately)
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
	(1, 'Pria', 'Pakaian untuk pria', '2025-11-29 11:33:09'),
	(2, 'Wanita', 'Pakaian untuk wanita', '2025-11-29 11:33:09'),
	(9, 'Anak - Anak', 'Pakaian untuk anak - anak', '2025-11-29 23:50:36');

-- Dumping structure for table toko_pakaian.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table toko_pakaian.notifications: ~0 rows (approximately)
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
	(1, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Paid', 'info', 0, '2025-11-29 23:13:42'),
	(2, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Processing', 'info', 0, '2025-11-29 23:18:03'),
	(3, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Completed', 'info', 0, '2025-11-29 23:23:04'),
	(4, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Pending', 'info', 0, '2025-11-29 23:23:31'),
	(5, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Completed', 'info', 0, '2025-11-29 23:23:48'),
	(6, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Shipped', 'info', 0, '2025-11-30 00:23:25'),
	(7, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Completed', 'info', 0, '2025-11-30 00:23:31'),
	(8, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Pending', 'info', 0, '2025-11-30 00:25:51'),
	(9, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Completed', 'info', 0, '2025-11-30 00:26:08'),
	(10, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Ready pickup', 'info', 0, '2025-11-30 00:28:14'),
	(11, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Processing', 'info', 0, '2025-11-30 00:28:50'),
	(12, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Completed', 'info', 0, '2025-11-30 01:36:52'),
	(13, 2, 'Status Pesanan Diperbarui', 'Status pesanan Anda telah diubah menjadi: Completed', 'info', 0, '2025-11-30 01:46:16');

-- Dumping structure for table toko_pakaian.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category_id` int DEFAULT NULL,
  `size` enum('S','M','L','XL','XXL') NOT NULL,
  `color` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table toko_pakaian.products: ~1 rows (approximately)
INSERT INTO `products` (`id`, `name`, `description`, `category_id`, `size`, `color`, `price`, `stock`, `image`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'Hoodie', 'Hoodie Jumper Dewasa Hitam POLOS Premium', 1, 'L', 'Hitam', 150000.00, 6, '1764418853_hoodie.jpg', 1, '2025-11-29 12:20:53', '2025-11-30 01:44:04'),
	(3, 'Kemeja Wispie', 'Kemeja kerja wanita fit garis stripe karet Lembut', 2, 'M', 'Merah Maaron', 120000.00, 1, '1764460202_kemeja.jpg', 1, '2025-11-29 23:50:02', '2025-11-30 01:44:04');

-- Dumping structure for table toko_pakaian.settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_name` varchar(255) NOT NULL,
  `store_address` text,
  `store_phone` varchar(20) DEFAULT NULL,
  `store_email` varchar(100) DEFAULT NULL,
  `promo_text` text,
  `low_stock_threshold` int DEFAULT '10',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table toko_pakaian.settings: ~1 rows (approximately)
INSERT INTO `settings` (`id`, `store_name`, `store_address`, `store_phone`, `store_email`, `promo_text`, `low_stock_threshold`, `created_at`, `updated_at`) VALUES
	(1, 'Toko Pakaian Kita', 'Jl. Contoh No. 123, Jakarta', '021-1234567', 'info@tokopakaian.com', 'Diskon spesial untuk pembelian pertama!', 10, '2025-11-29 11:33:09', '2025-11-29 11:33:09');

-- Dumping structure for table toko_pakaian.transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `transaction_code` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','processing','ready_pickup','shipped','completed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `shipping_address` text,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_code` (`transaction_code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table toko_pakaian.transactions: ~1 rows (approximately)
INSERT INTO `transactions` (`id`, `user_id`, `transaction_code`, `total_amount`, `status`, `payment_method`, `paid_at`, `payment_proof`, `shipping_address`, `notes`, `created_at`, `updated_at`) VALUES
	(1, 2, 'TRX20251129692AECC814DC4', 150000.00, 'completed', 'transfer', NULL, NULL, '', NULL, '2025-11-29 12:53:28', '2025-11-30 01:36:52'),
	(2, 2, 'TRX20251130692B932F38CBF', 240000.00, 'paid', 'cod', '2025-11-30 08:43:16', 'COD - No proof required', '', NULL, '2025-11-30 00:43:27', '2025-11-30 01:43:16'),
	(3, 2, 'TRX20251130692B93844B62A', 420000.00, 'cancelled', 'transfer', NULL, NULL, '', NULL, '2025-11-30 00:44:52', '2025-11-30 01:01:14'),
	(4, 2, 'TRX20251130692BA16462AFC', 270000.00, 'completed', 'transfer', '2025-11-30 08:44:22', 'payments/payment_proof_TRX20251130692BA16462AFC_1764467062.jpg', '', NULL, '2025-11-30 01:44:04', '2025-11-30 01:46:16');

-- Dumping structure for table toko_pakaian.transaction_items
CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table toko_pakaian.transaction_items: ~1 rows (approximately)
INSERT INTO `transaction_items` (`id`, `transaction_id`, `product_id`, `quantity`, `price`) VALUES
	(1, 1, 1, 1, 150000.00),
	(2, 2, 3, 2, 120000.00),
	(3, 3, 1, 2, 150000.00),
	(4, 3, 3, 1, 120000.00),
	(5, 4, 3, 1, 120000.00),
	(6, 4, 1, 1, 150000.00);

-- Dumping structure for table toko_pakaian.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table toko_pakaian.users: ~1 rows (approximately)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `role`, `created_at`, `updated_at`) VALUES
	(1, 'admin', 'admin@tokopakaian.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, NULL, 'admin', '2025-11-29 11:33:09', '2025-11-29 11:33:09'),
	(2, 'Bambang', 'Bambang@gmail.com', '$2y$10$EoNoXUt7gE4jkUglz1ja9.cYRrnqxEnXzVlOKvkFIUB4STAI8bLbu', 'Bambang Surya Prana', '0895123456789', 'Perum PDL', 'user', '2025-11-29 11:50:10', '2025-11-29 22:43:50');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
