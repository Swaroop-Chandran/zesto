<?php
/**
 * Zesto — Redirect to Home with Auth Drawer open
 */
require_once __DIR__ . '/config/config.php';
header('Location: ' . BASE_URL . '/index.php?auth=open');
exit;
