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

-- Seed admin user (password: Admin@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin', 'admin@zesto.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Seed demo customer (password: Customer@123)
INSERT IGNORE INTO users (name, email, password, phone, role) VALUES
('Alex Johnson', 'alex@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1 555-0101', 'customer');

-- Seed restaurant owner (password: Owner@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Mario Rossi', 'mario@zesto.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'restaurant_owner');

-- Seed delivery partner (password: Delivery@123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Marcus Rodriguez', 'marcus@zesto.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery_partner');

-- Seed categories
INSERT IGNORE INTO categories (name, image, display_order) VALUES
('Pizza',    'https://lh3.googleusercontent.com/aida-public/AB6AXuCmf3zFUlKXutpRxTK_3-QlgR36ZowZKDpJYa3j53CsKM4mFjYBvYqVI3mCHtXQd4DCJrhhyHtF7ekcIeRzV8g7ftXqOtoGxmmFmyxh7qpfOb_fRz49Uv6z_lOljCv3rk-sJ5kkJJqg5ZzBrswKdaZSZ7sDr5ySQLplEmdldpyO8ha97lMrwKkPvVUvquOlQaEDns69EYiPOtGQRjmG0xa5Ee1F8Az3z7L7PDXBdDxzEE7elTrPV6xZNAMWiXqobulan0fWFnFqeo8', 1),
('Burgers',  'https://lh3.googleusercontent.com/aida-public/AB6AXuBZLbHo94g2948yCQi_Q1dVUSPm7BgZNWKJWBJwlPkeAxvdQXlETDOg88T30AcJwkVKeiDN3TZ3h4Uzx-ktYgh2MxBjNSgQmOdj3cR8mlX0VcaeE9AA-ynZ-cXRNbEOjFU47cUGFE9pWTrzGgqg6liFOHMYjEWhj-CyDCSeVvyO5282aXh30ZUK6uEhmx48fz-0Os880RaqVw-iUMvfgiHqI0oGi_UikGPKsXXv80RBqP2yhQQchY8YwAnkKE6NJTZJYRarOE_5lng', 2),
('Sushi',    'https://lh3.googleusercontent.com/aida-public/AB6AXuCJT_Jg8SYMH4uRHjwaZVnW0HQImHM4omGuUTsqkoJeLFz7iBwNbtU3vKFejxg-h-Hu1REdHRuyVrljsjZAlqVYgWmyv28vUTQKUskpMVOHjcKB3EdIKLINyUf2Od5EN2LVX1Evuq3y-BNW1KAEkoWDVgNDofO4jgPhcGA-XQ5kGBFBhWMkn_5NaI5Fx8S3cZl1aDoglTXRloby2AdxN3nNZ3AnGCRcqsN-Srd6ltOVPLjUjFXoca0ZpOItktJw9uSfo2LjUFIooGY', 3),
('Pasta',    'https://lh3.googleusercontent.com/aida-public/AB6AXuCm2X3BOIdAAWgicslb39AKBnNVOBx8O_auQuzctdOBmS_PVn8Id7niaQjDwuIUjYUhwt0uLmC6IMHF9hbsJnFCxzpauJeQ9vbqxvRqi-lE6XuFg0lYH9aHRhOXzYIJ6pWUu0KtxiBaO_YQeL_w_y9WmKjEYX2bqQz2_dwjT-I-kZtxiRYKNAcillhYrTq2yRZsR-D8_Zl21qujBEaX6eYITTCRKBEZUEMWkfytoDu2Z1OO4dbcWCOtbHcA7o3ftlN_zTgqX7lkVT4', 4),
('Desserts', 'https://lh3.googleusercontent.com/aida-public/AB6AXuCjb4QnX7QdK2njUj7cm5zMMt_z6M7TgjQXjKGvVjlyj_lMulWXhFMzGhKHLdC8oi-vSZBb8OD5TTsaBl1ZxFi1XeMrf6BEgaMfoLzxgc9LTL7Ushufv42J9xr3k_x69Uol5bIvQyNJLHfqlOJpr4G__zIskHJ8u-5fAU-G1BcFDtWqKX8LhEmQzqWdkXenZ9VLOfT6q4svJR4O0PCuOVU7ri04mYwJSJ8rzQ4oIWwMfA7j67bzlITK0A15bO7bAYlIJ-nO1_7sM3s', 5),
('Healthy',  'https://lh3.googleusercontent.com/aida-public/AB6AXuCJV9NVHf_4y_cCjz9eEQYvvDhEzj3W1T0v67YCrdCZtJOXaIK3_ushKGml16cpsP0zMAzG9bEtz30q7aS0Om12Q8UhUSDtE-oO-1swechcZbO5cEdShsA_9B_hr6zBqnP_nzscmkrKNq3tiZLvYsFuuzTl0M3teeBkd9T2BIXAbbs3AgvEGQBdEcrih6CnfcaIFTvg048HOEHdUCs2x6tzuASCgKXeFWp-IRIjnKRZ00LvVOAGxeuWVg-_uIRjOnKgqFas11TNl8U', 6),
('Drinks',   'https://lh3.googleusercontent.com/aida-public/AB6AXuDgyfyP7rZ5dmAORInBqrp6VhdNNjTUc3kJb-uGys1DXHWggV9aJfUPMwEIDyBuchzQlSz2_H-GhgK4CPrHMHDdT9XcXzk0tjfAafyZhNbgMUYIhKMJFY_T6Lkiyv7bLzAcf_LH9yFedLNmQWqOU9FEplVgB2QNXItgaSM0PngufbViGwnLmgTG6zXQ_giH7ILTd1-Wvircw50sDHB3PrwG3ug70sfg4ydbThZMLuJ8BQqJ5NOQ4kOyZ6ntA2f5zDfwXWqenvjvpPQ', 7);

-- Seed restaurants
INSERT IGNORE INTO restaurants (slug, name, tags, rating, delivery_time, delivery_time_value, distance, delivery_fee, is_free_delivery, image) VALUES
('steakhouse',     'The Steakhouse Grill',  'Premium Steaks,American,Fine Dining',      4.8, '25-35 min', 30, 2.4, 0.00, 1, 'https://lh3.googleusercontent.com/aida-public/AB6AXuACPe1OwcnqiSYz6mGkYPwpTwUZkoQT8Jeq336MHTLd5-szfhdGafbxKuJ3QVMBjxqcxm4UwTDipbBKsEECFSl_VHIJI58oJjjfYhQRcILi8-eedqeW9Mmlq_MJCKbX6yX6excKavJXTN1YruIGDT445j8SmCA9w4wNuJUqWrKgCGPpn5cc-E6Ph19OOcwM0Lu_vntB6rnd88Rr2jXfoBPCYqOX-gehGl-S_UIFfvPKeRPs0iP4Kc_0ZbV9KJ8H6mFYWZPD6gO7v2U'),
('urban-bites',    'Urban Bites',           'Burgers,Fries,Fast Casual',                4.6, '15-25 min', 20, 1.1, 1.99, 0, 'https://lh3.googleusercontent.com/aida-public/AB6AXuA4jPmsOW8BKn873nl6iulEAGQL68SalnXdDBpixzUU5q4QjwpCkuvx4l2EkjUb1d2LjLDtBasc3MyvpIGeyRWdPTYI7SfIAE0SfBaMV1kC225DGbTGKp3sTGJmMVgty20fJ04Lj43xiUc2D4ODVrufxfTl_MBkqlwHAw_G3Ikiu5ac78s5VoZQLC_nrle09lwVrWFiVPLBYnPVsl4qcgLC6ztuNbnK_sCiX-wmaadjLRrrvI6BrdmvLb93XtoTZ3EQydhVnTgzaiI'),
('pasta-fresca',   'Pasta Fresca',          'Italian,Handmade Pasta,Vegetarian Friendly',4.9, '30-40 min', 35, 3.7, 2.99, 0, 'https://lh3.googleusercontent.com/aida-public/AB6AXuArgDIL9KLzq5ZsApELWgYS3y8HQs9wSVxxHf8FHKmyqQhnzEAa3rvvaUUNQa5AYYgYehaggp8vqVC6IlXc697ltid9krYc8SUQxTRWEOI1BhMvYDHSnQP8ifgFllVkfqAda8OdbUhS2drUJO4O6JLWXW0JRxP_A3D0Ux1twYAwdJjId72SupMxUtW7IbyggYamrivoWy093ntQyoknBK6sIWoezFNdTnxijcuVsYafPQILAyhxunA-LeW5bCFyLrMaGBb_rGMvU0Q'),
('miyabi-sushi',   'Miyabi Sushi',          'Japanese,Seafood,Authentic',               4.7, '40-50 min', 45, 4.2, 3.50, 0, 'https://lh3.googleusercontent.com/aida-public/AB6AXuCR50Ht2X1Mwfs1wwMxi8XFT5HsY1AvQuN5ET_BVWNR5pbrkeDcKDHv-kOPsdRu4JJIWq2itatHkpGMBN2Krppu1yvVN551DQF8ag8-Jz1y65o9OwxhibwTBjWxKoMbEG0Go2t7TsMF_JMb2q9p8TVPfjCJxnbLsLhulhVW87uYs1moahSKWywnZR9dGA8GNxV2cavxL34efzr50YQ2ef9c2z5K8NddwlLkCkLp4wsQmkfNOL1CiQUKURKUFnwlvA21rd9lYEWv0eI'),
('green-leaf',     'Green Leaf Kitchen',    'Vegetarian,Salads,Organic',               4.5, '20-30 min', 25, 1.8, 1.50, 0, 'https://lh3.googleusercontent.com/aida-public/AB6AXuAFaOo2_2F_4fgzcANsaBFRuz9Dzo0agRgVjOugjYR5C1rm218seye-wgP3uVGE_DijvXoO9vqkGbmp88DpthaeK_dLnd7uIpXHyhpyAlpXhHCEYoFJWdl-ZC87rCp_4DBQKdfY9pZliM8dKvHs5kL0XWPwIZqfXs73M2pWKUHbAQxFA30Emo5HcCyEAZ504GxqAYMzVfpF9J0vUbSMeJYZGfGj2jUZOVuxp_B0B7drIpa0nyFkgY2ZZ3nxmGdAzDaifOJYqHBRVsM'),
('taco-fiesta',    'Taco Fiesta',           'Mexican,Street Food,Authentic',            4.4, '15-20 min', 17, 0.5, 0.00, 1, 'https://lh3.googleusercontent.com/aida-public/AB6AXuAhhu5yrzS4UxTPK0miFmvCWGhJvBy4pHBZ_py8DdnHumZiG1obv_6r5-1EbHS88k3Sxd_4LXvb0PciYmKNteitCJqJSpIEX_u5qXTbrUfBO1nj3kn10c1_zWRREcFlYOEGt-id-1ZfZHBM0WGH5zhYjyx0_u5Rkd1SW62DHbu9geq0bRRLVdRE0dsjkem-tjZmKwELi9p2Z5FAcd_GnoBr68upLYtppoQBG2zryR2H2swK-HGSL6CID-Tw2-mb2Jtr5Ka5QHNEk7o'),
('mizu-sushi',     'Mizu Sushi Bar',        'Japanese,Sushi,Gourmet',                  4.8, '20-30 min', 25, 1.2, 0.00, 1, 'https://lh3.googleusercontent.com/aida-public/AB6AXuDP-q7jh4-MLVz5tkEqfW-bMMMdrFH7POEz1_lOTvowl1-_ZTPWFOwzLsf-WKndJIgiD57Bz_ifUfaHdR80pa9JYnPQaAcKtJlpb_NVPCpIY6N_FLbHvMgtUM1oU21DGcP8DNoogiq9rYVj-VhfDYWiU_nMQ2-6iLL5SAwsvyY0YQn4FG2OZxDlLIsVH98cuPZ6e64QnsIb7G-1nYew5XsIdfqnZnjkQ2To42RWEnzxv3kclt0TEw0X13MrUIlVbjrQSqyCisIlNoQ'),
('breakfast-club', 'Breakfast Club',        'American,Pancakes,Breakfast',             4.5, '15-25 min', 20, 2.1, 0.99, 0, 'https://lh3.googleusercontent.com/aida-public/AB6AXuAjUk8C5lbXk0zVT7PJw-BDvhO3x7q3VLo-2EuJyFhLX1xLo1LOajRCGNw44OdAC2RqAOLRJV1X3uifD74Qkc_Jmc3Ec1QsNvzSzsj6URQliEpwX5c8-0XnqgHZA9XWFvX_e2gWACiPwii3PPBQrqYamJmd1Fpc87QLzLx-_oiDxi0tJYzzcgpxc_5pNzknxG87ObvwYNWqUPsCYlJpoDZDC35d251DDTvIDuMJgkbFP5hmoDWuDwG4YELS0_BrKtMHIiaBQ9zQyCw');

-- Seed menu items for The Steakhouse Grill
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='steakhouse'), 'Signature Sirloin Steak', 'Prime dry-aged 12oz sirloin grilled to perfection over hickory coal, served with garlic herbed butter and charred asparagus.', 34.00, '["Rare","Medium Rare","Medium","Well Done"]'),
((SELECT id FROM restaurants WHERE slug='steakhouse'), 'Bone-In Cowboy Ribeye', 'Tender, richly marbled 18oz ribeye, aged for 35 days, served with custom peppercorn cream sauce.', 45.00, '["Medium Rare","Medium","Medium Well"]'),
((SELECT id FROM restaurants WHERE slug='steakhouse'), 'Double Truffle Burger', 'Double wagyu beef patties, melted black truffle white cheddar, crisp arugula, and garlic aioli on a toasted brioche bun.', 18.50, '["Extra cheese","No onions","Gluten-free bun"]'),
((SELECT id FROM restaurants WHERE slug='steakhouse'), 'Classic Caesar with Grilled Steak', 'Crisp romaine lettuce, shaved parmigiano-reggiano, sourdough croutons, tossed in garlic anchovy emulsion, topped with prime sliced sirloin.', 19.00, NULL),
((SELECT id FROM restaurants WHERE slug='steakhouse'), 'Lobster Macaroni & Cheese', 'Campanelle pasta baked in creamy five-cheese mornay sauce with Atlantic lobster tail and herbed panko crust.', 22.00, NULL);

