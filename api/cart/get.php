<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');
jsonResponse(['success' => true, 'cart' => getCart(), 'cart_count' => getCartCount(), 'subtotal' => number_format(getCartSubtotal(), 2)]);
