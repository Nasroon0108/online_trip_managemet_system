<?php
require_once __DIR__ . "/includes/header.php";
?>

<div class="p-4 p-md-5 mb-4 bg-white rounded-3 shadow-sm hero-card">
    <div class="container-fluid py-2">
        <h1 class="display-6 fw-bold">TripEase - Online Trip Management System</h1>
        <p class="col-md-8 fs-5 text-muted">
            Browse destinations, view itineraries, book packages, pay securely in sandbox mode, and leave reviews after your trip.
        </p>
        <?php if (!isLoggedIn()): ?>
            <a class="btn btn-primary btn-lg me-2" href="<?= htmlspecialchars(appUrl('/auth/register.php')) ?>">Get Started</a>
            <a class="btn btn-outline-secondary btn-lg" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">Login</a>
        <?php else: ?>
            <a class="btn btn-primary btn-lg me-2" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">View Packages</a>
            <?php if (isAdmin()): ?>
                <a class="btn btn-outline-primary btn-lg" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">Admin Dashboard</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="feature-icon mb-3">1</div>
                <h5>Explore Packages</h5>
                <p class="text-muted mb-0">View destinations, pricing, availability, and traveler ratings before you book.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="feature-icon mb-3">2</div>
                <h5>Book & Pay</h5>
                <p class="text-muted mb-0">Create bookings, complete mock payment, and track booking status from your account.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="feature-icon mb-3">3</div>
                <h5>Review Trips</h5>
                <p class="text-muted mb-0">After trip completion, leave a rating and comment to help other travelers choose.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