-- Seed menu items for Urban Bites
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='urban-bites'), 'Urban Double Cheeseburger', 'Two smashed grass-fed beef patties, double American cheese, caramelized onions, house pickles, and secret Smash Sauce on potato roll.', 13.99, '["Extra cheese","Add bacon","No onions","Lettuce wrap"]'),
((SELECT id FROM restaurants WHERE slug='urban-bites'), 'Hot Crispy Chicken Sandwich', 'Buttermilk-brined double fried chicken thigh tossed in Nashville hot oil, sweet tangy pickle chips, and creamy slaw on brioche.', 12.50, '["Mild","Medium","Xtra Hot"]'),
((SELECT id FROM restaurants WHERE slug='urban-bites'), 'Parmesan Truffle Fries', 'Crisp hand-cut Russet potatoes, white truffle oil, grated pecorino, and fresh parsley, with house garlic dipping sauce.', 6.99, NULL),
((SELECT id FROM restaurants WHERE slug='urban-bites'), 'Gourmet Salted Caramel Shake', 'Slow-churned premium vanilla bean gelato blended with homemade sea salted caramel sauce and fresh farm cream.', 5.50, NULL);

-- Seed menu items for Pasta Fresca
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='pasta-fresca'), 'Handmade Fettuccine Alfredo', 'Fettuccine kneaded from organic semolina and pasture egg yolk, cooked al dente in a rich emulsion of authentic parmigiano-reggiano and sweet butter.', 18.99, '["Add grilled chicken","Add prawns","Gluten-free noodles"]'),
((SELECT id FROM restaurants WHERE slug='pasta-fresca'), 'Grandma''s Bolognese Lasagna', 'Fresh pasta sheets layered with slow-simmered 8-hour beef & veal ragù, creamy bechamel, and melted whole-milk mozzarella.', 21.00, NULL),
((SELECT id FROM restaurants WHERE slug='pasta-fresca'), 'House Signature Tiramisu', 'Light ladyfingers soaked in robust espresso and sweet dark rum, layered with whipped organic mascarpone custard, dusted with direct-trade cocoa.', 8.50, NULL),
((SELECT id FROM restaurants WHERE slug='pasta-fresca'), 'Creamy Heirloom Burrata Salad', 'Pugliese burrata surrounded by heirloom cherry tomatoes, wild arugula, extra virgin olive oil, aged balsamic glaze, and fresh pine nut pesto.', 14.50, NULL);

