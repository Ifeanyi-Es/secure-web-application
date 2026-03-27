<?php
session_start();
require_once 'includes/db_connect.php';

// Fetch job roles
$job_roles = [];
$sql = "SELECT * FROM job_roles ORDER BY id DESC LIMIT 6";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $job_roles[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Lancashire Medicare</title>

  <!-- Performance: preconnect to CDN and use a single modern Bootstrap + icons -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
  
  integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">


  <link href="style.css" rel="stylesheet">
  <link href="css/index.css" rel="stylesheet">
  <style>
   
  </style>
</head>
<body>


  <!-- Navbar: brand left, items right, accessible collapse -->
  <header>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
      <div class="container-fluid">
        <a class="navbar-brand site-brand" href="index.php">
          <i class="bi bi-heart-pulse-fill me-1"></i> Lancashire Medicare
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
          <ul class="navbar-nav ms-auto align-items-lg-center">
            <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="jobroles.php">Internship Roles</a></li>
            <li class="nav-item"><a class="nav-link" href="About.html">About</a></li>
            
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <!-- Logged in user menu -->
            <li class="nav-item ms-2">
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
                        <li><a class="dropdown-item logoutBtn" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                    </ul>
                </div>
            </li>
            <?php else: ?>
            <li class="nav-item ms-2">
              <a class="btn btn-outline-primary btn-sm" href="Login.php">Sign in</a>
            </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <!-- Main -->
  <main id="main" class="container-fluid">
    <section class="hero d-flex align-items-center">
      <div class="container-lg py-5 text-center">
        <h1 class="display-5 fw-semibold text-primary mb-2">Find Internship Opportunities</h1>
        <p class="lead text-muted mb-4">Connecting users with quality internships and healthcare roles.</p>

        <form class="row g-2 justify-content-center" id="search_bar" role="search" aria-label="Search internships">
          <div class="col-12 col-sm-8 col-md-6 col-lg-5">
            <label for="q" class="visually-hidden">Search internships</label>
            <div class="input-group shadow-sm">
              <input id="q" name="q" class="form-control form-control-lg" type="search" placeholder="Search internships, roles, locations..." aria-label="Search internships">
              <button class="btn btn-primary" type="submit" aria-label="Search">Search</button>
            </div>
          </div>
          <div class="col-12 mt-2">
            <!-- Browse now navigates to the jobs section -->
            <a id="browseBtn" class="btn btn-outline-secondary" href="#jobs" role="button">Browse all internships</a>
          </div>
        </form>
      </div>
    </section>

    <section class="container-lg py-5">
      <h2 class="h4 text-center text-primary mb-4">Featured Services</h2>

      <!-- Responsive card grid (row-cols used for modern responsive layout) -->
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <div class="col">
          <article class="card h-100 shadow-sm">
            <img src="images\book.jpg" loading="lazy" class="card-img-top" alt="Book an appointment">
            <div class="card-body d-flex flex-column">
              <h3 class="h6 card-title">Book an Appointment</h3>
              <p class="card-text text-muted flex-grow-1">Quickly schedule clinical appointments with available providers.</p>
              <div class="mt-3">
                <a href="#" class="btn btn-sm btn-primary disabled">Book now</a>
                <a href="#" class="btn btn-sm btn-link text-decoration-none">Learn more</a>
              </div>
            </div>
          </article>
        </div>

        <div class="col">
          <article class="card h-100 shadow-sm">
            <img src="images\bill.jpg" loading="lazy" class="card-img-top" alt="Pay a bill">
            <div class="card-body d-flex flex-column">
              <h3 class="h6 card-title">Pay a Bill</h3>
              <p class="card-text text-muted flex-grow-1">Securely manage invoices and payments online.</p>
              <div class="mt-3">
                <a href="#" class="btn btn-sm btn-primary disabled">Pay now</a>
                <a href="#" class="btn btn-sm btn-link">Billing FAQ</a>
              </div>
            </div>
          </article>
        </div>

        <div class="col">
          <article class="card h-100 shadow-sm">
            <img src="images\ambulance.jpg" loading="lazy" class="card-img-top" alt="Ambulance emergency">
            <div class="card-body d-flex flex-column">
              <h3 class="h6 card-title">Emergency Help</h3>
              <p class="card-text text-muted flex-grow-1">Immediate assistance and emergency contact details.</p>
              <div class="mt-3">
                <a href="tel:0123456789" class="btn btn-sm btn-danger">Call Emergency</a>
                <a href="#" class="btn btn-sm btn-link">More info</a>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- NEW: Open Internships (job cards) - added below Featured Services -->
    <section id="jobs" class="container-lg py-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 text-primary mb-0">Open Internships</h2>
        <small class="text-muted">Showing <span id="visibleCount">0</span> of <span id="totalCount">0</span></small>
      </div>

      <div id="jobsGrid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($job_roles as $job): ?>
        <div class="col">
          <article class="card job-card h-100 shadow-sm" 
                   data-title="<?php echo htmlspecialchars($job['title']); ?>" 
                   data-location="<?php echo htmlspecialchars($job['location']); ?>" 
                   data-type="<?php echo htmlspecialchars($job['type']); ?>" 
                   data-tags="<?php echo htmlspecialchars($job['title']); ?>">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start">
                <h3 class="h6 mb-1"><?php echo htmlspecialchars($job['title']); ?></h3>
                <span class="badge-role"><?php echo htmlspecialchars($job['type']); ?></span>
              </div>
              <div class="job-meta"><?php echo htmlspecialchars($job['location']); ?> • <?php echo htmlspecialchars($job['company']); ?> • Deadline: <?php echo date('d/m/Y', strtotime($job['deadline'])); ?></div>
              <p class="card-text text-muted mb-2"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></p>
              <div class="mt-auto d-flex gap-2">
                <a href="StudentFormSubmit.php?role=<?php echo urlencode($job['title']); ?>" class="btn btn-sm btn-primary">Apply</a>
                <button class="btn btn-sm btn-outline-secondary viewBtn" type="button" data-bs-toggle="modal" data-bs-target="#jobModal" 
                        data-jobid="<?php echo $job['id']; ?>"
                        data-desc="<?php echo htmlspecialchars($job['description']); ?>"
                        data-req="<?php echo htmlspecialchars($job['requirements']); ?>"
                        data-resp="<?php echo htmlspecialchars($job['responsibilities']); ?>"
                        >Details</button>
              </div>
            </div>
          </article>
        </div>
        <?php endforeach; ?>
        <?php if (empty($job_roles)): ?>
            <div class="col-12 text-center text-muted">No open positions at the moment.</div>
        <?php endif; ?>
      </div>
    </section>

    <section class="container-lg pb-5">
      <h2 class="h4 text-center mb-4">How it works</h2>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <!-- repeat cards, use same card structure to keep consistent height -->
        <div class="col ">
          <div class="card h-100">
            <img src="images\doctor1.jpg" loading="lazy" class="card-img-top" alt="Provider">
            <div class="card-body">
              <h3 class="h6">Find roles</h3>
              <p class="small text-muted">Search and filter internships based on your interests and location.</p>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="card h-100">
            <img src="images\intern.jpg" loading="lazy" class="card-img-top" alt="Provider">
            <div class="card-body">
              <h3 class="h6">Apply online</h3>
              <p class="small text-muted">Submit applications and track progress in your dashboard.</p>
            </div>
          </div>
        </div>

        <div class="col">
          <div class="card h-100">
            <img src="images\doctor2.jpg" loading="lazy" class="card-img-top" alt="Provider">
            <div class="card-body">
              <h3 class="h6">Start the internship</h3>
              <p class="small text-muted">Complete onboarding and begin your placement with support.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Job details modal (shared by job cards) -->
  <div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="jobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="jobModalLabel">Job details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="jobModalBody">Loading…</div>
        </div>
        <div class="modal-footer">
          <a id="modalApplyBtn" class="btn btn-primary" href="#">Apply</a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer: structured and responsive -->
  <footer class="bg-dark text-white mt-auto">
    <div class="container-lg py-5">
      <div class="row gy-4">
        <div class="col-lg-4">
          <a class="d-flex align-items-center site-brand text-white text-decoration-none mb-2" href="/">
            <svg width="40" height="40" viewBox="0 0 48 48" fill="none" class="me-2" aria-hidden="true" focusable="false">
              <path d="M23.3754 5.21913L39.3508 17H42V23H6V17H8.64928L23.3754 5.21913Z" fill="currentColor"/>
              <path d="M24 25C21.79 25 20 26.79 20 29V37H28V29C28 26.79 26.21 25 24 25Z" fill="currentColor"/>
            </svg>
            <span class="fs-5">Lancashire Medicare</span>
          </a>
          <p class="text-muted small">Preston — Committed to connecting talent with healthcare opportunities.</p>
        </div>

        <div class="col-6 col-lg-2">
          <h6 class="text-white">Links</h6>
          <ul class="list-unstyled">
            <li><a class="nav-link" href="/">Home</a></li>
            <li><a class="nav-link" href="jobroles.php">Internship Roles</a></li>
            <li><a class="nav-link" href="login">Login</a></li>
          </ul>
        </div>

        <div class="col-6 col-lg-2">
          <h6 class="text-white">Guides</h6>
          <ul class="list-unstyled">
            <li><a class="nav-link" href="#">Visit Us</a></li>
            <li><a class="nav-link" href="#">Donate</a></li>
          </ul>
        </div>

        <div class="col-lg-4">
          <h6 class="text-white">Newsletter</h6>
          <p class="small text-muted">Monthly digest of what's new and exciting from us.</p>
          <form class="d-flex gap-2" aria-label="Subscribe to newsletter">
            <label for="newsletter1" class="visually-hidden">Email address</label>
            <input id="newsletter1" type="email" class="form-control form-control-sm" placeholder="Email address" required>
            <button class="btn btn-primary btn-sm" type="submit">Subscribe</button>
          </form>
        </div>
      </div>

      <div class="text-center mt-4 small text-muted">© 2025 Lancashire Medicare. All rights reserved.</div>
    </div>
  </footer>

  <!-- Scripts: single modern Bootstrap bundle, defer loading for performance -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js" defer></script>

  <!-- Inline client-side search/filter + modal loader -->

  <script src="js/index.js" defer></script>
  <script src="js/script.js" defer></script>
</body>
</html>
