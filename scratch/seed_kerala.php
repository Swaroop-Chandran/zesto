<?php
/**
 * Zesto Nights — Database Seeder for Kerala/Kochi Content
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = db();
    echo "Connected to database successfully.\n";

    // 1. Disable legacy categories (1-9) and create/enable Kerala categories (10-14)
    $db->exec("UPDATE categories SET is_active = 0 WHERE id BETWEEN 1 AND 9");
    
    // Insert or update Kerala categories
    $categories = [
        [10, 'Porotta', 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&q=80&w=150', 1],
        [11, 'Beef Roast', 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=150', 2],
        [12, 'Kappa', 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&q=80&w=150', 3],
        [13, 'Black Tea', 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&q=80&w=150', 4],
        [14, 'Thattukada Snacks', 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&q=80&w=150', 5],
    ];

    foreach ($categories as $cat) {
        $stmt = $db->prepare("
            INSERT INTO categories (id, name, image, display_order, is_active)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE name = VALUES(name), image = VALUES(image), display_order = VALUES(display_order), is_active = 1
        ");
        $stmt->execute($cat);
    }
    echo "Seeded categories successfully.\n";

    // 2. Clean up existing Kochi restaurants/menu items
    $db->exec("DELETE FROM menu_items WHERE restaurant_id IN (SELECT id FROM restaurants WHERE city = 'Kochi')");
    $db->exec("DELETE FROM restaurants WHERE city = 'Kochi'");
    echo "Cleaned up old Kochi data.\n";

    // Get owner ID
    $ownerStmt = $db->query("SELECT id FROM users WHERE role = 'restaurant_owner' LIMIT 1");
    $ownerId = $ownerStmt->fetchColumn();
    if (!$ownerId) {
        $ownerId = 1; // Fallback
    }

    // 3. Seed Kochi restaurants
    $restaurants = [
        [
            'owner_id' => $ownerId,
            'slug' => 'manis-thattukada',
            'name' => "Mani's Thattukada",
            'tags' => 'Chicken Fry, Porotta & Beef Roast',
            'description' => 'Authentic Kochi roadside Thattukada serving piping hot Kerala delicacies.',
            'rating' => 4.8,
            'rating_count' => 312,
            'delivery_time' => '25 min',
            'delivery_time_value' => 25,
            'distance' => 1.20,
            'delivery_fee' => 0.00,
            'is_free_delivery' => 1,
            'discount' => 'FREE DELIVERY',
            'image' => 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&q=80&w=400',
            'banner_image' => 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&q=80&w=600',
            'logo_image' => '',
            'address' => 'Palarivattom Bypass, Kochi',
            'city' => 'Kochi',
            'phone' => '+91 99999 10001',
            'operating_hours' => '6:00 PM - 4:00 AM',
            'is_featured' => 1,
            'is_popular' => 1,
            'is_best_rated' => 1
        ],
        [
            'owner_id' => $ownerId,
            'slug' => 'night-rider-eats',
            'name' => 'Night Rider Eats',
            'tags' => 'Kappa Biriyani & Gourmet Black Tea',
            'description' => 'Late night cravings sorted with Kappa Biriyani and hot Sulaimani.',
            'rating' => 4.8,
            'rating_count' => 184,
            'delivery_time' => '30 min',
            'delivery_time_value' => 30,
            'distance' => 2.50,
            'delivery_fee' => 20.00,
            'is_free_delivery' => 0,
            'discount' => '20% OFF CODES',
            'image' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&q=80&w=400',
            'banner_image' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&q=80&w=600',
            'logo_image' => '',
            'address' => 'Edappally Toll, Kochi',
            'city' => 'Kochi',
            'phone' => '+91 99999 10002',
            'operating_hours' => '6:00 PM - 4:00 AM',
            'is_featured' => 1,
            'is_popular' => 1,
            'is_best_rated' => 1
        ],
        [
            'owner_id' => $ownerId,
            'slug' => 'tawa-night-express',
            'name' => 'Tawa Night Express',
            'tags' => 'Uggi Beef & Spicy Boti',
            'description' => 'Spicy boti fry and crispy porottas straight from the hot iron tawa.',
            'rating' => 4.6,
            'rating_count' => 256,
            'delivery_time' => '18 min',
            'delivery_time_value' => 18,
            'distance' => 3.40,
            'delivery_fee' => 30.00,
            'is_free_delivery' => 0,
            'discount' => 'ITEMS AT ₹129',
            'image' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&q=80&w=400',
            'banner_image' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&q=80&w=600',
            'logo_image' => '',
            'address' => 'MG Road, Kochi',
            'city' => 'Kochi',
            'phone' => '+91 99999 10003',
            'operating_hours' => '6:00 PM - 4:00 AM',
            'is_featured' => 1,
            'is_popular' => 0,
            'is_best_rated' => 0
        ],
        [
            'owner_id' => $ownerId,
            'slug' => 'worlds-4am-bites',
            'name' => "World's 4 AM Bites",
            'tags' => 'Porotta Combo & Kerala Night Snacks',
            'description' => 'Your ultimate 4 AM spot for hot black tea and Kerala snacks.',
            'rating' => 4.7,
            'rating_count' => 420,
            'delivery_time' => '23 min',
            'delivery_time_value' => 23,
            'distance' => 1.90,
            'delivery_fee' => 0.00,
            'is_free_delivery' => 1,
            'discount' => 'FREE TEA COMBO',
            'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&q=80&w=400',
            'banner_image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&q=80&w=600',
            'logo_image' => '',
            'address' => 'Kakkanad Junction, Kochi',
            'city' => 'Kochi',
            'phone' => '+91 99999 10004',
            'operating_hours' => '6:00 PM - 4:00 AM',
            'is_featured' => 0,
            'is_popular' => 1,
            'is_best_rated' => 1
        ],
    ];

    $restaurantIds = [];
    foreach ($restaurants as $r) {
        $stmt = $db->prepare("
            INSERT INTO restaurants (
                owner_id, slug, name, tags, description, rating, rating_count, 
                delivery_time, delivery_time_value, distance, delivery_fee, 
                is_free_delivery, discount, image, banner_image, logo_image, 
                address, city, phone, operating_hours, is_featured, is_popular, is_best_rated
            ) VALUES (
                :owner_id, :slug, :name, :tags, :description, :rating, :rating_count, 
                :delivery_time, :delivery_time_value, :distance, :delivery_fee, 
                :is_free_delivery, :discount, :image, :banner_image, :logo_image, 
                :address, :city, :phone, :operating_hours, :is_featured, :is_popular, :is_best_rated
            )
        ");
        $stmt->execute($r);
        $restaurantIds[$r['slug']] = $db->lastInsertId();
    }
    echo "Seeded restaurants successfully.\n";

    // 4. Seed menu items for the restaurants
    $menuItems = [
        // Mani's Thattukada
        [
            'restaurant_slug' => 'manis-thattukada',
            'category_id' => 11, // Beef Roast
            'name' => 'Beef Roast & Porotta',
            'description' => 'Spicy slow-roasted shredded beef tossed with organic Kerala coconut slices, served with 2 crispy handmade Porottas.',
            'price' => 180.00,
            'image' => 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 0,
            'is_popular' => 1,
            'is_special' => 1,
            'is_trending' => 1
        ],
        [
            'restaurant_slug' => 'manis-thattukada',
            'category_id' => 10, // Porotta
            'name' => 'Thattukada Set',
            'description' => 'Authentic roadside combination: 3 soft fluffy Porottas and a flavorful spicy chicken or beef gravy.',
            'price' => 180.00,
            'image' => 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 0,
            'is_popular' => 1,
            'is_special' => 1,
            'is_trending' => 0
        ],
        [
            'restaurant_slug' => 'manis-thattukada',
            'category_id' => 11, // Beef Roast
            'name' => 'Crispy Flaky Porotta + Spicy Red Beef Fry',
            'description' => 'The legendary combination—slow-roasted dry beef fry tossed in Ernakulam-style Thattukada spices, served with smoking hot porotta layers. Perfect for late-night cravings.',
            'price' => 180.00,
            'image' => 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 0,
            'is_popular' => 0,
            'is_special' => 0,
            'is_trending' => 1 // Used as the trending combo section item!
        ],
        [
            'restaurant_slug' => 'manis-thattukada',
            'category_id' => 14, // Thattukada Snacks
            'name' => 'Thattukada Chicken Fry',
            'description' => 'Kochi-style deep-fried spicy chicken marinated in ginger, garlic, and crushed red chillies.',
            'price' => 120.00,
            'image' => 'https://images.unsplash.com/photo-1569058242253-92a9c755a0ec?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 0,
            'is_popular' => 1,
            'is_special' => 0,
            'is_trending' => 0
        ],
        [
            'restaurant_slug' => 'manis-thattukada',
            'category_id' => 13, // Black Tea
            'name' => 'Sulaimani Tea',
            'description' => 'Traditional spiced black tea brewed with cardamom, cloves, and a dash of lemon juice.',
            'price' => 20.00,
            'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 1,
            'is_popular' => 0,
            'is_special' => 0,
            'is_trending' => 0
        ],

        // Night Rider Eats
        [
            'restaurant_slug' => 'night-rider-eats',
            'category_id' => 12, // Kappa
            'name' => 'Kappa Biriyani (Beef)',
            'description' => 'Slow-cooked mashed tapioca (Kappa) layered with tender spiced beef chunks and premium dry coconut flakes.',
            'price' => 160.00,
            'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 0,
            'is_popular' => 1,
            'is_special' => 1,
            'is_trending' => 1
        ],
        [
            'restaurant_slug' => 'night-rider-eats',
            'category_id' => 12, // Kappa
            'name' => 'Kappa & Fish Curry',
            'description' => 'Boiled tapioca served with spicy red-hot authentic Kerala fish curry cooked in clay pot.',
            'price' => 150.00,
            'image' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 0,
            'is_popular' => 1,
            'is_special' => 0,
            'is_trending' => 0
        ],
        [
            'restaurant_slug' => 'night-rider-eats',
            'category_id' => 13, // Black Tea
            'name' => 'Kochi Black Tea',
            'description' => 'Strong, hot, sugar-brewed black tea to keep you awake.',
            'price' => 15.00,
            'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 1,
            'is_popular' => 0,
            'is_special' => 0,
            'is_trending' => 0
        ],

        // Tawa Night Express
        [
            'restaurant_slug' => 'tawa-night-express',
            'category_id' => 11, // Beef Roast
            'name' => 'Tawa Beef Fry',
            'description' => 'Thinly sliced beef tossed with green chillies, ginger, and shallow fried on iron tawa.',
            'price' => 130.00,
            'image' => 'https://images.unsplash.com/photo-1603360946369-dc9bb6258143?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 0,
            'is_popular' => 1,
            'is_special' => 0,
            'is_trending' => 0
        ],
        [
            'restaurant_slug' => 'tawa-night-express',
            'category_id' => 10, // Porotta
            'name' => 'Single Porotta',
            'description' => 'One flaky, multilayered Kerala parotta prepared fresh on order.',
            'price' => 15.00,
            'image' => 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 1,
            'is_popular' => 1,
            'is_special' => 0,
            'is_trending' => 0
        ],

        // World's 4 AM Bites
        [
            'restaurant_slug' => 'worlds-4am-bites',
            'category_id' => 14, // Thattukada Snacks
            'name' => 'Parippu Vada (2 Pcs)',
            'description' => 'Crunchy, deep-fried lentil fritters seasoned with ginger, curry leaves, and green chillies.',
            'price' => 40.00,
            'image' => 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 1,
            'is_popular' => 1,
            'is_special' => 0,
            'is_trending' => 0
        ],
        [
            'restaurant_slug' => 'worlds-4am-bites',
            'category_id' => 13, // Black Tea
            'name' => 'Kattan Chai',
            'description' => 'Classic hot cardamom black tea, perfect companion for crispy vadas.',
            'price' => 15.00,
            'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&q=80&w=400',
            'is_veg' => 1,
            'is_popular' => 0,
            'is_special' => 0,
            'is_trending' => 0
        ]
    ];

    foreach ($menuItems as $item) {
        $rSlug = $item['restaurant_slug'];
        $rId = $restaurantIds[$rSlug] ?? null;
        if (!$rId) continue;

        $stmt = $db->prepare("
            INSERT INTO menu_items (
                restaurant_id, category_id, name, description, price, 
                image, is_veg, is_available, is_special, is_popular, is_trending
            ) VALUES (
                :restaurant_id, :category_id, :name, :description, :price, 
                :image, :is_veg, 1, :is_special, :is_popular, :is_trending
            )
        ");
        $stmt->execute([
            ':restaurant_id' => $rId,
            ':category_id' => $item['category_id'],
            ':name' => $item['name'],
            ':description' => $item['description'],
            ':price' => $item['price'],
            ':image' => $item['image'],
            ':is_veg' => $item['is_veg'],
            ':is_special' => $item['is_special'],
            ':is_popular' => $item['is_popular'],
            ':is_trending' => $item['is_trending']
        ]);
    }
    echo "Seeded menu items successfully.\n";
    echo "DATABASE SEED COMPLETE!\n";

} catch (Exception $e) {
    echo "Error seeding database: " . $e->getMessage() . "\n";
    exit(1);
}
