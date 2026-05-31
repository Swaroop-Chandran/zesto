<?php
/**
 * Zesto — Dynamic File Upload Helper
 */

if (!defined('UPLOAD_DIR')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Handle secure file uploads for images.
 * @param array  $file        The $_FILES['input_name'] array
 * @param string $subFolder   The sub-directory inside assets/uploads (e.g. 'foods', 'logos', 'banners')
 * @param array  &$errors     An array passed by reference to store error messages
 * @return string|null        The web-accessible relative URL on success, null on failure
 */
function handleImageUpload(array $file, string $subFolder, array &$errors): ?string {
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed with error code: ' . $file['error'];
        return null;
    }

    // 1. Target Directory Setup
    $targetDir = UPLOAD_DIR . $subFolder . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 2. Validate File Size
    $maxBytes = MAX_UPLOAD_MB * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $errors[] = 'File size exceeds limit of ' . MAX_UPLOAD_MB . 'MB.';
        return null;
    }

    // 3. Validate Mime Type & Extension
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileMime = mime_content_type($file['tmp_name']);
    if (!in_array($fileMime, $allowedMimes, true)) {
        $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
        return null;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $ext;
    $targetFile = $targetDir . $filename;

    // 4. Move File
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return BASE_URL . '/assets/uploads/' . $subFolder . '/' . $filename;
    } else {
        $errors[] = 'Could not save the uploaded file. Check permissions.';
        return null;
    }
}
