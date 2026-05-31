-- ============================================================
-- Zesto Food Delivery — Database Setup
-- Compatible with MySQL 8+ / MariaDB 10.4+
-- Run this file once via phpMyAdmin or: mysql -u root zesto_db < setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS zesto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zesto;

-- ─────────────────────────────────────────────────────────────
-- 1. USERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL,
    password      VARCHAR(255)  NOT NULL,
    phone         VARCHAR(20)   DEFAULT NULL,
    role          ENUM('customer','restaurant_owner','delivery_partner','admin') NOT NULL DEFAULT 'customer',
    avatar        VARCHAR(500)  DEFAULT NULL,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_role (email, role),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 2. CATEGORIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    image         VARCHAR(500)  DEFAULT NULL,
    display_order INT           NOT NULL DEFAULT 0,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 3. RESTAURANTS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS restaurants (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id            INT UNSIGNED DEFAULT NULL,
    slug                VARCHAR(120)    NOT NULL UNIQUE,
    name                VARCHAR(150)    NOT NULL,
    tags                VARCHAR(255)    DEFAULT NULL,
    description         TEXT            DEFAULT NULL,
    rating              DECIMAL(3,1)    NOT NULL DEFAULT 0.0,
    rating_count        INT             NOT NULL DEFAULT 0,
    delivery_time       VARCHAR(30)     DEFAULT NULL,
    delivery_time_value INT             DEFAULT NULL COMMENT 'minutes, used for sorting',
    distance            DECIMAL(5,2)    DEFAULT NULL COMMENT 'miles',
    delivery_fee        DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    is_free_delivery    TINYINT(1)      NOT NULL DEFAULT 0,
    discount            VARCHAR(50)     DEFAULT NULL,
    image               VARCHAR(500)    DEFAULT NULL,
    banner_image        VARCHAR(500)    DEFAULT NULL,
    logo_image          VARCHAR(500)    DEFAULT NULL,
    address             TEXT            DEFAULT NULL,
    city                VARCHAR(100)    NOT NULL DEFAULT 'Mumbai',
    phone               VARCHAR(30)     DEFAULT NULL,
    operating_hours     VARCHAR(150)    DEFAULT '9:00 AM - 10:00 PM',
    delivery_radius     DECIMAL(5,2)    DEFAULT 5.00,
    is_featured         TINYINT(1)      NOT NULL DEFAULT 0,
    is_popular          TINYINT(1)      NOT NULL DEFAULT 0,
    is_best_rated       TINYINT(1)      NOT NULL DEFAULT 0,
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug   (slug),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 4. MENU ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS menu_items (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id         INT UNSIGNED NOT NULL,
    category_id           INT UNSIGNED DEFAULT NULL,
    name                  VARCHAR(150) NOT NULL,
    description           TEXT         DEFAULT NULL,
    price                 DECIMAL(10,2) NOT NULL,
    image                 VARCHAR(500)  DEFAULT NULL,
    customization_options JSON          DEFAULT NULL,
    is_veg                TINYINT(1)   NOT NULL DEFAULT 1,
    is_available          TINYINT(1)   NOT NULL DEFAULT 1,
    is_special            TINYINT(1)   NOT NULL DEFAULT 0,
    is_popular            TINYINT(1)   NOT NULL DEFAULT 0,
    is_trending           TINYINT(1)   NOT NULL DEFAULT 0,
    display_order         INT          NOT NULL DEFAULT 0,
    created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_restaurant (restaurant_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 5. CART (for logged-in users; guests use session)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cart (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    menu_item_id    INT UNSIGNED NOT NULL,
    restaurant_id   INT UNSIGNED NOT NULL,
    quantity        INT          NOT NULL DEFAULT 1,
    customization   VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id)  REFERENCES menu_items(id)  ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_cart_item (user_id, menu_item_id, customization)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 6. ORDERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number        VARCHAR(20)     NOT NULL UNIQUE,
    user_id             INT UNSIGNED    DEFAULT NULL,
    restaurant_id       INT UNSIGNED    NOT NULL,
    delivery_partner_id INT UNSIGNED    DEFAULT NULL,
    delivery_address    TEXT            NOT NULL,
    payment_method      VARCHAR(60)     DEFAULT NULL,
    payment_status      ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    order_status        ENUM('placed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'placed',
    subtotal            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    delivery_fee        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    taxes               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    discount            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    coupon_code         VARCHAR(50)     DEFAULT NULL,
    total               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    notes               TEXT            DEFAULT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)             REFERENCES users(id)         ON DELETE SET NULL,
    FOREIGN KEY (restaurant_id)       REFERENCES restaurants(id)   ON DELETE RESTRICT,
    FOREIGN KEY (delivery_partner_id) REFERENCES users(id)         ON DELETE SET NULL,
    INDEX idx_user        (user_id),
    INDEX idx_restaurant  (restaurant_id),
    INDEX idx_status      (order_status),
    INDEX idx_created     (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 7. ORDER ITEMS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED  NOT NULL,
    menu_item_id    INT UNSIGNED  NOT NULL,
    item_name       VARCHAR(150)  NOT NULL COMMENT 'snapshot at time of order',
    item_price      DECIMAL(10,2) NOT NULL COMMENT 'snapshot at time of order',
    quantity        INT           NOT NULL DEFAULT 1,
    customization   VARCHAR(255)  DEFAULT NULL,
    FOREIGN KEY (order_id)     REFERENCES orders(id)     ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 8. DELIVERY PARTNERS (extends users)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS delivery_partners (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED NOT NULL UNIQUE,
    vehicle_type      VARCHAR(50)  DEFAULT 'bike',
    vehicle_number    VARCHAR(50)  DEFAULT NULL,
    driving_license_number VARCHAR(100) DEFAULT NULL,
    driving_license_image  VARCHAR(500) DEFAULT NULL,
    selfie_image      VARCHAR(500) DEFAULT NULL,
    bank_details      TEXT         DEFAULT NULL,
    address           TEXT         DEFAULT NULL,
    is_approved       TINYINT(1)   NOT NULL DEFAULT 0,
    is_available      TINYINT(1)   NOT NULL DEFAULT 1,
    total_deliveries  INT          NOT NULL DEFAULT 0,
    rating            DECIMAL(3,1) NOT NULL DEFAULT 5.0,
    total_earnings    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- SEED DATA
-- ─────────────────────────────────────────────────────────────

-- ─────────────────────────────────────────────────────────────
-- DEMO CREDENTIALS — ALL ACCOUNTS USE PASSWORD: Zesto@123
-- Hash generated with: password_hash('Zesto@123', PASSWORD_BCRYPT, ['cost' => 10])
-- ─────────────────────────────────────────────────────────────

-- Seed admin user (email: admin@zesto.com / password: Zesto@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin', 'admin@zesto.com', '$2y$10$MZ5HS/mNwvb3DSw63q0X..wZ9FwsB9QQKpj6DFTkJH6fwpPdbAOOy', 'admin');

-- Seed demo customer (email: alex@example.com / password: Zesto@123)
INSERT IGNORE INTO users (name, email, password, phone, role) VALUES
('Alex Johnson', 'alex@example.com', '$2y$10$MZ5HS/mNwvb3DSw63q0X..wZ9FwsB9QQKpj6DFTkJH6fwpPdbAOOy', '+91 98765 43210', 'customer');

-- Seed restaurant owner (email: mario@zesto.com / password: Zesto@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Mario Rossi', 'mario@zesto.com', '$2y$10$MZ5HS/mNwvb3DSw63q0X..wZ9FwsB9QQKpj6DFTkJH6fwpPdbAOOy', 'restaurant_owner');

-- Seed delivery partner (email: marcus@zesto.com / password: Zesto@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Marcus Rodriguez', 'marcus@zesto.com', '$2y$10$MZ5HS/mNwvb3DSw63q0X..wZ9FwsB9QQKpj6DFTkJH6fwpPdbAOOy', 'delivery_partner');

-- Clean up old seed data first to avoid duplicate errors or key mismatches
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE menu_items;
TRUNCATE TABLE restaurants;
TRUNCATE TABLE categories;
SET FOREIGN_KEY_CHECKS = 1;

-- Seed categories (Indian and International favorites)
INSERT INTO categories (id, name, image, display_order) VALUES
(1, 'Pizza', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&q=80', 1),
(2, 'Burgers', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80', 2),
(3, 'Biryani', 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=800&q=80', 3),
(4, 'South Indian', 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?w=800&q=80', 4),
(5, 'North Indian', 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&q=80', 5),
(6, 'Chinese', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=800&q=80', 6),
(7, 'Desserts', 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=800&q=80', 7),
(8, 'Healthy', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&q=80', 8),
(9, 'Drinks', 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&q=80', 9);

-- Seed restaurants (Indian and INR-focused in Mumbai)
INSERT INTO restaurants (id, owner_id, slug, name, tags, description, rating, rating_count, delivery_time, delivery_time_value, distance, delivery_fee, is_free_delivery, image, banner_image, logo_image, address, city, phone, is_featured, is_popular, is_best_rated) VALUES
(1, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'royal-biryani', 'Royal Biryani Kitchen', 'Biryani,Mughlai,North Indian', 'Experience the rich taste of authentic wood-fire cooked Hyderabadi and Lucknowi Biryanis made with premium long-grain basmati rice and aromatic spices.', 4.8, 1240, '20-30 min', 25, 1.5, 0.00, 1, 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=800&q=80', 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=800&q=80', 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=800&q=80', '12, Link Road, Andheri West, Mumbai', 'Mumbai', '+91 99999 11111', 0, 1, 0),
(2, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'spice-symphony', 'Spice Symphony', 'North Indian,Curry,Thali', 'A beautiful symphony of traditional Indian gravies, perfectly baked tandoori breads, and premium Maharaja thalis.', 4.7, 850, '30-40 min', 35, 2.2, 30.00, 0, 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&q=80', 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&q=80', 'https://images.unsplash.com/photo-1604152135912-04a022e23696?w=800&q=80', 'G-4, High Street Mall, Powai, Mumbai', 'Mumbai', '+91 99999 22222', 1, 0, 0),
(3, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'dakshin-delight', 'Dakshin Delight', 'South Indian,Dosa,Breakfast', 'Serving piping hot, paper-thin crispy dosas, fluffy idlis, and traditional filter coffee with freshly ground chutneys.', 4.6, 1420, '15-25 min', 20, 0.8, 20.00, 0, 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?w=800&q=80', 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?w=800&q=80', 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?w=800&q=80', 'Shop 3, Station Road, Matunga, Mumbai', 'Mumbai', '+91 99999 33333', 0, 1, 0),
(4, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'pizza-express', 'The Pizza Express', 'Pizza,Italian,Fast Food', 'Gourmet hand-tossed wood-fired sourdough pizzas with fresh local mozzarella, organic farm-fresh toppings, and house-made sauces.', 4.5, 980, '25-35 min', 30, 2.5, 0.00, 1, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&q=80', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&q=80', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&q=80', 'Plot 56, Carter Road, Bandra West, Mumbai', 'Mumbai', '+91 99999 44444', 0, 0, 0),
(5, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'burger-co', 'Burger & Co', 'Burgers,Fast Food,American', 'Juicy custom-crafted chicken and vegetable burger patties, loaded with cheese, special sauce, and served on toasted brioche.', 4.4, 710, '15-25 min', 20, 1.1, 15.00, 0, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80', '18, Lokhandwala Complex, Andheri West, Mumbai', 'Mumbai', '+91 99999 55555', 0, 0, 0),
(6, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'wok-roll', 'Wok & Roll', 'Chinese,Noodles,Street Food', 'Authentic Indo-Chinese street style woks, Hakka noodles, crispy manchurian, and steamed momos prepared fresh.', 4.3, 530, '20-30 min', 25, 1.9, 25.00, 0, 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=800&q=80', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=800&q=80', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=800&q=80', 'Block C, Phoenix Marketcity, Kurla, Mumbai', 'Mumbai', '+91 99999 66666', 0, 0, 0),
(7, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'sweet-retreat', 'Sweet Retreat Cafe', 'Desserts,Cakes,Ice Cream', 'Indulge in artisanal chocolate cakes, freshly churned premium ice creams, and melt-in-your-mouth pastries.', 4.9, 1600, '10-20 min', 15, 0.5, 0.00, 1, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=800&q=80', 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=800&q=80', 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=800&q=80', 'Shop 1, Hill Road, Bandra West, Mumbai', 'Mumbai', '+91 99999 77777', 0, 0, 1),
(8, (SELECT id FROM users WHERE email='mario@zesto.com' LIMIT 1), 'salad-story', 'The Salad Story', 'Healthy,Salads,Drinks', 'Freshly tossed organic salads, high-protein grain bowls, and sugar-free cold-pressed juices for the fitness enthusiast.', 4.5, 420, '20-30 min', 25, 1.7, 20.00, 0, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&q=80', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&q=80', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=800&q=80', '15, BKC Avenue, Bandra Kurla Complex, Mumbai', 'Mumbai', '+91 99999 88888', 0, 0, 0);

-- Seed menu items for Royal Biryani Kitchen (restaurant_id = 1)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(1, 3, 'Veg Dum Biryani', 'Authentic slow-cooked seasonal vegetables and basmati rice infused with saffron, cardamom, and fresh mint.', 249.00, 1, 1, 0, 0),
(1, 3, 'Special Chicken Biryani', 'Fragrant basmati rice layered with juicy, marinated chicken, caramelised onions, aromatic spices, cooked in handi.', 329.00, 0, 1, 1, 0),
(1, 3, 'Mutton Dum Biryani', 'Slow-cooked tender baby goat meat layered with long-grain basmati rice and signature spice blend.', 449.00, 0, 0, 0, 1),
(1, 3, 'Paneer Tikka Biryani', 'Rich tandoori paneer cubes layered with aromatic spices and saffron rice cooked on dum.', 289.00, 1, 0, 0, 0),
(1, 9, 'Classic Sweet Lassi', 'Chilled creamy yogurt beverage blended with sugar, rose water, and topped with dry fruits.', 89.00, 1, 1, 0, 0);

-- Seed menu items for Spice Symphony (restaurant_id = 2)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(2, 5, 'Paneer Butter Masala', 'Fresh cottage cheese cubes cooked in a rich, creamy, mild sweet tomato and butter gravy.', 279.00, 1, 1, 0, 0),
(2, 5, 'Butter Chicken', 'Tender tandoori chicken cooked in a rich, buttery, velvety tomato gravy with a touch of kasuri methi.', 349.00, 0, 1, 1, 0),
(2, 5, 'Dal Makhani', 'Black lentils slow cooked overnight with butter and cream, finished with fresh cream.', 229.00, 1, 0, 0, 0),
(2, 5, 'Garlic Naan', 'Tandoor-baked leavened flatbread brushed with melted butter and fresh minced garlic.', 59.00, 1, 0, 0, 0),
(2, 5, 'Maharaja Veg Thali', 'A royal platter of Dal Makhani, Paneer Butter Masala, Mix Veg, Rice, 2 Butter Rotis, Raita, and Gulab Jamun.', 399.00, 1, 0, 0, 1);

-- Seed menu items for Dakshin Delight (restaurant_id = 3)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(3, 4, 'Mysore Masala Dosa', 'Thin crispy rice and lentil crepe smeared with spicy red chutney, stuffed with spiced mashed potato.', 149.00, 1, 1, 0, 0),
(3, 4, 'Steamed Idli (2 Pcs)', 'Super fluffy, steamed rice-lentil cakes served with hot sambar and fresh coconut chutney.', 79.00, 1, 0, 0, 0),
(3, 4, 'Medu Vada (2 Pcs)', 'Crispy, deep-fried lentil donuts served with piping hot sambar and tomato chutney.', 99.00, 1, 0, 0, 0),
(3, 4, 'Rava Onion Dosa', 'Lacy, crispy crepe made with semolina, onions, green chillies, served with chutneys.', 169.00, 1, 0, 0, 1),
(3, 9, 'Traditional Filter Coffee', 'Freshly brewed aromatic chicory-blend South Indian filter coffee frothed with hot milk.', 59.00, 1, 1, 0, 0);

-- Seed menu items for The Pizza Express (restaurant_id = 4)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(4, 1, 'Margherita Pizza', '10" Sourdough base topped with rich San Marzano tomato sauce, fresh mozzarella, extra virgin olive oil, and fresh basil.', 299.00, 1, 1, 0, 0),
(4, 1, 'Chicken Tikka Pizza', 'Spiced tandoori chicken chunks, red onions, capsicum, and fresh mozzarella on a classic marinara base.', 399.00, 0, 1, 1, 0),
(4, 1, 'Garden Feast Pizza', 'Loaded with olives, bell peppers, corn, red onions, mushrooms, and fresh mozzarella cheese.', 349.00, 1, 0, 0, 1),
(4, 1, 'Garlic Breadsticks', 'Warm, freshly baked breadsticks brushed with herb garlic butter, served with cheesy dip.', 129.00, 1, 0, 0, 0);

-- Seed menu items for Burger & Co (restaurant_id = 5)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(5, 2, 'Crispy Veg Burger', 'Crispy mixed vegetable patty, topped with fresh lettuce, sliced tomatoes, onions, and house mayonnaise.', 129.00, 1, 1, 0, 0),
(5, 2, 'Maharaja Chicken Burger', 'Juicy grilled chicken breast patty, melted cheddar cheese, house-made spicy burger sauce, and crisp lettuce.', 189.00, 0, 1, 1, 0),
(5, 2, 'Peri Peri Fries', 'Crispy golden French fries tossed in a spicy, tangy peri-peri seasoning blend.', 99.00, 1, 0, 0, 0),
(5, 2, 'Double Cheese Crunch Burger', 'Double crispy fried vegetable patties layered with double cheddar slices, jalapenos, and spicy mayo.', 249.00, 1, 0, 0, 1);

-- Seed menu items for Wok & Roll (restaurant_id = 6)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(6, 6, 'Veg Hakka Noodles', 'Wok-tossed stir-fried wheat noodles with crunchy colorful bell peppers, cabbage, spring onions, and light soy.', 179.00, 1, 1, 0, 0),
(6, 6, 'Chicken Manchurian Dry', 'Crispy batter-fried chicken chunks tossed in a sweet, sour, spicy, and tangy soy-based sauce.', 219.00, 0, 0, 0, 1),
(6, 6, 'Steamed Veg Momos (6 Pcs)', 'Hand-folded steamed dumplings packed with finely minced seasoned vegetables, served with spicy red chilli chutney.', 119.00, 1, 0, 0, 0),
(6, 6, 'Chilli Paneer Dry', 'Cottage cheese cubes wok-tossed with fresh capsicum, red onions, garlic, green chillies, and savory dark soy.', 199.00, 1, 1, 0, 0);

-- Seed menu items for Sweet Retreat Cafe (restaurant_id = 7)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(7, 7, 'Death by Chocolate Cake', 'Rich, moist double-layer Belgian chocolate sponge filled and frosted with velvety dark chocolate ganache.', 149.00, 1, 1, 0, 0),
(7, 7, 'Premium Vanilla Bean Scoop', 'Double scoop of slow-churned pure organic vanilla bean gelato with real vanilla specks.', 89.00, 1, 0, 0, 0),
(7, 7, 'Hot Gulab Jamun (2 Pcs)', 'Traditional warm soft milk-solid dumplings soaked in aromatic cardamom and saffron sugar syrup.', 69.00, 1, 0, 1, 0),
(7, 7, 'Chocolate Fudge Waffle', 'Crispy golden waffle topped with melted milk chocolate, dark chocolate chips, and fresh cream.', 179.00, 1, 0, 0, 1);

-- Seed menu items for The Salad Story (restaurant_id = 8)
INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_popular, is_special, is_trending) VALUES
(8, 8, 'Avocado Quinoa Salad Bowl', 'Organic quinoa, fresh avocado, cherry tomatoes, cucumbers, mixed greens, chickpeas, with extra virgin olive oil vinaigrette.', 289.00, 1, 1, 0, 0),
(8, 8, 'High-Protein Chicken Salad', 'Grilled chicken breast strips, hard-boiled eggs, sweet corn, mixed crisp lettuce greens, and a light herb-mustard dressing.', 319.00, 0, 0, 0, 1),
(8, 9, 'Cold-Pressed Green Juice', 'Freshly extracted sugar-free nutrient-rich juice of celery, cucumber, green apple, spinach, ginger, and mint.', 149.00, 1, 0, 0, 0),
(8, 9, 'Berry Blast Smoothie', 'A wholesome blend of frozen strawberries, blueberries, raspberries, banana, Greek yogurt, and raw honey.', 189.00, 1, 1, 0, 0);

-- Seed delivery partner profile for Marcus
INSERT IGNORE INTO delivery_partners (user_id, vehicle_type, total_deliveries, rating)
SELECT id, 'bike', 2000, 4.9 FROM users WHERE email='marcus@zesto.com' LIMIT 1;


-- ─────────────────────────────────────────────────────────────
-- 9. ADDRESSES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS addresses (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED DEFAULT NULL,
    guest_session_id    VARCHAR(150) DEFAULT NULL,
    full_name           VARCHAR(150) NOT NULL,
    mobile_number       VARCHAR(20) NOT NULL,
    flat_number         VARCHAR(50) DEFAULT NULL,
    building_name       VARCHAR(150) DEFAULT NULL,
    street              VARCHAR(150) NOT NULL,
    area                VARCHAR(150) NOT NULL,
    landmark            VARCHAR(150) DEFAULT NULL,
    city                VARCHAR(100) NOT NULL,
    state               VARCHAR(100) NOT NULL,
    pincode             VARCHAR(20) NOT NULL,
    address_type        ENUM('home', 'work', 'other') NOT NULL DEFAULT 'home',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- 10. COUPONS & OFFERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percentage DECIMAL(5,2) NOT NULL,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    min_order_value DECIMAL(10,2) DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS offers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    code VARCHAR(50) DEFAULT NULL,
    image VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed coupons & offers
INSERT IGNORE INTO coupons (code, discount_percentage, max_discount, min_order_value) VALUES
('WELCOME50', 50.00, 150.00, 100.00),
('ZESTODELIGHT', 20.00, 100.00, 200.00),
('FREEFEAST', 100.00, 100.00, 500.00);

INSERT IGNORE INTO offers (title, description, code, image) VALUES
('50% Off First 3 Orders', 'Get 50% discount up to ₹150 on your first 3 orders on Zesto.', 'WELCOME50', 'https://lh3.googleusercontent.com/aida-public/AB6AXuD55EC6JU2Ccf8bZ_lQOIPcFj3kSuFakZ7Wxt-W0OpE6gHvNfyT49MPBvMPCJY1c2BABZdhUorBcsCBsRdjIi1hV8MN-qBhNvVfkFOGkwJwTBRXvQ5-xFFM-_YeWvKSO-ulKag_cSMFrCnyQzpDYMhwhkWuDXcHjGmocSq_VjOGID-zDA7slW4ITIXHdCFsr9Wqgp-iceaeLkg2slVfKC4jUW5I10I1LweWGGOkKZfWvhPdb4otIKn2F-2Gq6JwTISCzYme5748y0M'),
('Weekend Zesto Feast', 'Save 20% on premium dining experiences from select top kitchens.', 'ZESTODELIGHT', NULL);