-- Seed menu items for Miyabi Sushi
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='miyabi-sushi'), 'Premium Sushi Omakase Set', 'Chef selection of 8 nigiri sushi pieces (tuna belly, king salmon, red snapper, etc.) and one spicy tuna handroll.', 38.00, NULL),
((SELECT id FROM restaurants WHERE slug='miyabi-sushi'), 'Imperial Rainbow Roll', 'Lump crab meat and cucumber roll inside, topped with fresh avocado, yellowtail tuna, king salmon, and jumbo tiger prawns.', 19.50, NULL),
((SELECT id FROM restaurants WHERE slug='miyabi-sushi'), 'Garlic Truffle Edamame', 'Steamed young soy pods tossed in white truffle oil, sea salt, toasted garlic bits, and organic chili oil.', 6.50, NULL);

-- Seed menu items for Green Leaf Kitchen
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='green-leaf'), 'Harvest Quinoa & Avocado Bowl', 'Organic warm quinoa, avocado halves with hemp seeds, shredded sweet potatoes, purple cabbage, and lemon-tahini drizzle.', 15.50, NULL),
((SELECT id FROM restaurants WHERE slug='green-leaf'), 'Ultimate Greens & Berries Salad', 'Organic kale, sweet spinach, fresh blackberries, wild raspberries, roasted walnuts, crumbled goat cheese, in red-wine poppyseed vinaigrette.', 14.00, '["No Goat Cheese","Vegan (No Cheese)","Add roast tofu"]'),
((SELECT id FROM restaurants WHERE slug='green-leaf'), 'Immunity Green Cold-Press Juice', 'Cold-pressed cucumber, green apple, organic celery, curly kale, baby spinach, fresh lemon, ginger root, and mint sprigs.', 7.99, NULL);

