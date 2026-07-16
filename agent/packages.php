<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireAgent();
require_once __DIR__ . "/../includes/header.php";

$agentId = (int)$_SESSION["user_id"];

$packagesStmt = $pdo->prepare(
    "SELECT
        p.package_id,
        p.title,
        p.description,
        p.price,
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
     GROUP BY p.package_id, p.title, p.description, p.price, p.status, p.start_date, p.end_date, p.duration_days
     ORDER BY p.start_date ASC"
);
$packagesStmt->execute(["agent_id" => $agentId]);
$packages = $packagesStmt->fetchAll();
?>

<div class="page-header mb-4">
    <div>
        <h2 class="mb-1">Assigned Packages</h2>
        <p class="text-muted mb-0">Packages you are responsible for as a trip agent/guide.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/agent/index.php')) ?>">Back to Workspace</a>
</div>

<div class="row g-3">
<?php foreach ($packages as $package): ?>
    <div class="col-lg-6">
        <div class="card card-modern h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($package["title"]) ?></h5>
                    <span class="badge badge-soft"><?= htmlspecialchars($package["status"]) ?></span>
                </div>
                <p class="text-muted small mb-3"><?= htmlspecialchars(mb_strimwidth((string)$package["description"], 0, 140, "...")) ?></p>
                <div class="small text-muted mb-3">
                    <div><strong>Destinations:</strong> <?= htmlspecialchars($package["destination_names"] ?? "—") ?></div>
                    <div><strong>Duration:</strong> <?= (int)$package["duration_days"] ?> days</div>
                    <div><strong>Dates:</strong> <?= htmlspecialchars((string)$package["start_date"]) ?> to <?= htmlspecialchars((string)$package["end_date"]) ?></div>
                    <div><strong>Itinerary items:</strong> <?= (int)$package["itinerary_count"] ?></div>
                </div>
                <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/itineraries/manage.php?package_id=' . (int)$package["package_id"])) ?>">
                    Edit Itinerary
                </a>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$package["package_id"])) ?>">
                    Preview Package
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$packages): ?>
    <div class="col-12">
        <div class="card card-modern">
            <div class="card-body text-center text-muted py-5">
                No packages assigned to you yet.
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
