<?php
session_start();
// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("location: Login.php");
    exit;
}

require '../includes/db_connect.php';

// 1. KPI: Total Applicants
$total_applicants = 0;
$result = $conn->query("SELECT COUNT(*) as c FROM applications");
if ($result && $row = $result->fetch_assoc()) {
    $total_applicants = $row['c'];
}

// 2. KPI: Open Roles
$open_roles = 0;
$result = $conn->query("SELECT COUNT(*) as c FROM job_roles");
if ($result && $row = $result->fetch_assoc()) {
    $open_roles = $row['c'];
}

// 3. KPI: Popular Category
$popular_category = "N/A";
$result = $conn->query("SELECT jobRole, COUNT(*) as c FROM applications GROUP BY jobRole ORDER BY c DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $popular_category = $row['jobRole'];
}

// 4. KPI: Avg Time (Mocked for now as we lack data)
$avg_time = "14d";

// 5. Chart: Applicants (Last 24 Hours)
$applicants_data = array_fill(0, 24, 0); // Initialize 24 hours
$months_labels = []; // Reusing variable name for chart labels (now hours)
for ($i = 23; $i >= 0; $i--) {
    $months_labels[] = date('H:00', strtotime("-$i hours"));
}

try {
    // Group by Hour for the last 24 hours
    $sql = "SELECT HOUR(created_at) as h, COUNT(*) as c 
            FROM applications 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
            GROUP BY h";
    $result = $conn->query($sql);
    if ($result) {
        $hour_counts = [];
        while($row = $result->fetch_assoc()) {
            $hour_counts[$row['h']] = $row['c'];
        }
        
        // Re-populate applicants_data based on current hour logic
        $applicants_data = [];
        for ($i = 23; $i >= 0; $i--) {
            $h = (int)date('G', strtotime("-$i hours")); // 0-23 format
            $applicants_data[] = $hour_counts[$h] ?? 0;
        }
    }
} catch (Exception $e) {
    // Column created_at likely missing, keep 0s
}

// 6. Chart: Category Distribution (Job Roles from job_roles table)
$cat_labels = [];
$cat_data = [];
try {
    // Count unique job titles/roles available in the database
    $result = $conn->query("SELECT title, COUNT(*) as c FROM job_roles GROUP BY title ORDER BY c DESC");
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $cat_labels[] = $row['title'];
            $cat_data[] = $row['c'];
        }
    }
} catch (Exception $e) {}

// 7. Chart: Open Roles by Location (Active roles only)
$loc_labels = [];
$loc_data = [];
try {
    // Count active roles (deadline >= today) grouped by location
    $result = $conn->query("SELECT location, COUNT(*) as c FROM job_roles WHERE deadline >= CURDATE() GROUP BY location LIMIT 5");
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $loc_labels[] = $row['location'];
            $loc_data[] = $row['c'];
        }
    }
} catch (Exception $e) {}

// 8. Table: Top 20 Patients (New Cases)
$patients = [];
$sql = "SELECT p.*, 
        CONCAT(d.first_name, ' ', d.last_name) as doctor_assigned,
        CONCAT(n.first_name, ' ', n.last_name) as nurse_assigned
        FROM patients p
        LEFT JOIN staff d ON p.doctor_id = d.id
        LEFT JOIN staff n ON p.nurse_id = n.id
        ORDER BY p.id DESC LIMIT 20";

try {
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    // Fallback if schema not updated (missing doctor_id/nurse_id)
    $sql_fallback = "SELECT * FROM patients ORDER BY id DESC LIMIT 20";
    $result = $conn->query($sql_fallback);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard — Lancashire Medicare</title>

  <!-- Single modern Bootstrap + DataTables CSS -->
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/admin_dashboard.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
   <script src="../js/auth.js" defer></script>


</head>
<body class="min-vh-100 d-flex flex-column">

  <!-- Top navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand text-primary fw-bold" href="../index.php">Lancashire Medicare</a>
      <h5 class="mx-auto d-block d-lg-block text-secondary mb-0" style="margin-left: 35% !important;">Administrator Dashboard</h5>

     <div class="d-flex align-items-center ms-auto">
    <div class="dropdown">

        <!-- User Avatar + Name -->
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

            <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['name'] . ' ' . $_SESSION['last_name']) . '&background=random'); ?>"
                 alt="User Avatar"
                 class="rounded-circle shadow-sm border border-2 border-white"
                 width="42"
                 height="42"
                 style="object-fit: cover;">

            <i class="bi bi-chevron-down text-secondary" style="font-size: 0.75rem;"></i>
        </button>

        <!-- Dropdown Menu -->
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
                <a class="dropdown-item" href="../Profile_page.php">
                    <i class="bi bi-person me-2"></i> Profile
                </a>
            </li>

            <li>
                <a class="dropdown-item" href="../settings.php">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>
                <a class="dropdown-item logoutBtn" href="../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Sign out
                </a>
            </li>
        </ul>

    </div>
