<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireAgent();
require_once __DIR__ . "/../includes/header.php";

$agentId = (int)$_SESSION["user_id"];

$statsStmt = $pdo->prepare(
    "SELECT
        COUNT(DISTINCT aa.package_id) AS assigned_packages,
        COUNT(DISTINCT i.itinerary_id) AS itinerary_items,
        COUNT(DISTINCT b.booking_id) AS related_bookings
     FROM agent_assignments aa
     LEFT JOIN itineraries i ON i.package_id = aa.package_id
     LEFT JOIN bookings b ON b.package_id = aa.package_id AND b.status IN ('confirmed', 'completed', 'pending')
     WHERE aa.agent_id = :agent_id"
);
$statsStmt->execute(["agent_id" => $agentId]);
$stats = $statsStmt->fetch() ?: [
    "assigned_packages" => 0,
    "itinerary_items" => 0,
    "related_bookings" => 0,
];

$packagesStmt = $pdo->prepare(
    "SELECT
        p.package_id,
        p.title,
        p.status,
        p.start_date,
        p.end_date,
        p.duration_days,
        GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS destination_names,
        COUNT(DISTINCT i.itinerary_id) AS itinerary_count
     FROM agent_assignments aa
     INNER JOIN packages p ON p.package_id = aa.package_id
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id
     LEFT JOIN itineraries i ON i.package_id = p.package_id
     WHERE aa.agent_id = :agent_id
     GROUP BY p.package_id, p.title, p.status, p.start_date, p.end_date, p.duration_days
     ORDER BY p.start_date ASC"
);
$packagesStmt->execute(["agent_id" => $agentId]);
$packages = $packagesStmt->fetchAll();
?>

<div class="page-header mb-4">
    <div>
        <h2 class="mb-1">Agent Workspace</h2>
        <p class="text-muted mb-0">Manage itineraries for packages assigned to you.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Assigned Packages</div>
            <div class="stat-value"><?= (int)$stats["assigned_packages"] ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Itinerary Items</div>
            <div class="stat-value"><?= (int)$stats["itinerary_items"] ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Related Bookings</div>
            <div class="stat-value"><?= (int)$stats["related_bookings"] ?></div>
        </div>
    </div>
</div>

<div class="card card-modern">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">My Assigned Packages</h5>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(appUrl('/agent/bookings.php')) ?>">Assigned Bookings</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/agent/packages.php')) ?>">View all</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Package</th>
                        <th>Destinations</th>
                        <th>Dates</th>
                        <th>Itinerary</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($package["title"]) ?></td>
                        <td><?= htmlspecialchars($package["destination_names"] ?? "—") ?></td>
                        <td class="small"><?= htmlspecialchars((string)$package["start_date"]) ?> → <?= htmlspecialchars((string)$package["end_date"]) ?></td>
                        <td><?= (int)$package["itinerary_count"] ?> items</td>
                        <td><span class="badge badge-soft"><?= htmlspecialchars($package["status"]) ?></span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/admin/itineraries/manage.php?package_id=' . (int)$package["package_id"])) ?>">
                                Manage Itinerary
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$packages): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No packages assigned yet. Ask an admin to assign packages to your agent account.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
