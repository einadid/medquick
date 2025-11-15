
-- Main users table with roles
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `role` ENUM('customer', 'salesman', 'shop_admin', 'admin') NOT NULL DEFAULT 'customer',
  `shop_id` INT NULL, -- For shop_admin and salesman
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `tfa_secret` VARCHAR(255) NULL -- Placeholder for 2FA
) ENGINE=InnoDB;

-- Shops
CREATE TABLE `shops` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Master medicine catalog
CREATE TABLE `medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `manufacturer` VARCHAR(100) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `reorder_level` INT NOT NULL DEFAULT 10, -- Trigger for low-stock alert
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_med_name` (`name`),
  INDEX `idx_med_manufacturer` (`manufacturer`),
  INDEX `idx_med_category` (`category`)
) ENGINE=InnoDB;

-- Per-shop inventory with batches (core of the inventory system)
CREATE TABLE `inventory_batches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `medicine_id` INT NOT NULL,
  `shop_id` INT NOT NULL,
  `batch_number` VARCHAR(50) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `expiry_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE CASCADE,
  INDEX `idx_expiry` (`expiry_date`) -- Crucial for FEFO
) ENGINE=InnoDB;

-- Orders
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `shop_id` INT NOT NULL, -- The shop that fulfilled the order
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `order_status` ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery',
  `order_source` ENUM('web', 'pos') NOT NULL DEFAULT 'web', -- To distinguish customer vs salesman sales
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`)
) ENGINE=InnoDB;

-- Items within an order
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `medicine_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price_per_unit` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`)
) ENGINE=InnoDB;

-- Security: Login rate limiting
CREATE TABLE `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Security: Audit Log
CREATE TABLE `audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Pre-populate shops for testing
INSERT INTO `shops` (`name`, `address`) VALUES
('QuickMed Main Branch', '123 Health St, Dhaka'),
('QuickMed Gulshan', '456 Wellness Ave, Gulshan');