</div>

    </div>
  </nav>

  <!-- Page body -->
  <div class="container-fluid flex-grow-1">
    <div class="row g-0">

      <!-- Sidebar -->
      <aside class="col-12 col-md-2 sidebar p-3">
        <nav class="nav flex-column">
          <a class="nav-link active fw-semibold mb-2" href="#"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
          <a class="nav-link mb-2" href="admin_patient_tracker.php"><i class="bi bi-journal-medical me-2"></i> Patient Tracker</a>
          <a class="nav-link mb-2" href="../jobroles.php"><i class="bi bi-briefcase me-2"></i> Internship Roles</a>
          <a class="nav-link mb-2" href="../employer/employer_internship_tracker.php"><i class="bi bi-people-fill me-2"></i> Internship Tracker</a>
          <a class="nav-link mb-2" href="admin_publish.php"><i class="bi bi-briefcase me-2"></i> Job Publisher</a>
          <a class="nav-link mb-2" href="../events.html"><i class="bi bi-calendar-event me-2"></i> Events</a>
         
        </nav>

        
      </aside>

      <!-- Main content -->
      <main class="col-12 col-md-10 p-4">

        <!-- Summary cards -->
        <div class="row g-3 mb-4">
          <div class="col-6 col-sm-6 col-md-3">
            <div class="card summary-card shadow-sm">
              <div class="card-body d-flex flex-column">
                <small class="text-muted">Applicants</small>
                <div class="d-flex align-items-center justify-content-between mt-2">
                  <h3 id="statApplicants" class="mb-0"><?php echo number_format($total_applicants); ?></h3>
                  <i class="bi bi-people-fill fs-3 text-primary"></i>
                </div>
                <small class="text-success">+12% since last month</small>
              </div>
            </div>
          </div>

          <div class="col-6 col-sm-6 col-md-3">
            <div class="card summary-card shadow-sm">
              <div class="card-body d-flex flex-column">
                <small class="text-muted">Open Roles</small>
                <div class="d-flex align-items-center justify-content-between mt-2">
                  <h3 id="statRoles" class="mb-0"><?php echo number_format($open_roles); ?></h3>
                  <i class="bi bi-briefcase-fill fs-3 text-info"></i>
                </div>
                <small class="text-muted">3 new this week</small>
              </div>
            </div>
          </div>

          <div class="col-6 col-sm-6 col-md-3">
            <div class="card summary-card shadow-sm">
              <div class="card-body d-flex flex-column">
                <small class="text-muted">Popular Category</small>
                <div class="d-flex align-items-center justify-content-between mt-2">
                  <h5 id="statCategory" class="mb-0"><?php echo htmlspecialchars($popular_category); ?></h5>
                  <i class="bi bi-heart-pulse-fill fs-3 text-danger"></i>
                </div>
                <small class="text-muted">Most applied category</small>
              </div>
            </div>
          </div>

          <div class="col-6 col-sm-6 col-md-3">
            <div class="card summary-card shadow-sm">
              <div class="card-body d-flex flex-column">
                <small class="text-muted">Avg Time to Fill</small>
                <div class="d-flex align-items-center justify-content-between mt-2">
                  <h3 id="statTime" class="mb-0"><?php echo $avg_time; ?></h3>
                  <i class="bi bi-clock-fill fs-3 text-warning"></i>
                </div>
                <small class="text-muted">Days</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts row -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-lg-6">
            <div class="card chart-card shadow-sm">
              <div class="card-body">
                <h6 class="card-title">Applicants (last 24 hours)</h6>
                <canvas id="applicantsChart" aria-label="Applicants chart" role="img"></canvas>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-3">
            <div class="card chart-card shadow-sm">
              <div class="card-body">
                <h6 class="card-title">Category Distribution (Job Roles)</h6>
                <canvas id="categoryChart" aria-label="Category distribution" role="img"></canvas>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-3">
            <div class="card chart-card shadow-sm">
              <div class="card-body">
                <h6 class="card-title">Open Roles by Location (Active)</h6>
                <canvas id="locationsChart" aria-label="Open roles by location" role="img"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Patient / Applicants table -->
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Recent Patients (New Cases)</h6>
              <div class="d-flex gap-2">
                <input id="tableSearch" type="search" class="form-control form-control-sm" placeholder="Filter table">
                <button id="exportBtn" class="btn btn-sm btn-outline-secondary">Export</button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-striped table-hover text-center" id="employee_dashboard_table" style="width:100%">
                <thead class="table-light text-center">
                  <tr>
                    <th style="text-align: center !important;"></th>
                    <th style="text-align: center !important;">Name</th>
                    <th style="text-align: center !important;">Diagnosis</th>
                    <th style="text-align: center !important;">Doctor Assigned</th>
                    <th style="text-align: center !important;">Nurse Assigned</th>
                    <th style="text-align: center !important;">Location</th>
                    <th style="text-align: center !important;">Age</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($patients as $p): ?>
                  <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['diagnosis']); ?></td>
                    <td><?php echo htmlspecialchars($p['doctor_assigned'] ?? 'Unassigned'); ?></td>
                    <td><?php echo htmlspecialchars($p['nurse_assigned'] ?? 'Unassigned'); ?></td>
                    <td><?php echo htmlspecialchars($p['location']); ?></td>
                    <td><?php echo htmlspecialchars($p['age']); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <footer class="bd-footer py-3 bg-dark text-center text-light mt-auto">
    &copy; 2025 Lancashire Medicare
  </footer>

  <!-- Scripts: jQuery, Bootstrap, DataTables, Chart.js -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Inject PHP data into JS variables -->
  <script>
    // Applicants Data
    const applicantsLabels = <?php echo json_encode($months_labels); ?>;
    const applicantsData = <?php echo json_encode($applicants_data); ?>;

    // Categories Data
    const categoryLabels = <?php echo json_encode($cat_labels); ?>;
    const categoryData = <?php echo json_encode($cat_data); ?>;

    // Locations Data
    const locationLabels = <?php echo json_encode($loc_labels); ?>;
    const locationData = <?php echo json_encode($loc_data); ?>;
  </script>

  <!-- Initialize Charts -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Applicants Chart
        const applicantsCtx = document.getElementById('applicantsChart').getContext('2d');
        new Chart(applicantsCtx, {
            type: 'line',
            data: {
                labels: applicantsLabels,
                datasets: [{
                    label: 'Applicants',
                    data: applicantsData,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                onClick: (e) => {
                    window.location.href = '../student/application_history.php';
                },
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                }
            }
        });

        // 2. Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const catColors = [
            '#0d6efd', '#6f42c1', '#20c997', '#ffc107', '#fd7e14', 
            '#dc3545', '#0dcaf0', '#6610f2', '#d63384', '#adb5bd'
        ];
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels.length ? categoryLabels : ['No Data'],
                datasets: [{
                    data: categoryData.length ? categoryData : [1],
                    backgroundColor: catColors
                }]
            },
            options: { 
                plugins: { legend: { position: 'bottom' } }, 
                responsive: true,
                onClick: (e, activeEls) => {
                    if (activeEls.length > 0) {
                        const index = activeEls[0].index;
                        const label = categoryLabels[index];
                        if (label && label !== 'No Data') {
                            window.location.href = `../jobroles.php?search=${encodeURIComponent(label)}`;
                        }
                    }
                },
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                }
            }
        });

        // 3. Locations Chart
        const locationsCtx = document.getElementById('locationsChart').getContext('2d');
        new Chart(locationsCtx, {
            type: 'bar',
            data: {
                labels: locationLabels,
                datasets: [{
                    label: 'Open roles',
                    data: locationData,
                    backgroundColor: '#17a2b8'
                }]
            },
            options: { 
                plugins: { legend: { display: false } }, 
                responsive: true,
                onClick: (e, activeEls) => {
                    if (activeEls.length > 0) {
                        const index = activeEls[0].index;
                        const label = locationLabels[index];
                        if (label) {
                            window.location.href = `../jobroles.php?search=${encodeURIComponent(label)}`;
                        }
                    }
                },
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                }
            }
        });
    });
  </script>

  <script src="../js/admin_dashboard.js" defer></script>
  <script src="../js/script.js" defer></script>
  
</body>
</html>
