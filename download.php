<?php
session_start();
require 'includes/db_connect.php';
require 'includes/config.php';

// 1. Check Authentication
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit('Access Denied');
}

// 2. Validate Parameters
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($id <= 0 || !in_array($type, ['resume', 'cover', 'profile'])) {
    header("HTTP/1.1 400 Bad Request");
    exit('Invalid Request');
}

$filePath = '';
$originalName = '';
$mimeType = '';

// 3. Fetch File Info & Check Authorization
if ($type === 'profile') {
    // Profile Pictures: Accessible to any logged-in user (used in UI)
    $stmt = $conn->prepare("SELECT profile_pic, profile_pic_original_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($dbPath, $dbOriginalName);
    if ($stmt->fetch()) {
        // If the path in DB is a URL (e.g. ui-avatars), redirect to it
        if (filter_var($dbPath, FILTER_VALIDATE_URL)) {
            header("Location: $dbPath");
            exit;
        }
        // Otherwise, it's a local file
        // The DB might store the full path or just the filename. 
        // Our new logic stores just the filename in the safe path, but legacy might be full path.
        // We will assume new logic stores basename, but handle full path if present.
        $basename = basename($dbPath);
        $filePath = UPLOAD_DIR_PROFILE . $basename;
        $originalName = $dbOriginalName ?: 'profile.jpg';
        $mimeType = mime_content_type($filePath); // Auto-detect
    } else {
        header("HTTP/1.1 404 Not Found");
        exit('File not found');
    }
    $stmt->close();

} elseif ($type === 'resume' || $type === 'cover') {
    // Applications: Accessible to Admin, Employer, or the Owner
    $stmt = $conn->prepare("SELECT email, resume_path, resume_original_name, cover_letter_path, cover_original_name FROM applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($ownerEmail, $resumePath, $resumeOriginal, $coverPath, $coverOriginal);
    
    if ($stmt->fetch()) {
        // Authorization Check
        $canView = false;
        if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'employer') {
            $canView = true;
        } elseif ($_SESSION['email'] === $ownerEmail) {
            $canView = true;
        }

        if (!$canView) {
            header("HTTP/1.1 403 Forbidden");
            exit('Access Denied');
        }

        if ($type === 'resume') {
            $basename = basename($resumePath);
            $filePath = UPLOAD_DIR_RESUMES . $basename;
            $originalName = $resumeOriginal ?: 'resume.pdf';
        } else {
            if (empty($coverPath)) {
                exit('No cover letter found');
            }
            $basename = basename($coverPath);
            $filePath = UPLOAD_DIR_COVERS . $basename;
            $originalName = $coverOriginal ?: 'cover_letter.pdf';
        }
        $mimeType = 'application/pdf';
    } else {
        header("HTTP/1.1 404 Not Found");
        exit('Application not found');
    }
    $stmt->close();
}

// 4. Stream File
if (file_exists($filePath)) {
    // Prevent Path Traversal (basename check already done above, but double check realpath)
    $realPath = realpath($filePath);
    if ($realPath === false || strpos($realPath, UPLOAD_ROOT) !== 0) {
        // Ensure file is inside UPLOAD_ROOT
        header("HTTP/1.1 403 Forbidden");
        exit('Invalid file path');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . $originalName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    header("HTTP/1.1 404 Not Found");
    exit('File not found on server');
}
?>
