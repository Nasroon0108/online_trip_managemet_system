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

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 animate-slide-up">
    <div>
        <h2 class="fw-bold mb-1"><i class="fa-solid fa-chart-line text-primary me-2"></i>Admin Dashboard</h2>
        <p class="text-muted mb-0">Reports and management overview for TripEase.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;font-size:1.4rem;flex-shrink:0;">
                        <i class="fa-solid fa-money-bill-trend-up"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Revenue</div>
                        <div class="fs-4 fw-bold text-dark">Rs. <?= number_format($stats["revenue"], 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;font-size:1.4rem;flex-shrink:0;">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total Bookings</div>
                        <div class="fs-4 fw-bold text-dark"><?= $stats["bookings"] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;font-size:1.4rem;flex-shrink:0;">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Travelers</div>
                        <div class="fs-4 fw-bold text-dark"><?= $stats["travelers"] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-modern h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;font-size:1.4rem;flex-shrink:0;">
                        <i class="fa-solid fa-cubes"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Active Packages</div>
                        <div class="fs-4 fw-bold text-dark"><?= $stats["active_packages"] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small">Pending</div>
                <div class="fs-3 fw-semibold"><?= $stats["pending_bookings"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small">Confirmed</div>
                <div class="fs-3 fw-semibold"><?= $stats["confirmed_bookings"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small">Completed</div>
                <div class="fs-3 fw-semibold"><?= $stats["completed_bookings"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100 border-start border-4 border-danger">
            <div class="card-body">
                <div class="text-muted small">Cancelled</div>
                <div class="fs-3 fw-semibold"><?= $stats["cancelled_bookings"] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Popular Destinations</h5>
                <?php if ($popularDestinations): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Destination</th>
                                    <th>Country</th>
                                    <th>Bookings</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($popularDestinations as $destination): ?>
                                <tr>
                                    <td><?= htmlspecialchars($destination["name"]) ?></td>
                                    <td><?= htmlspecialchars($destination["country"]) ?></td>
                                    <td><?= (int)$destination["booking_count"] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No booking data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Top Packages</h5>
                <?php if ($topPackages): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Bookings</th>
                                    <th>Avg Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($topPackages as $pkg): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pkg["title"]) ?></td>
                                    <td><?= (int)$pkg["booking_count"] ?></td>
                                    <td><?= $pkg["avg_rating"] > 0 ? number_format((float)$pkg["avg_rating"], 1) . " / 5" : "—" ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No package data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Destination Management</h5>
                <p class="text-muted">Create, edit, activate, and deactivate destinations used by travel packages.</p>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>">Manage Destinations</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Package Management</h5>
                <p class="text-muted">Create and maintain packages, assign destinations, and control availability.</p>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Manage Packages</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Booking Management</h5>
                <p class="text-muted">Review booking and payment statuses, then cancel or complete trips as needed.</p>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php')) ?>">Manage Bookings</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">User Management</h5>
                <p class="text-muted">Activate or deactivate traveler accounts and review registered users.</p>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/users/list.php')) ?>">Manage Users</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
