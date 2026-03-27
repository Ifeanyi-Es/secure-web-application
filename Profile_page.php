<?php
session_start();
require 'includes/db_connect.php';
require 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: Login.php");
    exit;
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }
    
    // Handle Remove Photo
    if (isset($_POST['remove_photo'])) {
        // Get current profile pic path to delete file
        $sql = "SELECT profile_pic FROM users WHERE id = $userId";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $currentPic = $row['profile_pic'];
            // If it's a file path (not URL) and exists, delete it
            if (!empty($currentPic) && !filter_var($currentPic, FILTER_VALIDATE_URL) && file_exists($currentPic)) {
                unlink($currentPic);
            }
        }

        $sql = "UPDATE users SET profile_pic = NULL, profile_pic_original_name = NULL WHERE id = $userId";
        if ($conn->query($sql) === TRUE) {
            $_SESSION['profile_pic'] = null; // Will fallback to default
            $message = "Profile picture removed.";
        } else {
            $error = "Error removing profile picture: " . $conn->error;
        }
    }
    // Check for potential upload size issues causing empty POST
    elseif (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "The uploaded file is too large. Please try a smaller file (max 2MB usually).";
    } else {
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email)) {
             $error = "Name and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             $error = "Invalid email format.";
        } elseif (!empty($phone) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
             $error = "Invalid phone number format.";
        }
        
        // 1. Handle File Upload
        $profilePicPath = null;
        $profilePicOriginalName = null;

        if (empty($error) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                $error = "File size exceeds 5MB limit.";
            } else {
                $uploadDir = UPLOAD_DIR_PROFILE;
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0755, true)) {
                        $error = "Failed to create upload directory. Please check server permissions.";
                    }
                }

                if (empty($error)) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($_FILES['avatar']['tmp_name']);
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

                    if (in_array($mime, $allowedMimes)) {
                        $fileExtension = '';
                        if ($mime === 'image/jpeg') $fileExtension = 'jpg';
                        elseif ($mime === 'image/png') $fileExtension = 'png';
                        elseif ($mime === 'image/gif') $fileExtension = 'gif';

                        $profilePicOriginalName = basename($_FILES['avatar']['name']);
                        $newFileName = time() . '_' . $userId . '.' . $fileExtension;
                        $dest_path = $uploadDir . $newFileName;

                        if(move_uploaded_file($_FILES['avatar']['tmp_name'], $dest_path)) {
                            $profilePicPath = $dest_path;
                        } else {
                            $error = 'There was some error moving the file to upload directory.';
                        }
                    } else {
                        $error = 'Upload failed. Allowed file types: JPEG, PNG, GIF.';
                    }
                }
            }
        } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
             // Handle other upload errors
             switch ($_FILES['avatar']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "File is too large.";
                    break;
                default:
                    $error = "File upload error code: " . $_FILES['avatar']['error'];
             }
        }

        if (empty($error)) {
            // Password Update Logic
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    $error = "Please enter your current password to set a new one.";
                } else {
                    // Verify current password
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->bind_result($dbHash);
                    $stmt->fetch();
                    $stmt->close();

                    if (!password_verify($currentPassword, $dbHash)) {
                        $error = "Current password is incorrect.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = "New passwords do not match.";
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    }
                }
            }

            if (empty($error)) {
                // Update User Info using Prepared Statements
                $query = "UPDATE users SET first_name=?, last_name=?, email=?, phone=?, bio=?";
                $types = "sssss";
                $params = [$firstName, $lastName, $email, $phone, $bio];

                if (!empty($newPassword)) {
                    $query .= ", password=?";
                    $types .= "s";
                    $params[] = $hashedPassword;
                }

                if ($profilePicPath) {
                    $query .= ", profile_pic=?, profile_pic_original_name=?";
                    $types .= "ss";
                    $params[] = $profilePicPath;
                    $params[] = $profilePicOriginalName;
                    $_SESSION['profile_pic'] = $profilePicPath;
                }
                
                $query .= " WHERE id=?";
                $types .= "i";
                $params[] = $userId;

                $stmt = $conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        $message = "Profile updated successfully!";
                        $_SESSION['name'] = $firstName;
                        $_SESSION['last_name'] = $lastName;
                    } else {
                        $error = "Error updating record: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Database prepare error: " . $conn->error;
                }
            }
        }
    }
}

// Fetch Current User Data
$sql = "SELECT * FROM users WHERE id = $userId";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

// Fallback for profile pic
$default_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=random';
// If profile_pic is a path (not URL), convert to download link
$profilePic = $user['profile_pic'];
if (!empty($profilePic) && !filter_var($profilePic, FILTER_VALIDATE_URL)) {
    $profilePic = 'download.php?type=profile&id=' . $userId;
} else {
    $profilePic = !empty($profilePic) ? $profilePic : $default_avatar;
}

