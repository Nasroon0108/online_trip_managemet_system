<?php
require_once __DIR__ . "/includes/header.php";
?>

<div class="p-4 p-md-5 mb-4 bg-white rounded-3 shadow-sm">
    <div class="container-fluid py-2">
        <h1 class="display-6 fw-bold">Online Trip Management System</h1>
        <p class="col-md-8 fs-5 text-muted">
            Manage trips, register users, and book travel packages with a clean Bootstrap interface.
        </p>
        <?php if (!isLoggedIn()): ?>
            <a class="btn btn-primary btn-lg me-2" href="/auth/register.php">Get Started</a>
            <a class="btn btn-outline-secondary btn-lg" href="/auth/login.php">Login</a>
        <?php else: ?>
            <a class="btn btn-primary btn-lg" href="/trips/list.php">View Trips</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
