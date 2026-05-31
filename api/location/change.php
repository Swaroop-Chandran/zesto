<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/location_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$location = trim($data['location'] ?? '');

if (empty($location)) {
    jsonResponse(['success' => false, 'message' => 'Location is required.'], 422);
}

setCurrentLocation($location);

jsonResponse([
    'success' => true,
    'message' => 'Location updated successfully!',
    'location' => getCurrentLocation(),
    'city' => getCurrentCity()
]);
