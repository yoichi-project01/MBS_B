-- 修正版 MBS データベーススキーマ
-- セキュリティとパフォーマンスを向上させた版

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+09:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- データベースの作成（存在しない場合のみ）
CREATE DATABASE IF NOT EXISTS `mbs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mbs`;

-- --------------------------------------------------------

-- テーブルの構造 `customers`（修正版）
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customer_no` int(11) NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `address` varchar(500) NOT NULL,
  `telephone_number` varchar(20) NOT NULL,
  `delivery_conditions` varchar(500) DEFAULT NULL,
  `registration_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_no`),
  KEY `idx_store_name` (`store_name`),
  KEY `idx_customer_name` (`customer_name`),
  KEY `idx_registration_date` (`registration_date`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `chk_customer_no` CHECK (`customer_no` > 0),
  CONSTRAINT `chk_store_name` CHECK (`store_name` IN ('緑橋本店', '今里店', '深江橋店')),
  CONSTRAINT `chk_registration_date` CHECK (`registration_date` >= '1900-01-01' AND `registration_date` <= '2100-12-31')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- テーブルの構造 `deliveries`（修正版）
DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE `deliveries` (
  `delivery_no` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_record` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`delivery_no`),
  KEY `idx_delivery_record` (`delivery_record`),
  KEY `idx_total_amount` (`total_amount`),
  CONSTRAINT `chk_total_amount` CHECK (`total_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- テーブルの構造 `delivery_items`（修正版）
DROP TABLE IF EXISTS `delivery_items`;
CREATE TABLE `delivery_items` (
  `delivery_item_no` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_no` int(11) NOT NULL,
  `order_item_no` int(11) NOT NULL,
  `delivery_volume` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`delivery_item_no`),
  KEY `idx_delivery_no` (`delivery_no`),
  KEY `idx_order_item_no` (`order_item_no`),
  CONSTRAINT `chk_delivery_volume` CHECK (`delivery_volume` > 0),
  CONSTRAINT `chk_amount` CHECK (`amount` >= 0),
  CONSTRAINT `chk_tax` CHECK (`tax` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- テーブルの構造 `orders`（修正版）
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_no` int(11) NOT NULL AUTO_INCREMENT,
  `customer_no` int(11) NOT NULL,
  `registration_date` date NOT NULL,
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_no`),
  KEY `idx_customer_no` (`customer_no`),
  KEY `idx_registration_date` (`registration_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- テーブルの構造 `order_items`（修正版）
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `order_item_no` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` int(11) NOT NULL,
  `books` varchar(255) NOT NULL,
  `order_volume` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `abstract` text DEFAULT NULL,
  `order_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_item_no`),
  KEY `idx_order_no` (`order_no`),
  KEY `idx_books` (`books`),
  CONSTRAINT `chk_order_volume` CHECK (`order_volume` > 0),
  CONSTRAINT `chk_price` CHECK (`price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- テーブルの構造 `statistics_information`（修正版）
DROP TABLE IF EXISTS `statistics_information`;
CREATE TABLE `statistics_information` (
  `statistics_information_no` int(11) NOT NULL AUTO_INCREMENT,
  `customer_no` int(11) NOT NULL,
  `sales_by_customer` decimal(12,2) NOT NULL DEFAULT 0.00,
  `lead_time` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_amount` int(11) NOT NULL DEFAULT 0,
  `last_order_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`statistics_information_no`),
  UNIQUE KEY `idx_customer_no` (`customer_no`),
  KEY `idx_sales_by_customer` (`sales_by_customer`),
  KEY `idx_lead_time` (`lead_time`),
  KEY `idx_delivery_amount` (`delivery_amount`),
  CONSTRAINT `chk_sales_by_customer` CHECK (`sales_by_customer` >= 0),
  CONSTRAINT `chk_lead_time_positive` CHECK (`lead_time` >= 0),
  CONSTRAINT `chk_delivery_amount_positive` CHECK (`delivery_amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 外部キー制約の追加
ALTER TABLE `delivery_items`
  ADD CONSTRAINT `fk_delivery_items_delivery` FOREIGN KEY (`delivery_no`) REFERENCES `deliveries` (`delivery_no`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_delivery_items_order_item` FOREIGN KEY (`order_item_no`) REFERENCES `order_items` (`order_item_no`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_no`) REFERENCES `customers` (`customer_no`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_no`) REFERENCES `orders` (`order_no`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `statistics_information`
  ADD CONSTRAINT `fk_statistics_customer` FOREIGN KEY (`customer_no`) REFERENCES `customers` (`customer_no`) ON DELETE CASCADE ON UPDATE CASCADE;

-- サンプルデータの挿入（修正版）
INSERT INTO `customers` (`customer_no`, `store_name`, `customer_name`, `manager_name`, `address`, `telephone_number`, `delivery_conditions`, `registration_date`, `remarks`) VALUES
(10001, '緑橋本店', '野村圭太', NULL, '大阪市東成区中本4-3-2', '06-5315-9201', NULL, '1995-02-12', NULL),
(10002, '緑橋本店', '渡辺亮介', NULL, '大阪市東成区深江南10-5-1', '090-5106-3240', NULL, '1995-09-09', NULL),
(10003, '緑橋本店', '岩本春香', NULL, '大阪市東成区深江南6-4-3', '090-8606-5951', '日中不在', '1999-07-03', NULL),
(10004, '緑橋本店', 'フラワーショップ ブルーム', '村上拓哉', '大阪市東成区東小橋9-1-2', '090-4362-2124', NULL, '2000-04-08', NULL),
(10005, '緑橋本店', '木村紗希', NULL, '大阪市東成区深江南3-8-1', '06-9830-5304', NULL, '2000-06-12', NULL),
(20001, '深江橋店', '伊崎佳典', NULL, '大阪市東成区深江北3-4-2', '090-4685-4454', NULL, '2002-05-08', NULL),
(20002, '深江橋店', 'カフェ ブルーナ', '斐川美津子', '大阪市東成区神路3-1-1', '06-9339-6632', NULL, '2005-04-01', NULL),
(30001, '今里店', '長田翔', NULL, '大阪市東成区大今里4-4-9', '080-3531-7797', '日中不在', '1995-08-13', NULL),
(30002, '今里店', 'サロン ラ・ルーチェ', '渡辺亮介', '大阪市東成区東小橋2-2-7', '050-5936-2768', NULL, '1996-04-17', NULL),
(30003, '今里店', '中島葵', NULL, '大阪市東成区神路2-7-2', '080-5835-6549', NULL, '1996-08-29', NULL);

INSERT INTO `deliveries` (`delivery_no`, `delivery_record`, `total_amount`) VALUES
(1, '2025-06-19', 27000.00),
(2, '2025-06-19', 45600.00),
(3, '2025-06-20', 30400.00),
(4, '2025-06-20', 31000.00),
(5, '2025-06-21', 22400.00);

INSERT INTO `orders` (`order_no`, `customer_no`, `registration_date`, `status`) VALUES
(1, 10002, '2025-06-18', 'completed'),
(2, 10004, '2025-06-18', 'completed'),
(3, 10004, '2025-06-18', 'processing'),
(4, 10005, '2025-06-18', 'pending'),
(5, 10003, '2025-06-23', 'pending');

INSERT INTO `order_items` (`order_item_no`, `order_no`, `books`, `order_volume`, `price`, `abstract`, `order_remarks`) VALUES
(1, 1, 'ビジネス書籍セット A', 10, 1500.00, '経営戦略とマーケティングの基礎', '急ぎでお願いします'),
(2, 1, '自己啓発本セット B', 5, 1200.00, '個人成長とスキルアップ', NULL),
(3, 2, '技術書籍セット C', 8, 2000.00, 'プログラミングとIT技術', '新刊優先'),
(4, 2, 'デザイン書籍セット D', 12, 1800.00, 'グラフィックデザインとUI/UX', NULL),
(5, 3, '料理本セット E', 15, 1000.00, '和食とイタリアン料理', 'カフェ用');

INSERT INTO `delivery_items` (`delivery_item_no`, `delivery_no`, `order_item_no`, `delivery_volume`, `amount`, `tax`) VALUES
(1, 1, 1, 10, 15000.00, 1500.00),
(2, 1, 2, 5, 6000.00, 600.00),
(3, 2, 3, 8, 16000.00, 1600.00),
(4, 2, 4, 12, 21600.00, 2160.00),
(5, 3, 5, 15, 15000.00, 1500.00);

INSERT INTO `statistics_information` (`statistics_information_no`, `customer_no`, `sales_by_customer`, `lead_time`, `delivery_amount`, `last_order_date`) VALUES
(1, 10001, 254684.00, 2.50, 68, '2025-01-15'),
(2, 10002, 364871.00, 1.25, 25, '2025-06-18'),
(3, 10003, 364765.00, 3.75, 61, '2025-06-23'),
(4, 10004, 597415.00, 2.10, 125, '2025-06-18'),
(5, 10005, 579542.00, 4.20, 62, '2025-06-18'),
(6, 20001, 574610.00, 3.80, 97, '2025-02-10'),
(7, 20002, 574945.00, 2.90, 98, '2025-03-22'),
(8, 30001, 125666.00, 1.80, 10, '2025-01-08'),
(9, 30002, 135462.00, 2.30, 10, '2025-04-15'),
(10, 30003, 750000.00, 5.50, 15, '2025-05-20');

-- AUTO_INCREMENT の設定
ALTER TABLE `deliveries` AUTO_INCREMENT = 6;
ALTER TABLE `delivery_items` AUTO_INCREMENT = 6;
ALTER TABLE `orders` AUTO_INCREMENT = 6;
ALTER TABLE `order_items` AUTO_INCREMENT = 6;
ALTER TABLE `statistics_information` AUTO_INCREMENT = 11;

-- パフォーマンス向上のための追加インデックス
CREATE INDEX `idx_customers_store_registration` ON `customers` (`store_name`, `registration_date`);
CREATE INDEX `idx_customers_name_store` ON `customers` (`customer_name`, `store_name`);
CREATE INDEX `idx_orders_customer_date` ON `orders` (`customer_no`, `registration_date`);
CREATE INDEX `idx_statistics_sales_delivery` ON `statistics_information` (`sales_by_customer`, `delivery_amount`);

-- ビューの作成（よく使用されるクエリの最適化）
CREATE OR REPLACE VIEW `customer_summary` AS
SELECT 
    c.customer_no,
    c.customer_name,
    c.store_name,
    c.address,
    c.telephone_number,
    c.registration_date,
    COALESCE(s.sales_by_customer, 0) as total_sales,
    COALESCE(s.delivery_amount, 0) as delivery_count,
    COALESCE(s.lead_time, 0) as avg_lead_time,
    s.last_order_date,
    c.created_at,
    c.updated_at
FROM customers c
LEFT JOIN statistics_information s ON c.customer_no = s.customer_no;

-- セキュリティ向上のためのトリガー（ログ記録用）
DELIMITER $$

CREATE TRIGGER `customers_update_log` 
AFTER UPDATE ON `customers`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`table_name`, `operation`, `record_id`, `old_values`, `new_values`, `user`, `timestamp`)
    VALUES (
        'customers', 
        'UPDATE', 
        NEW.customer_no,
        CONCAT('name:', OLD.customer_name, ',store:', OLD.store_name),
        CONCAT('name:', NEW.customer_name, ',store:', NEW.store_name),
        USER(),
        NOW()
    );
END$$

DELIMITER ;

-- 監査ログテーブル（セキュリティ向上）
CREATE TABLE IF NOT EXISTS `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(64) NOT NULL,
  `operation` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `record_id` int(11) NOT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `user` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_table_operation` (`table_name`, `operation`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;