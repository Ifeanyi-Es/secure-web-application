<?php
session_start();

// Check if the user is already logged in, if so, redirect to appropriate page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["role"] === 'admin') {
        header("location: Admin_dashboard.php");
    } else if ($_SESSION["role"] === 'employer') {
        header("location: employee_dashboard.php");
    } else {
        header("location: application_history.php");
    }
    exit;
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'includes/db_connect.php';

$login_err = $signup_err = "";

// SIGNUP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'signup') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $email = trim($_POST["signupEmail"]);
    $password = trim($_POST["signupPassword"]);
    $firstName = trim($_POST["firstName"]);
    $lastName = trim($_POST["lastName"]);
    $role = trim($_POST["role"]);

    // Simple validation
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($role)) {
        $signup_err = "Please fill all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_err = "Invalid email format.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $signup_err = "This email is already taken.";
                } else {
                    $sql = "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)";
                    if ($stmt_insert = $conn->prepare($sql)) {
                        $stmt_insert->bind_param("sssss", $firstName, $lastName, $email, $param_password, $role);
                        $param_password = password_hash($password, PASSWORD_DEFAULT);
                        if ($stmt_insert->execute()) {
                            // Redirect to login page (or log them in directly)
                            header("location: login.php");
                        } else {
                            $signup_err = "Something went wrong. Please try again later.";
                        }
                        $stmt_insert->close();
                    }
                }
            } else {
                $signup_err = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
}

// LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $email = trim($_POST["loginEmail"]);
    $password = trim($_POST["loginPassword"]);

    if (empty($email) || empty($password)) {
        $login_err = "Please enter email and password.";
    } else {
        $sql = "SELECT id, email, password, role, first_name, last_name, profile_pic FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $email, $hashed_password, $role, $first_name, $last_name, $profile_pic);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            
                            session_regenerate_id(true); // Prevent session fixation
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;
                            $_SESSION["name"] = $first_name;
                            $_SESSION["last_name"] = $last_name;
                            // Use UI Avatars if no profile pic
                            $default_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($first_name . ' ' . $last_name) . '&background=random';
                            
                            // If profile_pic is set and not a URL, convert to download link
                            if (!empty($profile_pic) && !filter_var($profile_pic, FILTER_VALIDATE_URL)) {
                                $_SESSION["profile_pic"] = 'download.php?type=profile&id=' . $id;
                            } else {
                                $_SESSION["profile_pic"] = !empty($profile_pic) ? $profile_pic : $default_avatar;
                            }

                            if ($role === 'admin') {
                                header("location: Admin_dashboard.php");
                            } else if ($role === 'employer') {
                                header("location: employee_dashboard.php");
                            } else {
                                header("location: application_history.php");
                            }
                        } else {
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login — Lancashire Medicare</title>

 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
  
  integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <link href="css/login.css" rel="stylesheet">
</head>
<body>

  <main class="container-auth">
    <div class="auth-card" role="region" aria-label="Authentication">

      <!-- Left side: welcome, role selector info -->
      <div class="auth-side">
        <div class="d-flex align-items-center ">
          <a href="index.php" class="nav-link fw-bold text-primary text-center " style="margin-left: 30%;">Lancashire Medicare</a>
        </div>

        <p class="small-muted text-center" style="margin-bottom: 30%;">Sign in or create an account as Student or Employer </p>

    
        <div class="divider" aria-hidden="true"></div>

        <div class="small-muted mt-3 text-center">
          <p class="mb-1">Need help?</p> click to <a class="link-primary" href="Login.php">contact support</a>.
          <ul>
            
          </ul>
        </div>
      </div>

      <!-- Right side: forms -->
      <div class="auth-form">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Access account</h4>
          <div class="text-end small-muted">
            <div id="signedInUser" style="display:none">
              <img id="signedAvatar" src="" class="avatar-sm me-1" alt="">
              <span id="signedName"></span>
            </div>
            <div id="notSigned">Not signed in</div>
          </div>
        </div>

        <ul class="nav nav-pills role-tabs mb-3" id="authTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#loginPane" type="button" role="tab" aria-controls="loginPane" aria-selected="true">
              <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="signup-tab" data-bs-toggle="pill" data-bs-target="#signupPane" type="button" role="tab" aria-controls="signupPane" aria-selected="false">
              <i class="bi bi-person-plus me-1"></i> Sign up
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <!-- LOGIN PANE -->
          <div class="tab-pane fade show active" id="loginPane" role="tabpanel" aria-labelledby="login-tab">
            <form id="loginForm" action="Login.php" method="post" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="action" value="login">
              <div class="mb-3">
                <label for="loginEmail" class="form-label">Email</label>
                <input id="loginEmail" name="loginEmail" type="email" class="form-control" placeholder="you@lancashiremedicare.co.uk" required autocomplete="username">
              </div>

              <div class="mb-3 position-relative">
                <label for="loginPassword" class="form-label">Password</label>
                <input id="loginPassword" name="loginPassword" type="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                <button type="button" class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y" id="toggleLoginPwd" aria-label="Toggle password"><i id="toggleLoginIcon" class="bi bi-eye-slash"></i></button>
              </div>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                  <input id="remember" class="form-check-input" type="checkbox">
                  <label class="form-check-label small-muted" for="remember">Remember me</label>
                </div>
                <a href="Login.php" class="small">Forgot Password?</a>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
              </div>

              <div id="loginError" class="validation-error" role="alert" style="display:<?php echo !empty($login_err) ? 'block' : 'none'; ?>"><?php echo $login_err; ?></div>

            </form>
          </div>

          <!-- SIGNUP PANE -->
          <div class="tab-pane fade" id="signupPane" role="tabpanel" aria-labelledby="signup-tab">
            <form id="signupForm" action="Login.php" method="post" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="action" value="signup">
              <div class="row g-2">
                <div class="col-6">
                  <label for="firstName" class="form-label">First name</label>
                  <input id="firstName" name="firstName" class="form-control" required>
                </div>
                <div class="col-6">
                  <label for="lastName" class="form-label">Last name</label>
                  <input id="lastName" name="lastName" class="form-control" required>
                </div>
              </div>

              <div class="mb-3 mt-2">
                <label for="signupEmail" class="form-label">Work email</label>
                <input id="signupEmail" name="signupEmail" type="email" class="form-control" placeholder="you@lancashiremedicare.co.uk" required>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <label for="signupPassword" class="form-label">Password</label>
                  <input id="signupPassword" name="signupPassword" type="password" class="form-control" minlength="8" required>
                </div>
                <div class="col-6">
                  <label for="confirmPassword" class="form-label">Confirm</label>
                  <input id="confirmPassword" type="password" class="form-control" required>
                </div>
              </div>

              <div class="mb-3 mt-2">
                <label for="roleSelect" class="form-label">Role</label>
                <select id="roleSelect" name="role" class="form-select" required>
                  <option value="">Select role</option>
                  <option value="student">Student</option>
                  <option value="employer">Employer</option>
                </select>
              </div>

              <!-- role-specific fields -->
              <div id="roleExtra"></div>

              <div class="form-actions">
                <button type="submit" class="btn btn-success w-100">Create account</button>
              </div>

              <div id="signupMsg" class="login-note mt-2" style="display:<?php echo !empty($signup_err) ? 'block' : 'none'; ?>"><?php echo $signup_err; ?></div>
            </form>
          </div>
        </div>

        <div class="footer-note">© 2025 Lancashire Medicare</div>
      </div>
    </div>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="js/auth.js"></script>
</body>
</html>