-- Seed menu items for Taco Fiesta
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='taco-fiesta'), 'Artisanal Street Taco Trio', 'Three hand-pounded corn tortillas loaded with your choice of Birria beef, Carnitas pork, or Chipotle grilled cauliflower, topped with onions, cilantro, and salsa verde.', 11.50, '["Birria Beef","Carnitas Pork","Chipotle Cauliflower"]'),
((SELECT id FROM restaurants WHERE slug='taco-fiesta'), 'Oaxacan Melted Cheese Quesadilla', 'Huge flour tortilla crisp-melted with rich strings of artisan Oaxacan cheese, green chilies, and scallions, served with freshly mashed guacamole.', 12.00, NULL),
((SELECT id FROM restaurants WHERE slug='taco-fiesta'), 'Mexican Churros with Dulce de Leche', 'Crisp golden brown pastry fingers rolled in sugar-cinnamon powder, paired with liquid Mexican caramel dip.', 6.00, NULL);

-- Seed menu items for Mizu Sushi Bar
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='mizu-sushi'), 'Premium Salmon Avocado Roll', 'Fresh organic salmon slices, Hass avocado, cream cheese, seasoned rice wrap, sprinkled with black and white sesame.', 14.50, NULL),
((SELECT id FROM restaurants WHERE slug='mizu-sushi'), 'Golden Crispy Shrimp Tempura', 'Four colossal black tier prawns coated in light crispy panko batter, dunked with grated daikon radish and sweet tempura dip.', 16.00, NULL);

-- Seed menu items for Breakfast Club
INSERT IGNORE INTO menu_items (restaurant_id, name, description, price, customization_options) VALUES
((SELECT id FROM restaurants WHERE slug='breakfast-club'), 'Gourmet Wildberry Pancakes', 'Stack of three fluffy golden brown buttermilk pancakes with fresh dark blackberries, raspberries, blueberries, and Canadian grade-A maple syrup.', 12.00, '["Extra syrup","Add whipped cream","Gluten-free batter"]'),
((SELECT id FROM restaurants WHERE slug='breakfast-club'), 'All-American Morning Feast', 'Two sunnyside eggs cooked in butter, three crisp strips of thick maplewood bacon, home-fries, and choice of fresh sourdough toast.', 14.50, NULL);

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

