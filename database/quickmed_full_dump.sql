-- QuickMed Database Schema with Sample Data
-- Version 1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+06:00";

CREATE DATABASE IF NOT EXISTS `quickmed` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quickmed`;

-- ============================================
-- ROLES TABLE
-- ============================================
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `role_name`, `description`) VALUES
(1, 'customer', 'Regular customer who can purchase medicines'),
(2, 'salesman', 'Shop salesman who handles POS and walk-in sales'),
(3, 'shop_manager', 'Manager of a specific shop'),
(4, 'admin', 'System administrator with full access');

-- ============================================
-- SHOPS TABLE
-- ============================================
CREATE TABLE `shops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `phone` varchar(20),
  `email` varchar(255),
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `shops` (`id`, `name`, `address`, `city`, `phone`, `email`, `status`) VALUES
(1, 'QuickMed Dhaka Main', 'House 45, Road 12, Dhanmondi, Dhaka-1209', 'Dhaka', '01711111111', 'dhaka@quickmed.com', 'active'),
(2, 'QuickMed Chittagong', 'Station Road, Agrabad, Chittagong-4100', 'Chittagong', '01722222222', 'ctg@quickmed.com', 'active'),
(3, 'QuickMed Sylhet', 'Zindabazar, Sylhet-3100', 'Sylhet', '01733333333', 'sylhet@quickmed.com', 'active'),
(4, 'QuickMed Khulna', 'KDA Avenue, Khulna-9100', 'Khulna', '01744444444', 'khulna@quickmed.com', 'active');

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20),
  `address` text,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64),
  `status` enum('active','banned','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `shop_id` (`shop_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password for all: password123
INSERT INTO `users` (`id`, `role_id`, `shop_id`, `email`, `password`, `full_name`, `phone`, `address`, `status`) VALUES
(1, 4, NULL, 'admin@quickmed.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '01700000000', 'Head Office, Dhaka', 'active'),
(2, 3, 1, 'manager.dhaka@quickmed.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Karim Uddin', '01711111111', 'Dhaka', 'active'),
(3, 3, 2, 'manager.ctg@quickmed.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahim Ahmed', '01722222222', 'Chittagong', 'active'),
(4, 2, 1, 'salesman1@quickmed.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jamal Hossain', '01755555555', 'Dhaka', 'active'),
(5, 2, 2, 'salesman2@quickmed.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rafiq Khan', '01766666666', 'Chittagong', 'active'),
(6, 1, NULL, 'customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fatima Begum', '01777777777', 'Dhanmondi, Dhaka', 'active'),
(7, 1, NULL, 'customer2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Abdul Kadir', '01788888888', 'Agrabad, Chittagong', 'active');

-- ============================================
-- SIGNUP CODES TABLE
-- ============================================
CREATE TABLE `signup_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `role_id` int(11) NOT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_by` varchar(255),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `signup_codes_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `signup_codes` (`code`, `role_id`, `shop_id`, `used`) VALUES
('qm-admin-01', 4, NULL, 0),
('qm-admin-02', 4, NULL, 0),
('qm-manager-05', 3, 3, 0),
('qm-manager-06', 3, 4, 0),
('qm-salesman-12', 2, 3, 0),
('qm-salesman-13', 2, 4, 0);

-- ============================================
-- CATEGORIES TABLE
-- ============================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Antibiotics', 'Antibacterial medications'),
(2, 'Pain Relief', 'Analgesics and pain management'),
(3, 'Diabetes', 'Diabetes management medications'),
(4, 'Cardiovascular', 'Heart and blood pressure medications'),
(5, 'Respiratory', 'Asthma and respiratory medicines'),
(6, 'Vitamins', 'Vitamin supplements and nutrition'),
(7, 'Antacids', 'Digestive and gastric medicines'),
(8, 'Antihistamines', 'Allergy medications');

-- ============================================
-- MEDICINES TABLE
-- ============================================
CREATE TABLE `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) NOT NULL,
  `description` text,
  `image` varchar(255),
  `manufacturer` varchar(255),
  `dosage_form` varchar(100),
  `strength` varchar(100),
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `medicines` (`id`, `category_id`, `name`, `generic_name`, `description`, `manufacturer`, `dosage_form`, `strength`, `status`) VALUES
(1, 1, 'Napa', 'Paracetamol', 'Pain relief and fever reducer', 'Beximco Pharmaceuticals', 'Tablet', '500mg', 'active'),
(2, 1, 'Napa Extra', 'Paracetamol + Caffeine', 'Fast acting pain relief', 'Beximco Pharmaceuticals', 'Tablet', '500mg+65mg', 'active'),
(3, 2, 'Seclo', 'Omeprazole', 'Proton pump inhibitor for gastric problems', 'Square Pharmaceuticals', 'Capsule', '20mg', 'active'),
(4, 2, 'Ace', 'Ranitidine', 'Histamine H2 blocker for acidity', 'Square Pharmaceuticals', 'Tablet', '150mg', 'active'),
(5, 3, 'Glimec', 'Glimepiride', 'Type 2 diabetes management', 'Incepta Pharmaceuticals', 'Tablet', '2mg', 'active'),
(6, 3, 'Metformin', 'Metformin HCl', 'Diabetes medication', 'Renata Limited', 'Tablet', '500mg', 'active'),
(7, 4, 'Amlodipine', 'Amlodipine', 'Blood pressure medication', 'Healthcare Pharmaceuticals', 'Tablet', '5mg', 'active'),
(8, 4, 'Losar', 'Losartan Potassium', 'Hypertension treatment', 'Aristopharma', 'Tablet', '50mg', 'active'),
(9, 5, 'Montelukast', 'Montelukast', 'Asthma prevention', 'ACI Limited', 'Tablet', '10mg', 'active'),
(10, 5, 'Ventolin', 'Salbutamol', 'Bronchodilator inhaler', 'GSK Bangladesh', 'Inhaler', '100mcg', 'active'),
(11, 6, 'Vitamin D3', 'Cholecalciferol', 'Vitamin D supplement', 'Opsonin Pharma', 'Capsule', '40000 IU', 'active'),
(12, 6, 'B-Complex', 'B-Complex Vitamins', 'Multi B-vitamin supplement', 'Square Pharmaceuticals', 'Tablet', 'Multi', 'active'),
(13, 1, 'Monas', 'Montelukast', 'Antibiotic for infections', 'Square Pharmaceuticals', 'Tablet', '500mg', 'active'),
(14, 1, 'Azithromycin', 'Azithromycin', 'Broad spectrum antibiotic', 'Beximco Pharmaceuticals', 'Tablet', '500mg', 'active'),
(15, 2, 'Maxpro', 'Esomeprazole', 'Advanced gastric protection', 'Renata Limited', 'Capsule', '40mg', 'active'),
(16, 7, 'Antacid Plus', 'Aluminum Hydroxide + Magnesium', 'Fast acting antacid', 'ACI Limited', 'Syrup', '200ml', 'active'),
(17, 8, 'Fexo', 'Fexofenadine', 'Non-drowsy allergy relief', 'Square Pharmaceuticals', 'Tablet', '120mg', 'active'),
(18, 8, 'Cetirizine', 'Cetirizine HCl', 'Allergy and cold relief', 'Incepta Pharmaceuticals', 'Tablet', '10mg', 'active'),
(19, 2, 'Ibuprofen', 'Ibuprofen', 'Anti-inflammatory pain relief', 'Healthcare Pharmaceuticals', 'Tablet', '400mg', 'active'),
(20, 2, 'Diclofenac', 'Diclofenac Sodium', 'Strong pain reliever', 'Aristopharma', 'Tablet', '50mg', 'active'),
(21, 3, 'Insulin Actrapid', 'Human Insulin', 'Fast acting insulin', 'Novo Nordisk', 'Injection', '100IU/ml', 'active'),
(22, 4, 'Atorvastatin', 'Atorvastatin', 'Cholesterol management', 'Beximco Pharmaceuticals', 'Tablet', '20mg', 'active'),
(23, 4, 'Enalapril', 'Enalapril', 'ACE inhibitor for hypertension', 'Square Pharmaceuticals', 'Tablet', '5mg', 'active'),
(24, 5, 'Theophylline', 'Theophylline', 'Bronchodilator', 'Renata Limited', 'Tablet', '200mg', 'active'),
(25, 6, 'Calcium + D3', 'Calcium Carbonate + Vitamin D3', 'Bone health supplement', 'Opsonin Pharma', 'Tablet', '500mg+200IU', 'active'),
(26, 6, 'Iron Folic', 'Ferrous Sulfate + Folic Acid', 'Anemia prevention', 'ACI Limited', 'Tablet', '150mg+0.5mg', 'active'),
(27, 1, 'Ciprofloxacin', 'Ciprofloxacin', 'Broad spectrum antibiotic', 'Incepta Pharmaceuticals', 'Tablet', '500mg', 'active'),
(28, 7, 'Domperidone', 'Domperidone', 'Anti-nausea medication', 'Square Pharmaceuticals', 'Tablet', '10mg', 'active'),
(29, 2, 'Tramadol', 'Tramadol HCl', 'Moderate to severe pain relief', 'Healthcare Pharmaceuticals', 'Capsule', '50mg', 'active'),
(30, 8, 'Loratadine', 'Loratadine', 'Long-lasting allergy relief', 'Aristopharma', 'Tablet', '10mg', 'active');

-- ============================================
-- SHOP_MEDICINES TABLE (Stock Management)
-- ============================================
CREATE TABLE `shop_medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `buying_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `expiry_date` date,
  `batch_number` varchar(100),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shop_medicine_unique` (`shop_id`,`medicine_id`,`batch_number`),
  KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `shop_medicines_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`),
  CONSTRAINT `shop_medicines_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample stock for Dhaka Main shop
INSERT INTO `shop_medicines` (`shop_id`, `medicine_id`, `stock`, `buying_price`, `selling_price`, `expiry_date`, `batch_number`) VALUES
(1, 1, 500, 0.80, 1.50, '2025-12-31', 'NAPA2024A'),
(1, 2, 300, 1.20, 2.00, '2025-10-15', 'NAPAE2024B'),
(1, 3, 200, 3.50, 5.00, '2026-03-20', 'SEC2024C'),
(1, 4, 150, 2.00, 3.50, '2025-08-10', 'ACE2024D'),
(1, 5, 100, 8.00, 12.00, '2026-01-25', 'GLI2024E'),
(1, 6, 400, 1.50, 2.50, '2025-11-30', 'MET2024F'),
(1, 7, 250, 2.50, 4.00, '2026-05-15', 'AML2024G'),
(1, 8, 180, 4.00, 6.50, '2025-09-20', 'LOS2024H'),
(1, 9, 120, 15.00, 22.00, '2026-02-28', 'MON2024I'),
(1, 10, 80, 120.00, 180.00, '2025-12-15', 'VEN2024J'),
(1, 11, 90, 25.00, 35.00, '2026-06-30', 'VD32024K'),
(1, 12, 350, 2.00, 3.50, '2025-10-25', 'BCX2024L'),
(1, 13, 160, 12.00, 18.00, 'active', 'MON2024M'),
(1, 14, 140, 18.00, 28.00, '2026-04-10', 'AZI2024N'),
(1, 15, 110, 6.50, 10.00, '2025-11-20', 'MAX2024O');

-- Sample stock for Chittagong shop
INSERT INTO `shop_medicines` (`shop_id`, `medicine_id`, `stock`, `buying_price`, `selling_price`, `expiry_date`, `batch_number`) VALUES
(2, 1, 400, 0.80, 1.50, '2025-12-31', 'NAPA2024A'),
(2, 3, 180, 3.50, 5.00, '2026-03-20', 'SEC2024C'),
(2, 5, 90, 8.00, 12.00, '2026-01-25', 'GLI2024E'),
(2, 7, 200, 2.50, 4.00, '2026-05-15', 'AML2024G'),
(2, 10, 60, 120.00, 180.00, '2025-12-15', 'VEN2024J'),
(2, 16, 150, 80.00, 120.00, '2025-08-30', 'ANT2024P'),
(2, 17, 130, 10.00, 15.00, '2026-02-14', 'FEX2024Q'),
(2, 18, 200, 1.50, 2.50, '2025-09-18', 'CET2024R'),
(2, 19, 170, 3.00, 5.00, '2026-01-22', 'IBU2024S'),
(2, 20, 100, 4.50, 7.00, '2025-10-28', 'DIC2024T');

-- Sample stock for Sylhet shop
INSERT INTO `shop_medicines` (`shop_id`, `medicine_id`, `stock`, `buying_price`, `selling_price`, `expiry_date`, `batch_number`) VALUES
(3, 1, 350, 0.80, 1.50, '2025-12-31', 'NAPA2024A'),
(3, 2, 250, 1.20, 2.00, '2025-10-15', 'NAPAE2024B'),
(3, 6, 300, 1.50, 2.50, '2025-11-30', 'MET2024F'),
(3, 12, 280, 2.00, 3.50, '2025-10-25', 'BCX2024L'),
(3, 21, 40, 450.00, 650.00, '2025-07-15', 'INS2024U'),
(3, 22, 120, 12.00, 18.00, '2026-03-08', 'ATO2024V'),
(3, 23, 140, 5.00, 8.00, '2025-12-12', 'ENA2024W'),
(3, 24, 90, 6.00, 9.50, '2026-01-30', 'THE2024X');

-- Sample stock for Khulna shop
INSERT INTO `shop_medicines` (`shop_id`, `medicine_id`, `stock`, `buying_price`, `selling_price`, `expiry_date`, `batch_number`) VALUES
(4, 1, 450, 0.80, 1.50, '2025-12-31', 'NAPA2024A'),
(4, 4, 160, 2.00, 3.50, '2025-08-10', 'ACE2024D'),
(4, 8, 150, 4.00, 6.50, '2025-09-20', 'LOS2024H'),
(4, 11, 100, 25.00, 35.00, '2026-06-30', 'VD32024K'),
(4, 25, 180, 15.00, 22.00, '2026-04-25', 'CAD2024Y'),
(4, 26, 220, 3.50, 5.50, '2025-11-18', 'IRO2024Z'),
(4, 27, 110, 14.00, 22.00, '2026-02-05', 'CIP2024AA'),
(4, 28, 190, 2.50, 4.00, '2025-09-22', 'DOM2024AB'),
(4, 29, 70, 8.00, 12.50, '2026-01-14', 'TRA2024AC'),
(4, 30, 160, 2.00, 3.50, '2025-10-30', 'LOR2024AD');

-- ============================================
-- ORDERS TABLE
-- ============================================
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_type` enum('home','pickup') NOT NULL,
  `delivery_address` text,
  `delivery_phone` varchar(20),
  `points_used` int(11) DEFAULT 0,
  `status` enum('pending','confirmed','processing','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample orders
INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `delivery_type`, `delivery_address`, `delivery_phone`, `points_used`, `status`) VALUES
(1, 6, 215.00, 'home', 'Dhanmondi, Dhaka', '01777777777', 0, 'completed'),
(2, 7, 350.50, 'pickup', 'Agrabad, Chittagong', '01788888888', 50, 'completed'),
(3, 6, 180.00, 'home', 'Dhanmondi, Dhaka', '01777777777', 0, 'processing');

-- ============================================
-- PARCELS TABLE
-- ============================================
CREATE TABLE `parcels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','packed','at_hub','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `shop_id` (`shop_id`),
  CONSTRAINT `parcels_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `parcels_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `parcels` (`id`, `order_id`, `shop_id`, `total_amount`, `status`) VALUES
(1, 1, 1, 115.00, 'delivered'),
(2, 2, 2, 250.50, 'delivered'),
(3, 3, 1, 80.00, 'packed');

-- ============================================
-- PARCEL STATUS LOG TABLE
-- ============================================
CREATE TABLE `parcel_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parcel_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parcel_id` (`parcel_id`),
  CONSTRAINT `parcel_status_log_ibfk_1` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `parcel_status_log` (`parcel_id`, `status`, `notes`) VALUES
(1, 'pending', 'Parcel created'),
(1, 'confirmed', 'Order confirmed by shop'),
(1, 'packed', 'Package ready'),
(1, 'at_hub', 'Arrived at main hub'),
(1, 'out_for_delivery', 'Out for delivery'),
(1, 'delivered', 'Successfully delivered'),
(2, 'pending', 'Parcel created'),
(2, 'confirmed', 'Order confirmed'),
(2, 'delivered', 'Picked up by customer'),
(3, 'pending', 'Parcel created'),
(3, 'packed', 'Ready for shipment');

-- ============================================
-- ORDER ITEMS TABLE
-- ============================================
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `parcel_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `parcel_id` (`parcel_id`),
  KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`),
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_items` (`order_id`, `parcel_id`, `medicine_id`, `shop_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 10, 1.50),
(1, 1, 3, 1, 20, 5.00),
(2, 2, 17, 2, 5, 15.00),
(2, 2, 18, 2, 30, 2.50),
(2, 2, 19, 2, 15, 5.00),
(3, 3, 1, 1, 20, 1.50),
(3, 3, 6, 1, 20, 2.50);

