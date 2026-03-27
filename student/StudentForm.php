<?php
session_start();
include '../includes/db_connect.php';
require '../includes/config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || strtolower($_SESSION['role']) !== 'student') {
    header("Location: Login.php");
    exit;
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_msg = '';
$error_msg = '';

// Initialize variables
$title = '';
$firstName = $_SESSION['name'] ?? '';
$lastName = $_SESSION['last_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$phone = '';
$nationality = '';
$jobRole = $_GET['role'] ?? '';
$university = '';
$program = '';
$coverText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $title = $_POST['title'] ?? '';
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $jobRole = $_POST['jobRole'] ?? '';
    $university = $_POST['university'] ?? '';
    $program = $_POST['program'] ?? '';
    $coverText = $_POST['coverText'] ?? '';
    $consent = isset($_POST['consent']) ? 'true' : 'false';

    // Basic Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($jobRole)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $error_msg = "Invalid phone number format.";
    } elseif ($consent !== 'true') {
        $error_msg = "You must consent to processing.";
    } else {
        // File Upload Logic
        $resume_path = '';
        $resume_original_name = '';
        $cover_letter_path = null;
        $cover_original_name = null;

        // Resume
        if (isset($_FILES['resumeFile']) && $_FILES['resumeFile']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['resumeFile']['size'] > 5 * 1024 * 1024) {
                $error_msg = "Resume file size exceeds 5MB limit.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['resumeFile']['tmp_name']);
                
                $allowed_mimes = [
                    'application/pdf' => '.pdf',
                    'application/msword' => '.doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx'
                ];
                
                if (array_key_exists($mime, $allowed_mimes)) {
                    $resume_original_name = basename($_FILES['resumeFile']['name']);
                    $ext = $allowed_mimes[$mime];
                    $resume_filename = uniqid('resume_', true) . $ext;
                    $resume_path = UPLOAD_DIR_RESUMES . $resume_filename;
                    
                    if (!move_uploaded_file($_FILES['resumeFile']['tmp_name'], $resume_path)) {
                        $error_msg = "Failed to upload resume.";
                    }
                } else {
                    $error_msg = "Resume must be a PDF, DOC, or DOCX file.";
                }
            }
        } else {
            $error_msg = "Resume file is required.";
        }

        // Cover Letter
        if (empty($error_msg) && isset($_FILES['coverFile']) && $_FILES['coverFile']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['coverFile']['size'] > 5 * 1024 * 1024) {
                $error_msg = "Cover letter file size exceeds 5MB limit.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['coverFile']['tmp_name']);
                
                $allowed_mimes = [
                    'application/pdf' => '.pdf',
                    'application/msword' => '.doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx'
                ];
                
                if (array_key_exists($mime, $allowed_mimes)) {
                    $cover_original_name = basename($_FILES['coverFile']['name']);
                    $ext = $allowed_mimes[$mime];
                    $cover_filename = uniqid('cover_', true) . $ext;
                    $cover_letter_path = UPLOAD_DIR_COVERS . $cover_filename;
                    
                    if (!move_uploaded_file($_FILES['coverFile']['tmp_name'], $cover_letter_path)) {
                        $error_msg = "Failed to upload cover letter.";
                    }
                } else {
                    $error_msg = "Cover letter must be a PDF, DOC, or DOCX file.";
                }
            }
        }

        if (empty($error_msg)) {
            $status = 'Submitted';
            // Note: We store the full path or just the filename. 
            // The download script expects the file to be in the configured directory.
            // Let's store the full path as before to minimize DB changes, or just the basename if we want to be cleaner.
            // The previous code stored full path. Let's stick to full path for compatibility with existing logic if any, 
            // but our download script uses basename() anyway.
            
            $sql = "INSERT INTO applications (title, firstName, lastName, email, phone, nationality, jobRole, university, program, resume_path, resume_original_name, cover_letter_path, cover_original_name, cover_letter_text, status, consent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssssssssssssssss', 
                    $title, $firstName, $lastName, $email, $phone, $nationality, 
                    $jobRole, $university, $program, $resume_path, $resume_original_name, $cover_letter_path, $cover_original_name, 
                    $coverText, $status, $consent
                );

                if ($stmt->execute()) {
                    $success_msg = "Application submitted successfully! Reference: APP-" . $stmt->insert_id;
                } else {
                    $error_msg = "Database error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_msg = "Database prepare error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Internship Application — Lancashire Medicare</title>

  <!-- Bootstrap + icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
  <link href="../css/studentform.css" rel="stylesheet">
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand" href="../index.php">Lancashire Medicare</a>
      <div class="d-flex align-items-center ms-auto gap-2">
         <div id="signedInUser" class="d-none d-md-flex align-items-center text-muted small">
            Signed in as <strong class="ms-1"><?php echo htmlspecialchars($_SESSION['name'] . ' ' . $_SESSION['last_name']); ?></strong>
         </div>
        <div class="dropdown">
          <button class="btn btn-light btn-sm d-flex align-items-center" data-bs-toggle="dropdown">
            <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'https://ui-avatars.com/api/?name=User&background=random'); ?>" class="userProfilePic rounded-circle" width="34" height="34" alt="User Avatar">
            <i class="bi bi-caret-down-fill ms-2"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="../Profile_page.php"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="../settings.html"><i class="bi bi-gear me-2"></i>Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li> <a class="dropdown-item logoutBtn" href="../logout.php"> <i class="bi bi-box-arrow-right me-2"></i>Sign out </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <main class="container-fluid layout py-4">
    <div class="row gx-4">
      <aside class="col-12 col-md-3" id="sidebar">
        <div class="card-surface">
          <h6 class="mb-2 text-center">Application Status</h6>

          <div class="status-steps mb-3" id="statusSteps" aria-hidden="false">
            <div class="step" data-step="submitted"><span class="dot"></span><div class="label">Submitted</div></div>
            <div class="step" data-step="review"><span class="dot"></span><div class="label">Under review</div></div>
            <div class="step" data-step="decision"><span class="dot"></span><div class="label">Decision</div></div>
          </div>

            <div>
                <a href="application_history.php" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-journal-text me-1"></i>View application history</a>
        </div>

        <div class="card-surface mt-3">
          <h6 class="mb-2">Notes</h6>
          <p class="muted-small mb-0">Accepted file types: .pdf, .doc, .docx — max 5MB each. Use the Submit button to send your final application.</p>
        </div>
      </aside>

      <section class="col-12 col-md-9">
        <div class="card-surface">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Internship Application</h5>
            <div class="muted-small">Fill required fields and submit</div>
          </div>

          <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <form id="applicationForm" method="POST" action="StudentFormSubmit.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="row">
              <div class="col-lg-6 form-section">
                <label class="form-label">Title</label>
                <input class="form-control" id="title" name="title" placeholder="Mr / Ms / Dr" value="<?php echo htmlspecialchars($title); ?>">

                <label class="form-label mt-3">First name *</label>
                <input class="form-control" id="firstName" name="firstName" required value="<?php echo htmlspecialchars($firstName); ?>">

                <label class="form-label mt-3">Last name *</label>
                <input class="form-control" id="lastName" name="lastName" required value="<?php echo htmlspecialchars($lastName); ?>">

                <label class="form-label mt-3">Email *</label>
                <input class="form-control" id="email" type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">

                <label class="form-label mt-3">Phone *</label>
                <input class="form-control" id="phone" type="tel" name="phone" required value="<?php echo htmlspecialchars($phone); ?>">
              </div>

              <div class="col-lg-6 form-section">
                <label class="form-label">Nationality</label>
                <select id="nationality" name="nationality" class="form-select">
                  <option value="">Select nationality</option>
                  <?php 
                    $nations = ['United Kingdom', 'France', 'Canada', 'India'];
                    foreach ($nations as $nat) {
                        $selected = ($nationality == $nat) ? 'selected' : '';
                        echo "<option value='$nat' $selected>$nat</option>";
                    }
                  ?>
                </select>

                <label class="form-label mt-3">Desired role *</label>
                <select id="jobRole" name="jobRole" class="form-select" required>
                  <option value="">Choose role</option>
                  <?php 
                    $roles = ['Medical Intern', 'Nursing Intern', 'Pharmacy Intern', 'Radiology Intern', 'Dentist Intern'];
                    foreach ($roles as $role) {
                        $selected = ($jobRole == $role) ? 'selected' : '';
                        echo "<option value='$role' $selected>$role</option>";
                    }
                  ?>
                </select>

                <label class="form-label mt-3">University / Institution</label>
                <input class="form-control" id="university" name="university" value="<?php echo htmlspecialchars($university); ?>">

                <label class="form-label mt-3">Program of study</label>
                <input class="form-control" id="program" name="program" value="<?php echo htmlspecialchars($program); ?>">
              </div>
            </div>

            <hr>

            <div class="row">
              <div class="col-md-6">
                <label class="form-label">Resume / CV *</label>
                <input type="file" class="form-control" id="resumeFile" name="resumeFile" accept=".pdf,.doc,.docx" required>
                <div class="form-text">PDF / DOC / DOCX (max 5MB)</div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Cover letter (optional)</label>
                <input type="file" class="form-control" id="coverFile" name="coverFile" accept=".pdf,.doc,.docx">
                <div class="form-text">Upload file or paste text below</div>
                
                <textarea id="coverText" name="coverText" class="form-control mt-2" rows="6" placeholder="Paste cover letter text (optional)"><?php echo htmlspecialchars($coverText); ?></textarea>
              </div>
            </div>

            <div class="form-check form-check-inline mt-3">
              <input class="form-check-input" type="checkbox" id="consent" name="consent" value="true" required>
              <label class="form-check-label muted-small" for="consent">I confirm the information is correct and I consent to processing (required).</label>
            </div>

            <div class="mt-4 d-flex gap-2">
              <button id="submitBtn" type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit application</button>
            </div>

          </form>
        </div>
      </section>
    </div>
  </main>

  <footer class="py-3 text-center muted-small">© 2025 Lancashire Medicare</footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="../js/auth.js"></script>
  <script src="../js/script.js" ></script>

  <!-- Loading Modal -->
  <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center py-4">
          <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
          </div>
          <h5>Submitting Application...</h5>
          <p class="text-muted mb-0">Please wait while we upload your files.</p>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('applicationForm').addEventListener('submit', function() {
        // Only show if form is valid (browser validation)
        if (this.checkValidity()) {
            var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
        }
    });
  </script>

</body>
</html>
