<?php
require_once __DIR__ . "/includes/header.php";
?>

<div class="hero-section mb-5 animate-slide-up">
    <div class="container-fluid py-3">
        <span class="badge bg-primary-subtle text-primary mb-3 px-3 py-2 rounded-pill fw-semibold">
            <i class="fa-solid fa-earth-americas me-1"></i> Escape the Ordinary
        </span>
        <h1 class="hero-title display-5 mb-3">Explore the World with TripEase</h1>
        <p class="col-md-8 fs-5 text-muted mb-4">
            Browse premium destinations, view detailed day-by-day itineraries, book packages instantly, pay securely in sandbox mode, and share your experiences.
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!isLoggedIn()): ?>
                <a class="btn btn-primary btn-lg px-4" href="<?= htmlspecialchars(appUrl('/auth/register.php')) ?>">
                    <i class="fa-solid fa-circle-user me-1"></i> Get Started
                </a>
                <a class="btn btn-outline-secondary btn-lg px-4" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                </a>
            <?php else: ?>
                <a class="btn btn-primary btn-lg px-4" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">
                    <i class="fa-solid fa-compass me-1"></i> View Packages
                </a>
                <?php if (isAdmin()): ?>
                    <a class="btn btn-outline-primary btn-lg px-4" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">
                        <i class="fa-solid fa-chart-line me-1"></i> Admin Dashboard
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4 mb-3">
    <div class="col-md-4">
        <div class="card card-modern h-100 p-3">
            <div class="card-body">
                <div class="feature-icon-modern">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h5 class="fw-bold mb-2">1. Explore Packages</h5>
                <p class="text-muted mb-0">View beautiful destinations, schedules, traveler ratings, and budget-friendly pricing before booking.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-modern h-100 p-3">
            <div class="card-body">
                <div class="feature-icon-modern">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <h5 class="fw-bold mb-2">2. Book & Pay</h5>
                <p class="text-muted mb-0">Select your package, enter traveler details, complete a sandbox payment, and instantly view details in your account.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-modern h-100 p-3">
            <div class="card-body">
                <div class="feature-icon-modern">
                    <i class="fa-solid fa-star"></i>
                </div>
                <h5 class="fw-bold mb-2">3. Review Trips</h5>
                <p class="text-muted mb-0">After completing your journey, share ratings and feedback to help fellow travelers choose their next trip.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
