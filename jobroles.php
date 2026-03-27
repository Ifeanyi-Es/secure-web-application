<?php
session_start();
require_once 'includes/db_connect.php';

// Fetch job roles from database
$sql = "SELECT * FROM job_roles ORDER BY deadline ASC";
$result = $conn->query($sql);
$job_roles = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $job_roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Internship Roles — Lancashire Medicare</title>

  <!-- Bootstrap 5, icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <link href="css/jobroles.css" rel="stylesheet">
</head>
<body>

  <header class="topbar">
    <div class="d-flex align-items-center">
      <a class="navbar-brand brand me-3" href="index.php">Lancashire Medicare</a>
    </div>


    <div class="ms-auto d-flex align-items-center gap-3">
      <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
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
          <li><a class="dropdown-item" href="Profile_page.php"><i class="bi bi-person me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="settings.html"><i class="bi bi-gear me-2"></i>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li> <a class="dropdown-item logoutBtn" href="#"> <i class="bi bi-box-arrow-right me-2"></i>Sign out </a></li>
        </ul>
      </div>
      <?php else: ?>
        <a href="Login.php" class="btn btn-primary btn-sm">Sign In</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="container-main">
          <h6 class="text-center text-muted mb-3">Available Internship Roles</h6>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="small-muted">Find internships by role, location or type.</div>
    </div>

    <div class="search-row">
      <input id="jobSearch" type="search" class="form-control form-control-md" placeholder="Search roles, locations, types..." aria-label="Search roles">
      <select id="filterType" class="form-select form-select-md" style="max-width:200px">
        <option value="">All types</option>
        <option value="Full-Time">Full-Time</option>
        <option value="Part-Time">Part-Time</option>
      </select>
    </div>

    <section class="job-grid" id="jobsList">
      <?php if (!empty($job_roles)): ?>
        <?php foreach ($job_roles as $job): ?>
          <article class="job-card p-3" data-title="<?php echo htmlspecialchars($job['title']); ?>" data-location="<?php echo htmlspecialchars($job['location']); ?>" data-type="<?php echo htmlspecialchars($job['type']); ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <h5 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                <div class="job-meta"><?php echo htmlspecialchars($job['company']); ?></div>
              </div>
              <div class="text-end">
                <span class="chip"><?php echo htmlspecialchars($job['type']); ?></span>
                <div class="small-muted mt-1">Deadline: <?php echo date('d/m/Y', strtotime($job['deadline'])); ?></div>
              </div>
              <div>
                <a class="btn btn-primary btn-sm apply-btn" href="StudentFormSubmit.php?role=<?php echo urlencode($job['title']); ?>" aria-label="Apply <?php echo htmlspecialchars($job['title']); ?>">Apply Now</a>
              </div>
            </div>

            <p class="mt-3 small-muted"><?php echo htmlspecialchars($job['description']); ?></p>
            <div class="job-meta">Location: <?php echo htmlspecialchars($job['location']); ?></div>

            <div class="job-footer">
              <div class="d-flex gap-2 align-items-start">
                <details>
                  <summary class="btn btn-outline-primary btn-sm">View details</summary>
                  <div class="mt-2 small-muted">
                    <?php if (!empty($job['requirements'])): ?>
                      <strong>Requirements:</strong>
                      <ul class="requirements">
                        <?php echo strip_tags($job['requirements'], '<ul><li><p><br><strong><em>'); ?>
                      </ul>
                    <?php endif; ?>
                    <?php if (!empty($job['responsibilities'])): ?>
                      <strong>Responsibilities:</strong>
                      <ul class="requirements">
                        <?php echo strip_tags($job['responsibilities'], '<ul><li><p><br><strong><em>'); ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                </details>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center text-muted">No job roles available at the moment.</p>
      <?php endif; ?>
    </section>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/jobroles.js"></script>
  <script src="js/script.js"></script>
  <script src="js/auth.js"></script>

 
</body>
</html>