// Ensure session has it
if (!isset($_SESSION['profile_pic'])) {
    $_SESSION['profile_pic'] = $profilePic;
} else {
    // Update session if it's a local path
    if (!empty($_SESSION['profile_pic']) && !filter_var($_SESSION['profile_pic'], FILTER_VALIDATE_URL)) {
        $_SESSION['profile_pic'] = 'download.php?type=profile&id=' . $userId;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <title>Admin Dashboard</title>

</head>
<body>

 <nav class="navbar navbar-expand-lg navbar-light bg-light  p-2 m-0 w-100">
        <div class="container-fluid">
            <a class="navbar-brand text-primary fw-bold" href="index.php">Lancashire Medicare</a>
             <!-- Added Profile Pic to Navbar -->
             <div class="dropdown ms-auto">
                <button class="btn d-flex align-items-center gap-2 border-0 bg-transparent py-1 px-2"
                        id="accountMenu"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">

                    <div class="d-none d-md-flex flex-column align-items-end me-1">
                        <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hi,</span>
                        <span class="fw-bold text-dark">
                            <?php echo htmlspecialchars(($_SESSION['name'] ?? 'User') . ' ' . ($_SESSION['last_name'] ?? '')); ?>
                        </span>
                    </div>

                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'https://ui-avatars.com/api/?name=User&background=random'); ?>"
                         alt="User Avatar"
                         class="rounded-circle shadow-sm border border-2 border-white"
                         width="42"
                         height="42"
                         style="object-fit: cover;">

                    <i class="bi bi-chevron-down text-secondary" style="font-size: 0.75rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="Profile_page.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                  <li><a class="dropdown-item" href="settings.html"><i class="bi bi-gear me-2"></i>Settings</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li> <a class="dropdown-item logoutBtn" href="logout.php"> <i class="bi bi-box-arrow-right me-2"></i>Sign out </a></li>
                </ul>
            </div>
        </div>
  </nav>

    <div class=" h-100">
        <div class="row g-0 h-100">
            <!-- Sidebar -->
            <aside class="col-md-2 bg-light p-3 h-100 border-end" id="sidebar"> <!--  side bar starts here -->
         <div class="d-flex flex-column  bg-light h-100 ">

            
        <button onclick="history.back()" class="btn btn-primary mb-3"> &larr; Go Back </button>

        
    
    
      </div>
   
    </aside> <!-- sidebar end here-->
        

            <!-- Main -->
             <main class="col-12 col-md-9 col-xl-10 p-4">
                <div class="container">
                    <!-- Messages -->
                    <?php if($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Edit Profile</h2>
                        <div>
                            <button type="button" onclick="history.back()" class="btn btn-outline-secondary me-2">Cancel</button>
                            <button form="profileForm" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-lg-4">
                            <div class="card shadow-sm">
                                <div class="card-body text-center">
                                    <!-- Dynamic Avatar -->
                                    <img id="avatarPreview" class="avatar mb-3 rounded-circle" alt="Profile avatar" 
                                         src="<?php echo htmlspecialchars($profilePic); ?>" 
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                    
                                    <h5 class="mb-1" id="displayName"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['role']); ?></p>

                                    <div class="d-grid gap-2">
                                        <label class="btn btn-outline-primary file-input-btn mb-0">
                                            Change photo
                                            <input id="avatarInput" name="avatar" type="file" accept="image/*" form="profileForm" hidden onchange="previewImage(this)">
                                        </label>
                                        <button id="removePhoto" type="button" class="btn btn-outline-danger" onclick="if(confirm('Are you sure you want to remove your profile picture?')) document.getElementById('removePhotoForm').submit();">Remove photo</button>
                                    </div>
                                    
                                    <!-- Hidden form for removing photo -->
                                    <form id="removePhotoForm" method="POST" style="display: none;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="remove_photo" value="1">
                                    </form>

                                    <small class="text-muted d-block mt-3">JPG, PNG up to 5MB. Cropping will be applied by the server.</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-8">
                            <form id="profileForm" class="card p-3 shadow-sm" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="firstName" class="form-label">First name</label>
                                        <input id="firstName" name="firstName" type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Last name</label>
                                        <input id="lastName" name="lastName" type="text" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input id="email" name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input id="phone" name="phone" type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>

                                    <div class="col-12">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <hr class="my-3">
                                        <h6 class="text-muted mb-3">Change Password</h6>
                                    </div>

                                    <div class="col-12">
                                        <label for="currentPassword" class="form-label">Current Password (required to change password)</label>
                                        <input id="currentPassword" name="currentPassword" type="password" class="form-control" placeholder="Enter current password">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="newPassword" class="form-label">New Password</label>
                                        <input id="newPassword" name="newPassword" type="password" class="form-control" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                                        <input id="confirmPassword" name="confirmPassword" type="password" class="form-control" placeholder="Confirm new password">
                                    </div>

                                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                        <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                        <button type="submit" class="btn btn-primary">Save profile</button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <footer class="bd-footer py-3 mt-auto bg-dark text-center text-light">
      &copy; 2025 Lancashire Medicare
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
