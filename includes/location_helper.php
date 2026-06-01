<?php
/**
 * Zesto — Location Management Helper
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DEFAULT_LOCATION', 'Mumbai, Maharashtra');

// Predefined available Indian locations
$predefinedLocations = [
    'Mumbai, Maharashtra' => ['city' => 'Mumbai', 'lat' => 19.0760, 'lng' => 72.8777, 'desc' => 'Andheri West, Bandra, Colaba'],
    'Delhi, NCR' => ['city' => 'Delhi', 'lat' => 28.7041, 'lng' => 77.1025, 'desc' => 'Connaught Place, Karol Bagh, Saket'],
    'Bangalore, Karnataka' => ['city' => 'Bangalore', 'lat' => 12.9716, 'lng' => 77.5946, 'desc' => 'Koramangala, Indiranagar, HSR Layout'],
    'Pune, Maharashtra' => ['city' => 'Pune', 'lat' => 18.5204, 'lng' => 73.8567, 'desc' => 'Koregaon Park, Kothrud, Hinjewadi'],
    'Hyderabad, Telangana' => ['city' => 'Hyderabad', 'lat' => 17.3850, 'lng' => 78.4867, 'desc' => 'Gachibowli, Jubilee Hills, Madhapur'],
    'Chennai, Tamil Nadu' => ['city' => 'Chennai', 'lat' => 13.0827, 'lng' => 80.2707, 'desc' => 'Adyar, T-Nagar, Velachery']
];

/**
 * Get currently selected location from session, or default.
 */
function getCurrentLocation(): string {
    return $_SESSION['current_location'] ?? DEFAULT_LOCATION;
}

/**
 * Get active city name from the selected location.
 */
function getCurrentCity(): string {
    $loc = getCurrentLocation();
    global $predefinedLocations;
    if (isset($predefinedLocations[$loc])) {
        return $predefinedLocations[$loc]['city'];
    }
    return 'Mumbai';
}

/**
 * Set the current location in the session.
 */
function setCurrentLocation(string $location): void {
    global $predefinedLocations;
    if (isset($predefinedLocations[$location])) {
        $_SESSION['current_location'] = $location;
    } else {
        // Find nearest or dynamic match
        foreach ($predefinedLocations as $name => $info) {
            if (stripos($name, $location) !== false || stripos($info['city'], $location) !== false) {
                $_SESSION['current_location'] = $name;
                return;
            }
        }
        // Fallback
        $_SESSION['current_location'] = DEFAULT_LOCATION;
    }
}

/**
 * Get available predefined locations.
 */
function getPredefinedLocations(): array {
    global $predefinedLocations;
    return $predefinedLocations;
}

/**
 * Calculate Haversine distance between two coordinates in kilometers.
 */
function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earthRadius = 6371; // km
    $latDelta = deg2rad($lat2 - $lat1);
    $lngDelta = deg2rad($lng2 - $lng1);
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lngDelta / 2) * sin($lngDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 2);
}

/**
 * Get coordinates for a customer/delivery partner (user_id) or restaurant (restaurant_id)
 */
function getEntityLocation(string $entityType, int $entityId): ?array {
    $column = ($entityType === 'restaurant') ? 'restaurant_id' : 'user_id';
    $stmt = db()->prepare("SELECT latitude, longitude FROM delivery_locations WHERE {$column} = :eid LIMIT 1");
    $stmt->execute([':eid' => $entityId]);
    $res = $stmt->fetch();
    if ($res) {
        return [
            'lat' => (float)$res['latitude'],
            'lng' => (float)$res['longitude']
        ];
    }
    
    // Fallback coordinates to make sure the app never crashes
    if ($entityType === 'restaurant') {
        return ['lat' => 19.0760, 'lng' => 72.8777]; // Mumbai center
    }
    return ['lat' => 19.0820, 'lng' => 72.8820];
}

/**
 * Update coordinates for a customer/partner (user_id) or restaurant (restaurant_id)
 */
function updateEntityLocation(string $entityType, int $entityId, float $lat, float $lng): bool {
    $column = ($entityType === 'restaurant') ? 'restaurant_id' : 'user_id';
    $stmt = db()->prepare("
        INSERT INTO delivery_locations ({$column}, latitude, longitude)
        VALUES (:eid, :lat, :lng)
        ON DUPLICATE KEY UPDATE latitude = :lat, longitude = :lng
    ");
    return $stmt->execute([
        ':eid' => $entityId,
        ':lat' => $lat,
        ':lng' => $lng
    ]);
}
