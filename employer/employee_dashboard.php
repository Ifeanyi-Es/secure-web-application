<?php
session_start();
// Check if user is logged in and is an employer
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'employer') {
    header("location: Login.php");
    exit;
}

require '../includes/db_connect.php';

// 1. KPI Data
$total_applicants = 0;
$interviewing = 0;
$successful = 0;

// Total Applicants
// Get total count of application IDs
if ($result = $conn->query("SELECT COUNT(id) as count FROM applications")) {
    $row = $result->fetch_assoc();
    $total_applicants = $row['count'];
}

// Interviewing
// Get count of application IDs where status is 'Interview'
if ($result = $conn->query("SELECT COUNT(id) as count FROM applications WHERE status = 'Interview'")) {
    $row = $result->fetch_assoc();
    $interviewing = $row['count'];
}

// Successful (Accepted)
if ($result = $conn->query("SELECT COUNT(id) as count FROM applications WHERE status = 'Accepted' OR status = 'Offer'")) {
    $row = $result->fetch_assoc();
    $successful = $row['count'];
}

// 2. Patient Data for Signed-in Staff
$my_patients = [];
$email = $_SESSION['email'];

// First get staff ID
$staff_id = null;
$stmt = $conn->prepare("SELECT id FROM staff WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $staff_id = $row['id'];
}
$stmt->close();

if ($staff_id) {
    // Get patients assigned to this staff member (either as doctor or nurse)
    $sql = "SELECT 
                p.id, p.first_name, p.last_name, p.diagnosis, p.location, p.age,
                CONCAT(d.first_name, ' ', d.last_name) as doctor_assigned,
                CONCAT(n.first_name, ' ', n.last_name) as nurse_assigned
            FROM patients p
            LEFT JOIN staff d ON p.doctor_id = d.id
            LEFT JOIN staff n ON p.nurse_id = n.id
            WHERE p.doctor_id = ? OR p.nurse_id = ?";
            
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $staff_id, $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $my_patients[] = $row;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Patient Tracker — Lancashire Medicare</title>

  <!-- Bootstrap 5 & DataTables & Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
  <link href="../css/employee_dashboard.css" rel="stylesheet">
    <script src="../js/auth.js" defer></script>

</head>
<body>

  <header class="topbar">
    <div class="d-flex align-items-center">
      <a class="navbar-brand brand me-3" href="../index.php">Lancashire Medicare</a>
    </div>

    <div class="ms-auto d-flex align-items-center gap-3">
      <!-- profile & settings on the right -->
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
          <li> <a class="dropdown-item logoutBtn" href="../logout.php"> <i class="bi bi-box-arrow-right me-2"></i>Sign out </a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="container-fluid layout py-3">
    <div class="row g-0">
         <!-- Sidebar (minimal) -->
      <aside class="col-12 col-md-2 p-4 h-100" id="sidebar">
        <div class="d-flex flex-column h-100">
          <nav class="nav nav-pills flex-column mb-3" aria-label="Main navigation">
            <a class="nav-link active d-flex align-items-center mb-2" href="employer/employee_dashboard.php">
              <i class="bi bi-speedometer2 me-2"></i>
              <span>Dashboard</span>
            </a>

            

            <a class="nav-link d-flex align-items-center mb-2" href="employer_internship_tracker.php" aria-current="page">
              <i class="bi bi-people-fill me-2"></i>
              <span>Internships Tracker</span>
            </a>

            <a class="nav-link d-flex align-items-center mb-2" href="employer_patient_tracker.php" aria-current="page">
              <i class="bi bi-people-fill me-2"></i>
              <span>Patient tracker</span>
            </a>
            
            <a class="nav-link d-flex align-items-center mb-2" href="../events.html">
              <i class="bi bi-calendar-event me-2"></i>
              <span>Events &amp; Schedule</span>
            </a>
          </nav>

          
        </div>
      </aside> <!-- sidebar end here-->

      <!-- Main content -->
      <section class="col-12 col-md-10 p-4">
           <h5 class="text-center justify-content-center mb-2">Internship Application summary</h5>

        <div class="card-outer">

          <!-- KPI cards with small donut visuals -->
          <div class="kpi-grid">
            <div class="kpi">
              <div style="width:56px;height:56px;">
                <canvas id="kpiTotalChart" width="56" height="56" aria-hidden="true"></canvas>
              </div>
              <div>
                <div class="meta">Interviewing</div>
                <div class="value" id="interviewing"><?php echo $interviewing; ?></div>
              </div>
            </div>

            <div class="kpi">
              <div style="width:56px;height:56px;">
                <canvas id="kpiInChart" width="56" height="56" aria-hidden="true"></canvas>
              </div>
              <div>
                <div class="meta">successful</div>
                <div class="value" id="successful"><?php echo $successful; ?></div>
              </div>
            </div>

            <div class="kpi">
              <div style="width:56px;height:56px;">
                <canvas id="kpiOccupancy" width="56" height="56" aria-hidden="true"></canvas>
              </div>
              <div>
                <div class="meta">Total Applicants</div>
                <div class="value" id="totalApplicants"><?php echo $total_applicants; ?></div>
              </div>
            </div>
          </div>
   
          <!-- Table controls -->
           <h5 class="text-center justify-content-center mt-5">Patient Overview</h5>

          <div class="controls">
            
            <input id="tableSearch" class="form-control form-control-sm search-sm" type="search" placeholder="Search patients" aria-label="Search patients">
          </div>
          <!-- Patient table -->
          <div class="table-responsive mt-3">
            <table id="employee_dashboard_table" class="table table-striped table-hover table-bordered align-middle" style="width:100%">
              <thead class="table-light">
                <tr>
                  <th style="width:48px">#</th>
                  <th>Name</th>
                  <th>Diagnosis</th>
                  <th>Doctor Assigned</th>
                  <th>Nurse Assigned</th>
                  <th>Support</th>
                  <th>Location</th>
                  <th>Age</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($my_patients)): ?>
                    <?php foreach ($my_patients as $p): ?>
                    <tr>
                      <td><?php echo $p['id']; ?></td>
                      <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                      <td><?php echo htmlspecialchars($p['diagnosis']); ?></td>
                      <td><?php echo htmlspecialchars($p['doctor_assigned'] ?? 'Unassigned'); ?></td>
                      <td><?php echo htmlspecialchars($p['nurse_assigned'] ?? 'Unassigned'); ?></td>
                      <td><?php echo htmlspecialchars($p['support_assigned'] ?? 'Unassigned'); ?></td>
                      <td><?php echo htmlspecialchars($p['location']); ?></td>
                      <td><?php echo htmlspecialchars($p['age']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
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

  <!-- JS libraries -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/employee_dashboard.js?v=<?php echo time(); ?>"></script>

</body>
</html>
