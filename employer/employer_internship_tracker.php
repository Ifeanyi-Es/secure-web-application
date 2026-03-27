<?php
session_start();
// Check if user is logged in and is an employer OR admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'employer' && $_SESSION['role'] !== 'admin')) {
    header("location: Login.php");
    exit;
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require '../includes/db_connect.php';

// Handle Status Update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $app_id = $_POST['id'] ?? '';
    $new_status = $_POST['status'] ?? '';
    
    if (!empty($app_id) && !empty($new_status)) {
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $app_id);
        if ($stmt->execute()) {
            // Success
        }
        $stmt->close();
    }
    // Redirect to avoid resubmission
    header("Location: employer_internship_tracker.php");
    exit;
}

$applications = [];
if ($conn) {
    $sql = "SELECT id, firstName, lastName, email, nationality, university, program, jobRole, status, resume_path, cover_letter_path FROM applications";
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $row['cv_url'] = 'download.php?type=resume&id=' . $row['id'];
            $row['cover_url'] = $row['cover_letter_path'] ? 'download.php?type=cover&id=' . $row['id'] : '#';
            $applications[] = $row;
        }
        $result->free();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Employer Tracker— Lancashire Medicare</title>

  <!-- Bootstrap 5, DataTables and Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
  <link href="../css/employee_patient_tracker.css" rel="stylesheet">
