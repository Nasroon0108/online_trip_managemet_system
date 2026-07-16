<?php
declare(strict_types=1);
require_once __DIR__ . "/auth.php";

$currentUri = $_SERVER["REQUEST_URI"] ?? "";
$isLinkActive = function (string $path) use ($currentUri): string {
    return str_contains($currentUri, $path) ? "active" : "";
};
$useAppShell = isLoggedIn();
$homeHref = $useAppShell ? appUrl(dashboardPath()) : appUrl("/index.php");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TripEase — Online Trip Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(appUrl('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= $useAppShell ? "app-body" : "public-body" ?>">

<?php if (!$useAppShell): ?>
    <header class="public-topbar">
        <div class="container d-flex align-items-center justify-content-between py-3">
            <a class="public-brand text-decoration-none" href="<?= htmlspecialchars(appUrl('/index.php')) ?>">
                TripEase
            </a>
            <a class="btn btn-brand btn-sm px-3" href="<?= htmlspecialchars(appUrl('/auth/register.php')) ?>">Create account</a>
        </div>
    </header>

    <?php $flash = consumeFlash(); ?>
    <?php if ($flash): ?>
        <?php
        $alertClass = match ($flash["type"]) {
            "success" => "alert-success",
            "danger" => "alert-danger",
            "warning" => "alert-warning",
            default => "alert-info",
        };
        ?>
        <div class="container mt-3">
            <div class="alert <?= $alertClass ?> border-0 shadow-sm rounded-3 mb-0">
                <?= htmlspecialchars($flash["message"]) ?>
            </div>
        </div>
    <?php endif; ?>

    <main class="public-main">
<?php else: ?>
    <div class="sidebar-layout">
        <div class="mobile-top-bar w-100">
            <a class="sidebar-brand" href="<?= htmlspecialchars($homeHref) ?>">TripEase</a>
            <button class="btn btn-outline-secondary border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="fa-solid fa-bars fs-5"></i>
            </button>
        </div>

        <div class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
            <div class="sidebar-header d-flex justify-content-between align-items-center">
                <a class="sidebar-brand" id="sidebarMenuLabel" href="<?= htmlspecialchars($homeHref) ?>">TripEase</a>
                <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
            </div>

            <div class="sidebar-menu">
                <nav class="nav flex-column">
                    <?php if (isTraveler()): ?>
                        <div class="sidebar-heading">Traveler</div>
                        <a class="sidebar-nav-link <?= $isLinkActive('/trips/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">
                            <i class="fa-solid fa-map"></i> Browse Packages
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/bookings/') || $isLinkActive('/payments/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>">
                            <i class="fa-solid fa-ticket"></i> My Bookings
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/profile/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/profile/edit.php')) ?>">
                            <i class="fa-solid fa-user"></i> Profile
                        </a>
                    <?php endif; ?>

                    <?php if (isAgent()): ?>
                        <div class="sidebar-heading">Agent Workspace</div>
                        <a class="sidebar-nav-link <?= $isLinkActive('/agent/index.php') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/agent/index.php')) ?>">
                            <i class="fa-solid fa-briefcase"></i> Dashboard
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/agent/packages.php') || $isLinkActive('/admin/itineraries/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/agent/packages.php')) ?>">
                            <i class="fa-solid fa-route"></i> Assigned Packages
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/agent/bookings.php') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/agent/bookings.php')) ?>">
                            <i class="fa-solid fa-clipboard-check"></i> Assigned Bookings
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/trips/list.php') || $isLinkActive('/trips/view.php') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">
                            <i class="fa-solid fa-eye"></i> Preview Packages
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/profile/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/profile/edit.php')) ?>">
                            <i class="fa-solid fa-user"></i> Profile
                        </a>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                        <div class="sidebar-heading">Admin Console</div>
                        <a class="sidebar-nav-link <?= $isLinkActive('/admin/index.php') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/admin/destinations/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>">
                            <i class="fa-solid fa-location-dot"></i> Destinations
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/admin/packages/') || $isLinkActive('/admin/itineraries/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">
                            <i class="fa-solid fa-box-open"></i> Packages
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/admin/bookings/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php')) ?>">
                            <i class="fa-solid fa-clipboard-list"></i> Bookings
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/admin/users/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/admin/users/list.php')) ?>">
                            <i class="fa-solid fa-users"></i> Users & Roles
                        </a>
                        <div class="sidebar-heading">Quick Links</div>
                        <a class="sidebar-nav-link <?= $isLinkActive('/trips/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">
                            <i class="fa-solid fa-eye"></i> Preview Site
                        </a>
                        <a class="sidebar-nav-link <?= $isLinkActive('/profile/') ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('/profile/edit.php')) ?>">
                            <i class="fa-solid fa-user"></i> Profile
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="sidebar-profile">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-circle me-2"><?= strtoupper(substr($_SESSION["user_name"] ?? "U", 0, 1)) ?></div>
                    <div class="overflow-hidden">
                        <div class="fw-semibold text-truncate" style="max-width: 160px;"><?= htmlspecialchars($_SESSION["user_name"] ?? "User") ?></div>
                        <span class="badge badge-role badge-role-<?= htmlspecialchars(currentUserRole()) ?>"><?= htmlspecialchars(currentUserRole()) ?></span>
                    </div>
                </div>
                <a class="btn btn-outline-danger btn-sm sidebar-footer-btn" href="<?= htmlspecialchars(appUrl('/auth/logout.php')) ?>">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>

        <div class="main-content">
            <?php $flash = consumeFlash(); ?>
            <?php if ($flash): ?>
                <?php
                $alertClass = match ($flash["type"]) {
                    "success" => "alert-success",
                    "danger" => "alert-danger",
                    "warning" => "alert-warning",
                    default => "alert-info",
                };
                ?>
                <div class="container-fluid px-4 mt-4">
                    <div class="alert <?= $alertClass ?> border-0 shadow-sm rounded-3 mb-0">
                        <?= htmlspecialchars($flash["message"]) ?>
                    </div>
                </div>
            <?php endif; ?>
            <main class="container-fluid p-4 pb-5">
<?php endif; ?>
