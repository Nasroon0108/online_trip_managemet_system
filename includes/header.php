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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(appUrl('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light navbar-glass sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= htmlspecialchars(appUrl('/index.php')) ?>">
            <i class="fa-solid fa-compass text-primary me-2"></i>
            <span>TripEase</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">
                            <i class="fa-solid fa-plane-departure me-1"></i> Packages
                        </a>
                    </li>
                    <?php if (!isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>">
                                <i class="fa-solid fa-suitcase me-1"></i> My Bookings
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars(appUrl('/profile/edit.php')) ?>">
                            <i class="fa-solid fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-user-gear me-1"></i> Admin Portal
                            </a>
                            <ul class="dropdown-menu border-0 shadow-sm rounded-3 mt-2">
                                <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>"><i class="fa-solid fa-chart-line me-2 text-muted"></i>Dashboard</a></li>
                                <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>"><i class="fa-solid fa-map-location-dot me-2 text-muted"></i>Destinations</a></li>
                                <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>"><i class="fa-solid fa-cubes me-2 text-muted"></i>Manage Packages</a></li>
                                <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php')) ?>"><i class="fa-solid fa-receipt me-2 text-muted"></i>Manage Bookings</a></li>
                                <li><a class="dropdown-item py-2" href="<?= htmlspecialchars(appUrl('/admin/users/list.php')) ?>"><i class="fa-solid fa-users me-2 text-muted"></i>Manage Users</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if (isLoggedIn()): ?>
                    <span class="text-dark me-2 small align-self-center">
                        <i class="fa-solid fa-circle-user text-primary me-1"></i>
                        <strong><?= htmlspecialchars($_SESSION["user_name"] ?? "User") ?></strong>
                        <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.75rem;"><?= htmlspecialchars(currentUserRole()) ?></span>
                    </span>
                    <a class="btn btn-outline-danger btn-sm" href="<?= htmlspecialchars(appUrl('/auth/logout.php')) ?>">
                        <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                    </a>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                    </a>
                    <a class="btn btn-primary btn-sm text-white" href="<?= htmlspecialchars(appUrl('/auth/register.php')) ?>">
                        <i class="fa-solid fa-user-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php $flash = consumeFlash(); ?>
<?php if ($flash): ?>
    <?php 
    $alertClass = match($flash["type"]) {
        "success" => "alert-success",
        "danger" => "alert-danger",
        "warning" => "alert-warning",
        default => "alert-info"
    };
    $alertIcon = match($flash["type"]) {
        "success" => "fa-circle-check text-success",
        "danger" => "fa-circle-xmark text-danger",
        "warning" => "fa-triangle-exclamation text-warning",
        default => "fa-circle-info text-info"
    };
    ?>
    <div class="container mt-3">
        <div class="alert <?= $alertClass ?> d-flex align-items-center shadow-sm border-0 rounded-4 p-3 animate-slide-up" role="alert">
            <i class="fa-solid <?= $alertIcon ?> fs-4 me-3"></i>
            <div>
                <?= htmlspecialchars($flash["message"]) ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<main class="container pb-5">
