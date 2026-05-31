<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
logoutUser();
setFlash('success', 'You have been signed out. See you soon!');
header('Location: ' . BASE_URL . '/login.php');
exit;