</head>
<body class="bg-light">

  <!-- Top navigation -->
  <nav class="navbar navbar-expand-md navbar-light bg-white border-bottom shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="../index.php">
        <span class="fw-bold">Lancashire Medicare</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="dropdown">
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
            <li><a class="dropdown-item" href="../Profile_page.php"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="../settings.html"><i class="bi bi-gear me-2"></i>Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item logoutBtn" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row">
      <!-- Offcanvas sidebar for small screens -->
      <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Menu</h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        
      </div>

      <!-- Sidebar (visible on md+) -->
      <aside class="col-12 col-md-3 col-lg-2 d-none d-md-block bg-white border-end vh-100 p-3">
        <nav class="nav nav-pills flex-column">
          <a class="btn btn-outline-success mb-2 disabled" href="employer_internship_tracker.php"><i class="bi bi-people-fill me-2"></i>Internship tracker</a><br>

          <a class="btn mb-2  btn-primary" href="../events.html"><i class="bi bi-calendar-event me-2 fw-bold"></i>Schedule an Interview</a>
        </nav>
      </aside>

      <!-- Main content -->
      <main class="col-12 col-md-9 col-lg-10 py-4">
        <div class="container-lg">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-1 mt-3">
                <h4 class="mb-0 " style="margin-left: 30%;">Internship Applications</h4>
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-secondary btn-sm" id="refreshBtn" data-bs-toggle="tooltip" title="Refresh table"><i class="bi bi-arrow-clockwise"></i></button>
                  <div class="input-group input-group-sm" style="width:220px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="tableSearch" class="form-control form-control-sm" placeholder="Search applicants">
                  </div>
                </div>
              </div>

              <div class="table-responsive mt-4">
                <table id="applicantsTable" class="table table-hover table-borderless align-middle mt-3">
                  <thead class="table-light text-center">
                    <tr>
                      <th class="text-center" style="width:48px"></th>
                      <th class="text-center">FirstName</th>
                      <th class="text-center">LastName</th>
                      <th class="text-center">Email</th>
                      <th class="text-center">University</th>
                      <th class="text-center">Role</th>
                      <th class="text-center">Status</th>
                      <th class="text-center" style="width:180px">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="appBody">
                    <?php foreach ($applications as $app): 
                        $pid = 'app_' . $app['id'];
                    ?>
                    <tr data-pid="<?php echo $pid; ?>" 
                        data-id="<?php echo $app['id']; ?>"
                        data-first="<?php echo htmlspecialchars($app['firstName']); ?>"
                        data-last="<?php echo htmlspecialchars($app['lastName']); ?>"
                        data-email="<?php echo htmlspecialchars($app['email']); ?>"
                        data-nation="<?php echo htmlspecialchars($app['nationality']); ?>"
                        data-univ="<?php echo htmlspecialchars($app['university']); ?>"
                        data-course="<?php echo htmlspecialchars($app['program']); ?>"
                        data-role="<?php echo htmlspecialchars($app['jobRole']); ?>"
                        data-status="<?php echo htmlspecialchars($app['status']); ?>"
                        data-cv="<?php echo htmlspecialchars($app['cv_url']); ?>"
                        data-cover="<?php echo htmlspecialchars($app['cover_url']); ?>">
                        
                        <td><?php echo $app['id']; ?></td>
                        <td><?php echo htmlspecialchars($app['firstName']); ?></td>
                        <td><?php echo htmlspecialchars($app['lastName']); ?></td>
                        <td><?php echo htmlspecialchars($app['email']); ?></td>
                        <td><?php echo htmlspecialchars($app['university']); ?></td>
                        <td><?php echo htmlspecialchars($app['jobRole']); ?></td>
                        <td class="status-cell">
                            <?php 
                                $status = $app['status'];
                                $colorClass = '';
                                if ($status === 'Interview') {
                                    $colorClass = 'text-success';
                                } elseif ($status === 'Rejected') {
                                    $colorClass = 'text-danger';
                                }
                                echo '<span class="' . $colorClass . ' fw-bold">' . htmlspecialchars($status) . '</span>';
                            ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group" aria-label="Actions">
                                <button class="btn btn-sm btn-outline-secondary viewBtn" data-pid="<?php echo $pid; ?>" data-bs-toggle="tooltip" title="View details"><i class="bi bi-eye"></i></button>
                                
                                <form method="POST" action="employer_internship_tracker.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                    <input type="hidden" name="status" value="Interview">
                                    <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Offer interview"><i class="bi bi-calendar-check"></i></button>
                                </form>

                                <form method="POST" action="employer_internship_tracker.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                    <input type="hidden" name="status" value="Rejected">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Reject"><i class="bi bi-x-circle"></i></button>
                                </form>

                                <a href="<?php echo htmlspecialchars($app['cv_url']); ?>" download class="btn btn-sm btn-outline-dark" data-bs-toggle="tooltip" title="Download CV"><i class="bi bi-file-earmark-person"></i></a>
                                <a href="<?php echo htmlspecialchars($app['cover_url']); ?>" download class="btn btn-sm btn-outline-dark" data-bs-toggle="tooltip" title="Download Cover"><i class="bi bi-file-earmark-text"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Activity / Notifications -->
          <div id="toastArea" class="position-fixed bottom-0 end-0 p-3" style="z-index:1080"></div>
        </div>

        <!-- Applicant Modal -->
        <div class="modal fade" id="applicantModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Applicant Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <dl class="row mb-0">
                  <dt class="col-sm-4">First Name</dt><dd class="col-sm-8" id="modalFirst"></dd>
                  <dt class="col-sm-4">Last Name</dt><dd class="col-sm-8" id="modalLast"></dd>
                  <dt class="col-sm-4">Email</dt><dd class="col-sm-8" id="modalEmail"></dd>
                  <dt class="col-sm-4">Nationality</dt><dd class="col-sm-8" id="modalNation"></dd>
                  <dt class="col-sm-4">University</dt><dd class="col-sm-8" id="modalUniv"></dd>
                  <dt class="col-sm-4">Course</dt><dd class="col-sm-8" id="modalCourse"></dd>
                  <dt class="col-sm-4">Desired Role</dt><dd class="col-sm-8" id="modalRole"></dd>
                  <dt class="col-sm-4">Status</dt><dd class="col-sm-8" id="modalStatus"></dd>
                  <dt class="col-sm-4">CV</dt><dd class="col-sm-8"><a href="#" id="modalCV" download>Download CV</a></dd>
                  <dt class="col-sm-4">Cover Letter</dt><dd class="col-sm-8"><a href="#" id="modalCover" download>Download Cover Letter</a></dd>
                </dl>
              </div>
              <div class="modal-footer">
                <form method="POST" action="employer_internship_tracker.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="modalAppId">
                    <input type="hidden" name="status" value="Interview">
                    <button type="submit" class="btn btn-outline-primary">Offer Interview</button>
                </form>
                <form method="POST" action="employer_internship_tracker.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="modalAppIdReject">
                    <input type="hidden" name="status" value="Rejected">
                    <button type="submit" class="btn btn-outline-danger">Reject</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <footer class="bd-footer py-3 bg-white text-center text-muted border-top mt-4">
    &copy; 2025 Lancashire Medicare
  </footer>

  <!-- JS libraries -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="../js/auth.js"></script>
  <script src="../js/employer_tracker.js?v=<?php echo time(); ?>"></script>
  <script src="../js/script.js"></script>

</body>
</html>
