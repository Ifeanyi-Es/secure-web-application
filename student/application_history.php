<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: Login.php");
    exit;
}

// Use email to identify the user because StudentFormSubmit.php stores email but not user_id
if (!isset($_SESSION['email'])) {
    // Fallback or error if email is missing from session
    $applications = [];
    $error_message = "User email not found in session.";
} else {
    $email = $_SESSION['email'];

    // We select jobRole as job_title. 
    // We join with job_roles table to get the actual category (type) and location
    $sql = "SELECT 
                a.jobRole as job_title, 
                j.type as category, 
                j.location as location, 
                a.status 
            FROM applications a
            LEFT JOIN job_roles j ON a.jobRole = j.title
            WHERE a.email = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $applications = [];
            while ($row = $result->fetch_assoc()) {
                $applications[] = $row;
            }
        } else {
            $applications = [];
            $error_message = "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $applications = [];
        // If table doesn't exist or other DB error
        $error_message = "Error preparing query: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Application history</title>

  <!-- Bootstrap 5 & DataTables -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
  <link href="../css/application_history.css" rel="stylesheet">
   <script src="../js/auth.js" defer></script>


</head>
<body>

  <header class="topbar">
    <a class="navbar-brand brand" href="index.php">Lancashire Medicare</a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="dropdown">
        <button class="btn d-flex align-items-center gap-2 border-0 bg-transparent py-1 px-2"
                id="userMenu"
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
          <li> <a class="dropdown-item logoutBtn" href="../logout.php"> <i class="bi bi-box-arrow-right me-2"></i>Sign out </a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="container-fluid layout">
    <div class="row g-0">
      <!-- Sidebar -->
      <aside class="col-12 col-md-2 sidebar d-flex flex-column">
        <nav class="nav flex-column mb-3" aria-label="Student navigation">
          <!-- Student dashboard removed -->
          <a class="nav-link btn btn-outline-secondary w-100 d-flex align-items-center mb-2" href="../jobroles.php">
            <i class="bi bi-journal-plus me-2"></i>Internship Roles
          </a>

          <a class="nav-link btn btn-outline-secondary w-100 d-flex align-items-center mb-2" href="StudentForm.php">
            <i class="bi bi-upload me-2"></i>Submit Application
          </a>

          <a class="nav-link btn btn-primary w-100 d-flex align-items-center mb-3 active" href="application_history.php">
            <i class="bi bi-journal-text me-2"></i>Application history
          </a>

          <a class="nav-link btn btn-outline-secondary w-100 d-flex align-items-center mb-2" href="../events.html">
            <i class="bi bi-calendar-event me-2"></i>Schedule Interview
          </a>
        </nav>

        <hr>

        <!-- Profile & Settings removed from sidebar per request -->

      </aside>

      <!-- Content -->
      <section class="col-12 col-md-10 p-4">
        <div class="card-outer">
          <h5 class="page-title">My Application history</h5>

          <div class="controls">
            <input id="tableSearch" class="form-control form-control-sm search-sm" type="search" placeholder="Search applications" aria-label="Search applications">
          </div>

          <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
              <?php echo htmlspecialchars($error_message); ?>
            </div>
          <?php endif; ?>

          <div class="table-responsive">
            <table id="employee_dashboard_table" class="table table-striped table-hover table-bordered align-middle text-center" style="width:100%">
              <thead class="table-light text-center">
                <tr>
                  <th scope="col" class="text-center" style="width:48px"></th>
                  <th scope="col" class="text-center">Job Title</th>
                  <th scope="col" class="text-center">Category</th>
                  <th scope="col" class="text-center">Location</th>
                  <th scope="col" class="text-center">Application Status</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $counter = 1;
                foreach ($applications as $app): 
                    $statusClass = '';
                    if ($app['status'] === 'Interview') {
                        $statusClass = 'text-success';
                    } elseif ($app['status'] === 'Rejected') {
                        $statusClass = 'text-primary';
                    }
                ?>
                <tr>
                  <td><?php echo $counter++; ?></td>
                  <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                  <td><?php echo htmlspecialchars($app['category']); ?></td>
                  <td><?php echo htmlspecialchars($app['location']); ?></td>
                  <td><span class="<?php echo $statusClass; ?> fw-bold"><?php echo htmlspecialchars($app['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </main>

  <footer class="bd-footer py-3 bg-dark text-center text-light">
    &copy; 2025 Lancashire Medicare
  </footer>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>

  <script>
    $(document).ready(function() {
      var table = $('#employee_dashboard_table').DataTable({
        responsive: true,
        paging: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        columnDefs: [{ orderable: false, targets: 0 }],
        dom: 't<"d-flex justify-content-between align-items-center mt-2"p>',
        language: {
          paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        }
      });

      $('#tableSearch').on('keyup', function() {
        table.search(this.value).draw();
      });
    });
  </script>
</body>
</html>
