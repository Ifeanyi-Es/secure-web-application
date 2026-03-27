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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_job') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $id = $_POST['id'] ?? '';
    $title = $_POST['title'];
    $company = $_POST['company'];
    $type = $_POST['type'];
    $deadline = $_POST['deadline'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $responsibilities = $_POST['responsibilities'];

    if (!empty($id)) {
        // Update existing job
        $stmt = $conn->prepare("UPDATE job_roles SET title=?, company=?, type=?, deadline=?, location=?, description=?, requirements=?, responsibilities=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $title, $company, $type, $deadline, $location, $description, $requirements, $responsibilities, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new job
        $stmt = $conn->prepare("INSERT INTO job_roles (title, company, type, deadline, location, description, requirements, responsibilities) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $title, $company, $type, $deadline, $location, $description, $requirements, $responsibilities);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_publish.php");
    exit;
}

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $id = $_POST['id'];
    if (!empty($id)) {
        $stmt = $conn->prepare("DELETE FROM job_roles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_publish.php");
    exit;
}

// Fetch Job Roles
$job_roles = [];
$result = $conn->query("SELECT * FROM job_roles ORDER BY deadline ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $job_roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Job Publisher — Lancashire Medicare</title>

  <!-- Bootstrap + DataTables + Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../css/admin_patient_tracker.css" rel="stylesheet"> <!-- Reusing existing CSS for layout -->
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>

  <!-- Top brand -->
  <div class="brand-bar d-flex align-items-center">
    <a class="navbar-brand fw-bold" href="index.php" style="color: var(--accent);">Lancashire Medicare</a>
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
          <a class="nav-link mb-2" href="admin_patient_tracker.php"><i class="bi bi-journal-plus me-2"></i> Patient Tracker</a>
          <a class="nav-link mb-2 active" href="admin_publish.php"><i class="bi bi-briefcase me-2"></i> Job Publisher</a>
          <a class="nav-link mb-2" href="../events.html"><i class="bi bi-calendar-event me-2"></i> Events</a>
        </nav>

        
      </aside>

      <!-- Main -->
      <main class="col-12 col-md-10 p-4">
        <div class="table-card">

          <!-- Centered title -->
          <div class="page-title">
            <h4>Job Roles Management</h4>
          </div>

          <!-- Controls: small search left, prominent add button right -->
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="controls">
              <input id="tableSearch" class="form-control form-control-sm search-small" type="search" placeholder="Search jobs" aria-label="Search jobs">
            </div>

            <div class="controls">
              <button id="addDataBtn" class="btn btn-primary btn-add btn-sm" title="Add Job Role" data-bs-toggle="modal" data-bs-target="#addDataModal">
                <i class="bi bi-plus-lg"></i>
                <span class="d-none d-sm-inline">Add Job</span>
              </button>
            </div>
          </div>

          <!-- Table -->
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle" id="Admin_job_table" style="width:100%">
              <thead class="table-light">
                <tr>
                  <th style="width:48px">ID</th>
                  <th>Title</th>
                  <th>Company</th>
                  <th>Type</th>
                  <th>Location</th>
                  <th>Deadline</th>
                  <th style="width:100px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($job_roles as $job): ?>
                <tr>
                  <td><?php echo $job['id']; ?></td>
                  <td><?php echo htmlspecialchars($job['title']); ?></td>
                  <td><?php echo htmlspecialchars($job['company']); ?></td>
                  <td><?php echo htmlspecialchars($job['type']); ?></td>
                  <td><?php echo htmlspecialchars($job['location']); ?></td>
                  <td><?php echo htmlspecialchars($job['deadline']); ?></td>
                  <td>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary edit-btn" 
                            data-id="<?php echo $job['id']; ?>"
                            data-title="<?php echo htmlspecialchars($job['title']); ?>"
                            data-company="<?php echo htmlspecialchars($job['company']); ?>"
                            data-type="<?php echo htmlspecialchars($job['type']); ?>"
                            data-deadline="<?php echo htmlspecialchars($job['deadline']); ?>"
                            data-location="<?php echo htmlspecialchars($job['location']); ?>"
                            data-description="<?php echo htmlspecialchars($job['description']); ?>"
                            data-requirements="<?php echo htmlspecialchars($job['requirements']); ?>"
                            data-responsibilities="<?php echo htmlspecialchars($job['responsibilities']); ?>"
                            data-bs-toggle="modal" data-bs-target="#addDataModal">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="admin_publish.php" onsubmit="return confirm('Are you sure you want to delete this job role?');" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="delete_job">
                            <input type="hidden" name="id" value="<?php echo $job['id']; ?>">
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <form id="addDataForm" class="modal-content" method="POST" action="admin_publish.php">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="action" value="save_job">
        <div class="modal-header">
          <h5 class="modal-title" id="addDataModalLabel">Add/Edit Job Role</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2 d-none">
            <label class="form-label small">ID</label>
            <input name="id" class="form-control form-control-sm">
          </div>
          <div class="row">
              <div class="col-6 mb-2">
                <label class="form-label small">Job Title</label>
                <input name="title" class="form-control form-control-sm" required>
              </div>
              <div class="col-6 mb-2">
                <label class="form-label small">Company</label>
                <input name="company" class="form-control form-control-sm" required>
              </div>
          </div>
          <div class="row">
              <div class="col-4 mb-2">
                <label class="form-label small">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                    <option value="Internship">Internship</option>
                </select>
              </div>
              <div class="col-4 mb-2">
                <label class="form-label small">Location</label>
                <input name="location" class="form-control form-control-sm" required>
              </div>
              <div class="col-4 mb-2">
                <label class="form-label small">Deadline</label>
                <input name="deadline" type="date" class="form-control form-control-sm" required>
              </div>
          </div>
          
          <div class="mb-2">
            <label class="form-label small">Description</label>
            <textarea name="description" class="form-control form-control-sm" rows="3" required></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label small">Requirements (HTML allowed, e.g. &lt;li&gt;Item&lt;/li&gt;)</label>
            <textarea name="requirements" class="form-control form-control-sm" rows="3"></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label small">Responsibilities (HTML allowed)</label>
            <textarea name="responsibilities" class="form-control form-control-sm" rows="3"></textarea>
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

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Init DataTable
      const table = $('#Admin_job_table').DataTable({
        responsive: true,
        pageLength: 10,
        lengthChange: false,
        ordering: true,
        order: [[5, 'asc']], // Order by deadline
        columnDefs: [{ orderable: false, targets: [6] }], // Disable sorting on Actions
        dom: '<"d-flex justify-content-end mb-2"p>t',
        language: {
          paginate: {
            previous: '<i class="bi bi-chevron-left"></i>',
            next: '<i class="bi bi-chevron-right"></i>'
          }
        }
      });

      // Small search wired to DataTable
      const tableSearch = document.getElementById('tableSearch');
      if (tableSearch) {
        tableSearch.addEventListener('input', function () {
          table.search(this.value).draw();
        });
      }

      // Add Data modal handling
      const addDataBtn = document.getElementById('addDataBtn');
      const addDataModalEl = document.getElementById('addDataModal');
      const addDataModal = new bootstrap.Modal(addDataModalEl);
      const addDataForm = document.getElementById('addDataForm');

      addDataBtn.addEventListener('click', function () {
        addDataForm.reset();
        addDataForm.querySelector('[name="id"]').value = ''; 
        document.getElementById('addDataModalLabel').textContent = 'Add Job Role';
        addDataModal.show();
      });

      // Handle Edit Button Click
      document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.edit-btn');
        if (btn) {
            const id = btn.getAttribute('data-id');
            const title = btn.getAttribute('data-title');
            const company = btn.getAttribute('data-company');
            const type = btn.getAttribute('data-type');
            const deadline = btn.getAttribute('data-deadline');
            const location = btn.getAttribute('data-location');
            const description = btn.getAttribute('data-description');
            const requirements = btn.getAttribute('data-requirements');
            const responsibilities = btn.getAttribute('data-responsibilities');

            // Populate Form
            addDataForm.querySelector('[name="id"]').value = id;
            addDataForm.querySelector('[name="title"]').value = title;
            addDataForm.querySelector('[name="company"]').value = company;
            addDataForm.querySelector('[name="type"]').value = type;
            addDataForm.querySelector('[name="deadline"]').value = deadline;
            addDataForm.querySelector('[name="location"]').value = location;
            addDataForm.querySelector('[name="description"]').value = description;
            addDataForm.querySelector('[name="requirements"]').value = requirements;
            addDataForm.querySelector('[name="responsibilities"]').value = responsibilities;

            document.getElementById('addDataModalLabel').textContent = 'Edit Job Role';
        }
      });
    });
  </script>
  <script src="../js/auth.js" defer></script>
  <script src="../js/script.js" defer></script>
</body>
</html>
