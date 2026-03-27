<?php
// Configuration for file uploads
// Determine if we are on the production server or local
if (strpos(__DIR__, 'home/foldername') !== false) {
    define('UPLOAD_ROOT', '/home/foldername/uploads/');
} else {
    // Local fallback for development (Windows)
    define('UPLOAD_ROOT', __DIR__ . '/../../uploads/');
}

define('UPLOAD_DIR_RESUMES', UPLOAD_ROOT . 'resumes/');
define('UPLOAD_DIR_COVERS', UPLOAD_ROOT . 'coverletters/');
define('UPLOAD_DIR_PROFILE', UPLOAD_ROOT . 'profile_pictures/');
?>
