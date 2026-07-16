<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TripEase - Online Trip Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(appUrl('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= htmlspecialchars(appUrl('/index.php')) ?>">TripEase</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>">My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/profile/edit.php')) ?>">Profile</a></li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">Admin Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>">Destinations</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Manage Packages</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php')) ?>">Manage Bookings</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(appUrl('/admin/users/list.php')) ?>">Manage Users</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="d-flex">
                <?php if (isLoggedIn()): ?>
                    <span class="text-white me-3 small align-self-center">
                        <?= htmlspecialchars($_SESSION["user_name"] ?? "User") ?> (<?= htmlspecialchars(currentUserRole()) ?>)
                    </span>
                    <a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars(appUrl('/auth/logout.php')) ?>">Logout</a>
                <?php else: ?>
                    <a class="btn btn-light btn-sm me-2" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">Login</a>
                    <a class="btn btn-warning btn-sm" href="<?= htmlspecialchars(appUrl('/auth/register.php')) ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php $flash = consumeFlash(); ?>
<?php if ($flash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= htmlspecialchars($flash["type"]) ?> mb-0"><?= htmlspecialchars($flash["message"]) ?></div>
    </div>
<?php endif; ?>
<main class="container pb-4">
