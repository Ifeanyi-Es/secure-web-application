<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("location: Login.php");
    exit;
}

require '../includes/db_connect.php';

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_patient') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $id = $_POST['id'] ?? '';
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $age = $_POST['age'];
    $diagnosis = $_POST['diagnosis'];
    $location = $_POST['location'];
    
    $doctor_id = $_POST['doctor_id'] ?? '';
    $nurse_id = $_POST['nurse_id'] ?? '';

    // Check if patient exists by Name if ID is not provided
    if (empty($id)) {
        $stmt = $conn->prepare("SELECT id FROM patients WHERE first_name = ? AND last_name = ?");
        $stmt->bind_param("ss", $first_name, $last_name);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $id = $row['id']; // Found existing patient, switch to update mode
        }
        $stmt->close();
    }

    if (!empty($id)) {
        // Update existing patient
        $stmt = $conn->prepare("UPDATE patients SET first_name=?, last_name=?, age=?, diagnosis=?, location=?, doctor_id=?, nurse_id=? WHERE id=?");
        // Handle empty strings for IDs as NULL
        $d_id = !empty($doctor_id) ? $doctor_id : null;
        $n_id = !empty($nurse_id) ? $nurse_id : null;
        
        // Using 's' for all parameters allows NULLs to be passed correctly without strict integer casting issues
        $stmt->bind_param("ssissiii", $first_name, $last_name, $age, $diagnosis, $location, $d_id, $n_id, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new patient
        $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, age, diagnosis, location, doctor_id, nurse_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $d_id = !empty($doctor_id) ? $doctor_id : null;
        $n_id = !empty($nurse_id) ? $nurse_id : null;
        
        $stmt->bind_param("ssissii", $first_name, $last_name, $age, $diagnosis, $location, $d_id, $n_id);
        $stmt->execute();
        $id = $stmt->insert_id; // Get the new ID
        $stmt->close();
    }

    // (Old assignment logic removed)

    header("Location: admin_patient_tracker.php");
    exit;
}

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_patient') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $id = $_POST['id'];
    if (!empty($id)) {
        // Delete patient
        $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_patient_tracker.php");
    exit;
}

// Fetch Patients with Assigned Staff (Direct Columns)
$patients = [];
$sql = "SELECT p.*, 
        CONCAT(d.first_name, ' ', d.last_name) as doctor_assigned,
        CONCAT(n.first_name, ' ', n.last_name) as nurse_assigned
        FROM patients p
        LEFT JOIN staff d ON p.doctor_id = d.id
        LEFT JOIN staff n ON p.nurse_id = n.id";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch Staff for Dropdowns
