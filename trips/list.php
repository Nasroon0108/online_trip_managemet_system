<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireLogin();

const PER_PAGE = 8;
const LIST_PAGE_PATH = "/trips/list.php?page=";

$page = max(1, (int)($_GET["page"] ?? 1));
$offset = ($page - 1) * PER_PAGE;

$totalPackages = (int)$pdo->query("SELECT COUNT(*) FROM packages WHERE status = 'active'")->fetchColumn();
$totalPages = max(1, (int)ceil($totalPackages / PER_PAGE));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * PER_PAGE;
}

$packagesStmt = $pdo->prepare(
    "SELECT
        p.package_id,
        p.title,
        GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') AS destination_names,
        p.price,
        p.available_slots,
        p.start_date,
        p.end_date,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(DISTINCT r.review_id) AS review_count
     FROM packages p
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id AND d.status = 'active'
     LEFT JOIN reviews r ON r.package_id = p.package_id
     WHERE p.status = 'active'
     GROUP BY p.package_id, p.title, p.price, p.available_slots, p.start_date, p.end_date
     ORDER BY p.start_date ASC
     LIMIT :limit OFFSET :offset"
);
$packagesStmt->bindValue("limit", PER_PAGE, PDO::PARAM_INT);
$packagesStmt->bindValue("offset", $offset, PDO::PARAM_INT);
$packagesStmt->execute();
$packages = $packagesStmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Available Packages</h3>
    <?php if (isAdmin()): ?>
        <a class="btn btn-success" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Manage Packages</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
            <tr>
                <th>Title</th>
                <th>Destination</th>
                <th>Date</th>
                <th>Price</th>
                <th>Rating</th>
                <th>Slots</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($packages as $package): ?>
                <tr>
                    <td><?= htmlspecialchars($package["title"]) ?></td>
                    <td><?= htmlspecialchars($package["destination_names"] ?? "—") ?></td>
                    <td><?= htmlspecialchars($package["start_date"]) ?> to <?= htmlspecialchars($package["end_date"]) ?></td>
                    <td>Rs. <?= htmlspecialchars((string)$package["price"]) ?></td>
                    <td>
                        <?php if ((int)$package["review_count"] > 0): ?>
                            <?= number_format((float)$package["avg_rating"], 1) ?> ★ (<?= (int)$package["review_count"] ?>)
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)$package["available_slots"]) ?></td>
                    <td>
                        <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$package["package_id"])) ?>">View</a>
                        <?php if (isTraveler()): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/create.php')) ?>" class="d-inline">
                                <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                                <input type="hidden" name="num_travelers" value="1">
                                <button class="btn btn-primary btn-sm" type="submit">Quick Book</button>
                            </form>
                        <?php endif; ?>
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

<?php if ($totalPages > 1): ?>
    <nav class="mt-3" aria-label="Package pagination">
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item <?= $page <= 1 ? "disabled" : "" ?>">
                <a class="page-link" href="<?= htmlspecialchars(appUrl(LIST_PAGE_PATH . ($page - 1))) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? "active" : "" ?>">
                    <a class="page-link" href="<?= htmlspecialchars(appUrl(LIST_PAGE_PATH . $i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? "disabled" : "" ?>">
                <a class="page-link" href="<?= htmlspecialchars(appUrl(LIST_PAGE_PATH . ($page + 1))) ?>">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
