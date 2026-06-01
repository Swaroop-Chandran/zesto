# Zesto Nights - PHP 8+ MVC Integration Guide

Greetings! This directory contains the exact, production-ready PHP views/components extracted from the "Zesto Nights" dark cinematic design system screens.

Follow this guide to bind these front-end views into your existing Apache, XAMPP, or manual PHP MVC backend.

---

## 1. MySQL Database Layout Blueprint
To back these screens, ensure your database has at least the following relational structure:

```sql
-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Delivery Addresses Table
CREATE TABLE IF NOT EXISTS `addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `label` VARCHAR(20) DEFAULT 'Home', -- 'Home', 'Work', 'Other'
  `address_line` VARCHAR(255) NOT NULL,
  `details` VARCHAR(255) DEFAULT NULL,
  `is_selected` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Restaurants Table (Thattukadas)
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `rating` DECIMAL(3,2) DEFAULT 4.5,
  `reviews_count` INT DEFAULT 0,
  `specialty` VARCHAR(255) NOT NULL,
  `delivery_time_mins` INT DEFAULT 25,
  `open_until` VARCHAR(10) DEFAULT '4 AM',
  `distance_km` DECIMAL(3,1) DEFAULT 1.5,
  `image_url` VARCHAR(255) NOT NULL,
  `is_featured` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Food Items Table (Menu)
CREATE TABLE IF NOT EXISTS `food_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT NOT NULL,
  `name` VARCHAR(105) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `category` VARCHAR(50) NOT NULL, -- 'Porotta', 'Beef Roast', 'Kappa', 'Black Tea'
  `image_url` VARCHAR(255) NOT NULL,
  `is_bestseller` TINYINT(1) DEFAULT 0,
  `spice_level` INT DEFAULT 1,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `restaurant_id` INT NOT NULL,
  `address_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `delivery_fee` DECIMAL(5,2) DEFAULT 50.00,
  `taxes` DECIMAL(5,2) DEFAULT 20.00,
  `total` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(20) DEFAULT 'UPI', -- 'UPI', 'Card', 'NetBanking', 'COD'
  `status` VARCHAR(30) DEFAULT 'received', -- 'received', 'cooking', 'out_for_delivery', 'delivered'
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`),
  FOREIGN KEY (`address_id`) REFERENCES `addresses`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 2. Integration Instructions
1. **Asset Deployment**:
   Place the Tailwind CSS link or compiled stylesheet in your primary layout HTML wrapper.
   Include the Google Font families "Syne" and "Be Vietnam Pro".
   
2. **MVC routing layout pattern**:
   - `header.php` and `footer.php` act as global site headers/footers.
   - Inject dynamic session data like variables `$isLoggedIn` and `$userName` in `header.php`.
   - Bind query resultsets into the `foreach()` loops provided inside `index.php`, `checkout.php`, and `order_tracking.php`.

3. **Responsive Assets**:
   Use standard Bootstrap style mappings or include Tailwind directly from CDN inside your master layout:
   `<script src="https://cdn.tailwindcss.com"></script>`
