<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

$packages = $pdo->query(
    "SELECT
        p.package_id,
        p.title,
        p.price,
        p.duration_days,
        p.available_slots,
        p.status,
        GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') AS destination_names
     FROM packages p
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id
     GROUP BY p.package_id, p.title, p.price, p.duration_days, p.available_slots, p.status
     ORDER BY p.created_at DESC, p.package_id DESC"
)->fetchAll();
?>

<div class="page-header">
    <div>
        <p class="page-kicker mb-1">Package management</p>
        <h2>Packages</h2>
        <p class="text-muted mb-0">Manage inventory, destinations, and day-by-day itineraries.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
        <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/packages/create.php')) ?>">
            <i class="fa-solid fa-plus me-1"></i> New package
        </a>
    </div>
</div>

<div class="card card-modern card-panel">
    <div class="card-body p-0">
        <div class="panel-toolbar">
            <div>
                <h5 class="mb-0">Package catalog</h5>
                <p class="text-muted small mb-0"><?= count($packages) ?> package<?= count($packages) === 1 ? "" : "s" ?></p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-app align-middle mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Destinations</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Slots</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($package["title"]) ?></td>
                        <td><?= htmlspecialchars($package["destination_names"] ?? "—") ?></td>
                        <td class="text-nowrap">Rs. <?= number_format((float)$package["price"], 2) ?></td>
                        <td class="text-nowrap"><?= (int)$package["duration_days"] ?> days</td>
                        <td><?= (int)$package["available_slots"] ?></td>
                        <td><span class="badge badge-soft"><?= htmlspecialchars($package["status"]) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/itineraries/manage.php?package_id=' . (int)$package["package_id"])) ?>">Itinerary</a>
                            <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/admin/packages/edit.php?id=' . (int)$package["package_id"])) ?>">Edit</a>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/admin/packages/toggle_status.php')) ?>" class="d-inline">
                                <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                                <input type="hidden" name="status" value="<?= $package["status"] === "active" ? "inactive" : "active" ?>">
                                <button class="btn btn-sm btn-outline-warning" type="submit">
                                    <?= $package["status"] === "active" ? "Deactivate" : "Activate" ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$packages): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">No packages yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
