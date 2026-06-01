<?php
/**
 * Zesto — Restaurant Management Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Restaurant.php';

class RestaurantService {

    /**
     * Fetch active restaurants in selected area.
     */
    public static function getActiveRestaurants(string $search = ''): array {
        $db = db();
        if (!empty($search)) {
            $stmt = $db->prepare("
                SELECT * FROM restaurants 
                WHERE is_active = 1 
                  AND (name LIKE :search OR address LIKE :search)
                ORDER BY rating DESC
            ");
            $stmt->execute([':search' => '%' . $search . '%']);
        } else {
            $stmt = $db->query("SELECT * FROM restaurants WHERE is_active = 1 ORDER BY rating DESC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Onboard a new restaurant.
     */
    public static function onboardRestaurant(int $ownerId, string $name, string $address, string $image): ?Restaurant {
        $db = db();
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        try {
            $stmt = $db->prepare("
                INSERT INTO restaurants (owner_id, name, slug, address, image)
                VALUES (:oid, :name, :slug, :addr, :img)
            ");
            $success = $stmt->execute([
                ':oid' => $ownerId,
                ':name' => $name,
                ':slug' => $slug,
                ':addr' => $address,
                ':img' => $image
            ]);

            if ($success) {
                return Restaurant::getByOwnerId($ownerId);
            }
        } catch (PDOException $e) {
            error_log("RestaurantService::onboardRestaurant Error: " . $e->getMessage());
        }
        return null;
    }
}
