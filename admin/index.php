<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../includes/header.php";

$stats = [
    "destinations" => (int)$pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn(),
    "packages" => (int)$pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn(),
    "active_packages" => (int)$pdo->query("SELECT COUNT(*) FROM packages WHERE status = 'active'")->fetchColumn(),
    "bookings" => (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    "travelers" => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'traveler'")->fetchColumn(),
    "revenue" => (float)$pdo->query(
        "SELECT COALESCE(SUM(pay.amount), 0)
         FROM payments pay
         WHERE pay.status = 'success'"
    )->fetchColumn(),
    "confirmed_bookings" => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
    "completed_bookings" => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn(),
    "pending_bookings" => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    "cancelled_bookings" => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn(),
];

$popularDestinations = $pdo->query(
    "SELECT d.name, d.country, COUNT(DISTINCT b.booking_id) AS booking_count
     FROM destinations d
     INNER JOIN package_destinations pd ON pd.destination_id = d.destination_id
     INNER JOIN bookings b ON b.package_id = pd.package_id AND b.status IN ('confirmed', 'completed')
     GROUP BY d.destination_id, d.name, d.country
     ORDER BY booking_count DESC, d.name ASC
     LIMIT 5"
)->fetchAll();

$topPackages = $pdo->query(
    "SELECT p.title, COUNT(b.booking_id) AS booking_count, COALESCE(AVG(r.rating), 0) AS avg_rating
     FROM packages p
     LEFT JOIN bookings b ON b.package_id = p.package_id AND b.status IN ('confirmed', 'completed')
     LEFT JOIN reviews r ON r.package_id = p.package_id
     GROUP BY p.package_id, p.title
     ORDER BY booking_count DESC, avg_rating DESC
     LIMIT 5"
)->fetchAll();
?>

<div class="page-header">
    <div>
        <p class="page-kicker mb-1">Operations overview</p>
        <h2>Admin Dashboard</h2>
        <p class="text-muted mb-0">Platform health, booking status, and quick management links.</p>
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/packages/create.php')) ?>">
        <i class="fa-solid fa-plus me-1"></i> New Package
    </a>
</div>

<div class="row g-4 stats-row">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value" style="font-size:1.55rem;">Rs. <?= number_format($stats["revenue"], 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
            <div>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-value"><?= $stats["bookings"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="stat-label">Travelers</div>
                <div class="stat-value"><?= $stats["travelers"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
            <div>
                <div class="stat-label">Active Packages</div>
                <div class="stat-value"><?= $stats["active_packages"] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 stats-row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= $stats["pending_bookings"] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Confirmed</div>
            <div class="stat-value"><?= $stats["confirmed_bookings"] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?= $stats["completed_bookings"] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Cancelled</div>
            <div class="stat-value"><?= $stats["cancelled_bookings"] ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-modern card-panel h-100">
            <div class="card-body p-0">
                <div class="panel-toolbar">
                    <div>
                        <h5 class="mb-0">Popular destinations</h5>
                        <p class="text-muted small mb-0">Based on confirmed and completed bookings</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-app align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Destination</th>
                                <th>Country</th>
                                <th class="text-end">Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($popularDestinations as $destination): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($destination["name"]) ?></td>
                                <td><?= htmlspecialchars($destination["country"]) ?></td>
                                <td class="text-end"><?= (int)$destination["booking_count"] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$popularDestinations): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No booking data yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-modern card-panel h-100">
            <div class="card-body p-0">
                <div class="panel-toolbar">
                    <div>
                        <h5 class="mb-0">Top packages</h5>
                        <p class="text-muted small mb-0">Highest booking volume and ratings</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-app align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Bookings</th>
                                <th class="text-end">Avg rating</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topPackages as $pkg): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($pkg["title"]) ?></td>
                                <td><?= (int)$pkg["booking_count"] ?></td>
                                <td class="text-end"><?= $pkg["avg_rating"] > 0 ? number_format((float)$pkg["avg_rating"], 1) . " / 5" : "—" ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$topPackages): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No package data yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-4">
                <h5 class="mb-2">Destinations</h5>
                <p class="text-muted small mb-3"><?= $stats["destinations"] ?> total · create and activate locations used by packages.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-4">
                <h5 class="mb-2">Packages</h5>
                <p class="text-muted small mb-3"><?= $stats["packages"] ?> total · maintain inventory and itineraries.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-4">
                <h5 class="mb-2">Bookings</h5>
                <p class="text-muted small mb-3">Review payments and mark trips completed or cancelled.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php')) ?>">Manage</a>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php?payment=success')) ?>">Paid</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-4">
                <h5 class="mb-2">Users & roles</h5>
                <p class="text-muted small mb-3">Activate accounts and assign traveler or admin roles.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(appUrl('/admin/users/list.php')) ?>">Manage</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
