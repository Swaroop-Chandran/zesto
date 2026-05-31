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
