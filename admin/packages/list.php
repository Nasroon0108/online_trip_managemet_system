<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/header.php";
requireAdmin();

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Manage Packages</h3>
    <div>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">Dashboard</a>
        <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/packages/create.php')) ?>">+ New Package</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Destinations</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Slots</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($packages as $package): ?>
                <tr>
                    <td><?= htmlspecialchars($package["title"]) ?></td>
                    <td><?= htmlspecialchars($package["destination_names"] ?? "—") ?></td>
                    <td>Rs. <?= htmlspecialchars((string)$package["price"]) ?></td>
                    <td><?= htmlspecialchars((string)$package["duration_days"]) ?> days</td>
                    <td><?= htmlspecialchars((string)$package["available_slots"]) ?></td>
                    <td><?= htmlspecialchars($package["status"]) ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/itineraries/manage.php?package_id=' . (int)$package["package_id"])) ?>">Itinerary</a>
                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(appUrl('/admin/packages/edit.php?id=' . (int)$package["package_id"])) ?>">Edit</a>
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
                <tr><td colspan="7" class="text-center text-muted py-3">No packages yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