$doctors = [];
$nurses = [];
$result = $conn->query("SELECT * FROM staff");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['role'] === 'Doctor') {
            $doctors[] = $row;
        } elseif ($row['role'] === 'Nurse') {
            $nurses[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Patient Tracker — Lancashire Medicare</title>

  <!-- Bootstrap + DataTables + Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/admin_patient_tracker.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>

  <!-- Top brand -->
  <div class="brand-bar d-flex align-items-center">
    <a class="navbar-brand fw-bold" href="../index.php" style="color: var(--accent);">Lancashire Medicare</a>
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
          <li> <a class="dropdown-item logoutBtn" href="../logout.php"> <i class="bi bi-box-arrow-right me-2"></i>Sign out </a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container-fluid layout">
    <div class="row g-0 min-vh-100">

      <!-- Sidebar -->
      <aside class="col-12 col-md-2 sidebar d-flex flex-column">
        
        <nav class="nav mb-3" aria-label="Main navigation">
          <a class="nav-link mb-2" href="Admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
          <a class="nav-link mb-2 active" href="admin_patient_tracker.php"><i class="bi bi-journal-plus me-2"></i> Patient Tracker</a>
          <a class="nav-link mb-2" href="../events.html"><i class="bi bi-calendar-event me-2"></i> Events</a>
        </nav>

        
      </aside>

      <!-- Main -->
      <main class="col-12 col-md-10 p-4">
        <div class="table-card">

          <!-- Centered title -->
          <div class="page-title">
            <h4>All Appointments</h4>
          </div>

          <!-- Controls: small search left, prominent add button right -->
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="controls">
              <input id="tableSearch" class="form-control form-control-sm search-small" type="search" placeholder="Search table" aria-label="Search table">
            </div>

            <div class="controls">
              <button id="addDataBtn" class="btn btn-primary btn-add btn-sm" title="Add appointment" data-bs-toggle="modal" data-bs-target="#addDataModal">
                <i class="bi bi-plus-lg"></i>
                <span class="d-none d-sm-inline">Add data</span>
              </button>
            </div>
          </div>

          <!-- Table -->
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle" id="Admin_patient_table" style="width:100%">
              <thead class="table-light">
                <tr>
                  <th style="width:48px">ID</th>
                  <th>First Name</th>
                  <th>Last Name</th>
                  <th>Diagnosis</th>
                  <th>Doctor Assigned</th>
                  <th>Nurse Assigned</th>
                  <th>Location</th>
                  <th>Age</th>
                  <th style="width:100px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($patients as $p): ?>
                <tr>
                  <td><?php echo $p['id']; ?></td>
                  <td><?php echo htmlspecialchars($p['first_name']); ?></td>
                  <td><?php echo htmlspecialchars($p['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($p['diagnosis']); ?></td>
                  <td><?php echo htmlspecialchars($p['doctor_assigned'] ?? 'Nil'); ?></td>
                  <td><?php echo htmlspecialchars($p['nurse_assigned'] ?? 'Nil'); ?></td>
                  <td><?php echo htmlspecialchars($p['location']); ?></td>
                  <td><?php echo htmlspecialchars($p['age']); ?></td>
                  <td>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                            data-id="<?php echo $p['id']; ?>"
                            data-first="<?php echo htmlspecialchars($p['first_name']); ?>"
                            data-last="<?php echo htmlspecialchars($p['last_name']); ?>"
                            data-diagnosis="<?php echo htmlspecialchars($p['diagnosis']); ?>"
                            data-location="<?php echo htmlspecialchars($p['location']); ?>"
                            data-age="<?php echo htmlspecialchars($p['age']); ?>"
                            data-doctor-id="<?php echo $p['doctor_id']; ?>"
                            data-nurse-id="<?php echo $p['nurse_id']; ?>"
                            data-bs-toggle="modal" data-bs-target="#addDataModal">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="admin_patient_tracker.php" onsubmit="return confirm('Are you sure you want to delete this patient?');" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="delete_patient">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>
      </main>
    </div>
  </div>

  <!-- Add Data Modal -->
  <div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form id="addDataForm" class="modal-content" method="POST" action="admin_patient_tracker.php">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="action" value="save_patient">
        <div class="modal-header">
          <h5 class="modal-title" id="addDataModalLabel">Add/Edit Patient</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">ID (Leave empty for new)</label>
            <input name="id" class="form-control form-control-sm" placeholder="Auto-generated if empty">
          </div>
          <div class="row">
              <div class="col-6 mb-2">
                <label class="form-label small">First Name</label>
                <input name="first_name" class="form-control form-control-sm" required>
              </div>
              <div class="col-6 mb-2">
                <label class="form-label small">Last Name</label>
                <input name="last_name" class="form-control form-control-sm" required>
              </div>
          </div>
          <div class="mb-2">
            <label class="form-label small">Diagnosis</label>
            <input name="diagnosis" class="form-control form-control-sm">
          </div>
          
          <div class="row">
              <div class="col-6 mb-2">
                <label class="form-label small">Assign Doctor</label>
                <select name="doctor_id" class="form-select form-select-sm">
                  <option value="" selected>Unassigned</option>
                  <?php foreach ($doctors as $doc): ?>
                    <option value="<?php echo $doc['id']; ?>">
                        <?php echo htmlspecialchars("Dr " . $doc['first_name'] . ' ' . $doc['last_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 mb-2">
                <label class="form-label small">Assign Nurse</label>
                <select name="nurse_id" class="form-select form-select-sm">
                  <option value="" selected>Unassigned</option>
                  <?php foreach ($nurses as $nurse): ?>
                    <option value="<?php echo $nurse['id']; ?>">
                        <?php echo htmlspecialchars("Nurse " . $nurse['first_name'] . ' ' . $nurse['last_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
          </div>

          <div class="mb-2">
            <label class="form-label small">Location</label>
            <input name="location" class="form-control form-control-sm">
          </div>
          <div class="mb-2">
            <label class="form-label small">Age</label>
            <input name="age" type="number" min="0" class="form-control form-control-sm">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save</button>
        </div>
      </form>
    </div>
  </div>

  <footer class="bd-footer py-3 mt-auto bg-dark text-center text-light">
    &copy; 2025 Lancashire Medicare
  </footer>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>


  <script src="../js/admin_patient_tracker.js?v=<?php echo time(); ?>" defer></script>
  <script src="../js/auth.js" defer></script>
  <script src="../js/script.js" defer></script>
</body>
</html>