-- ============================================
-- CART TABLE
-- ============================================
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shop_medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `shop_medicine_id` (`shop_medicine_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`shop_medicine_id`) REFERENCES `shop_medicines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- LOYALTY TRANSACTIONS TABLE
-- ============================================
CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type` enum('earned','redeemed') NOT NULL,
  `description` text,
  `order_id` int(11),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `loyalty_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `loyalty_transactions` (`user_id`, `points`, `type`, `description`) VALUES
(6, 100, 'earned', 'Signup Bonus'),
(7, 100, 'earned', 'Signup Bonus'),
(6, 21, 'earned', 'Points earned from order #1'),
(7, -50, 'redeemed', 'Points redeemed for order #2'),
(7, 35, 'earned', 'Points earned from order #2');

-- ============================================
-- NEWS TABLE
-- ============================================
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255),
  `published_by` int(11) NOT NULL,
  `status` enum('draft','published') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `published_by` (`published_by`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `news` (`title`, `content`, `published_by`, `status`) VALUES
('Tips for Managing Diabetes', 'Regular exercise, proper diet, and medication adherence are key to managing diabetes effectively. Consult your doctor for personalized advice.', 1, 'published'),
('Importance of Vaccination', 'Vaccines save millions of lives every year. Stay updated with your vaccination schedule to protect yourself and your community.', 1, 'published'),
('Seasonal Flu Prevention', 'Wash hands frequently, avoid close contact with sick people, and maintain a healthy lifestyle to prevent seasonal flu.', 1, 'published');

-- ============================================
-- AUDIT LOGS TABLE
-- ============================================
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `action` varchar(255) NOT NULL,
  `details` text,
  `ip_address` varchar(45),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- SESSIONS TABLE (Optional - for advanced session management)
-- ============================================
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11),
  `data` text,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;