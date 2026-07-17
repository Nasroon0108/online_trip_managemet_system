<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

const PER_PAGE = 8;

$page = max(1, (int)($_GET["page"] ?? 1));
$search = trim((string)($_GET["q"] ?? ""));
$offset = ($page - 1) * PER_PAGE;

$countSql = "SELECT COUNT(DISTINCT p.package_id)
     FROM packages p
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id AND d.status = 'active'
     WHERE p.status = 'active'";
$countParams = [];
if ($search !== "") {
    $countSql .= " AND (p.title LIKE :q OR p.description LIKE :q OR d.name LIKE :q)";
    $countParams["q"] = "%" . $search . "%";
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalPackages = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPackages / PER_PAGE));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * PER_PAGE;
}

$packagesSql = "SELECT
        p.package_id,
        p.title,
        p.description,
        GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS destination_names,
        p.price,
        p.duration_days,
        p.available_slots,
        p.start_date,
        p.end_date,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(DISTINCT r.review_id) AS review_count
     FROM packages p
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id AND d.status = 'active'
     LEFT JOIN reviews r ON r.package_id = p.package_id
     WHERE p.status = 'active'";
if ($search !== "") {
    $packagesSql .= " AND (p.title LIKE :q OR p.description LIKE :q OR d.name LIKE :q)";
}
$packagesSql .= " GROUP BY p.package_id, p.title, p.description, p.price, p.duration_days, p.available_slots, p.start_date, p.end_date
     ORDER BY p.start_date ASC
     LIMIT :limit OFFSET :offset";

$packagesStmt = $pdo->prepare($packagesSql);
if ($search !== "") {
    $packagesStmt->bindValue("q", "%" . $search . "%");
}
$packagesStmt->bindValue("limit", PER_PAGE, PDO::PARAM_INT);
$packagesStmt->bindValue("offset", $offset, PDO::PARAM_INT);
$packagesStmt->execute();
$packages = $packagesStmt->fetchAll();

$isStaffPreview = isAdmin();
$listPageBase = "/trips/list.php?" . ($search !== "" ? "q=" . rawurlencode($search) . "&" : "") . "page=";

require_once __DIR__ . "/../includes/header.php";
?>

<div class="page-header">
    <div>
        <h2><?= $isStaffPreview ? "Preview packages" : "Browse packages" ?></h2>
        <p class="text-muted mb-0">
            <?= $isStaffPreview
                ? "Public catalog view of active Sri Lankan tour packages."
                : "Find and book curated tours across Sri Lanka." ?>
            <?php if ($search !== ""): ?>
                · Showing results for “<?= htmlspecialchars($search) ?>”
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (isTraveler()): ?>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/dashboard/index.php')) ?>">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Manage packages</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isStaffPreview): ?>
    <div class="card card-modern card-panel">
        <div class="card-body p-0">
            <div class="panel-toolbar">
                <div>
                    <h5 class="mb-0">Active catalog</h5>
                    <p class="text-muted small mb-0"><?= $totalPackages ?> package<?= $totalPackages === 1 ? "" : "s" ?> live</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-app align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Destinations</th>
                            <th>Dates</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Slots</th>
                            <th>Rating</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($packages as $package): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($package["title"]) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars(mb_strimwidth((string)$package["description"], 0, 80, "…")) ?></div>
                            </td>
                            <td><?= htmlspecialchars($package["destination_names"] ?? "—") ?></td>
                            <td class="small text-nowrap">
                                <?= htmlspecialchars((string)$package["start_date"]) ?>
                                <span class="text-muted">–</span>
                                <?= htmlspecialchars((string)$package["end_date"]) ?>
                            </td>
                            <td class="text-nowrap"><?= (int)$package["duration_days"] ?> days</td>
                            <td class="text-nowrap">Rs. <?= number_format((float)$package["price"], 2) ?></td>
                            <td><?= (int)$package["available_slots"] ?></td>
                            <td>
                                <?php if ((int)$package["review_count"] > 0): ?>
                                    <?= number_format((float)$package["avg_rating"], 1) ?> / 5
                                    <span class="text-muted small">(<?= (int)$package["review_count"] ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$package["package_id"])) ?>">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$packages): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">No active packages available.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif (!$packages): ?>
    <div class="card card-modern">
        <div class="card-body text-center text-muted py-5">
            No packages available right now. Please check back later.
        </div>
    </div>
<?php else: ?>
    <div class="row g-4 mb-4">
        <?php foreach ($packages as $package): ?>
            <?php $slots = (int)$package["available_slots"]; ?>
            <div class="col-md-6 col-xl-4">
                <div class="card card-modern h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h5 class="mb-0"><?= htmlspecialchars($package["title"]) ?></h5>
                            <span class="badge badge-soft"><?= $slots > 0 ? $slots . " slots" : "Sold out" ?></span>
                        </div>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($package["destination_names"] ?? "—") ?></p>
                        <div class="small text-muted mb-3">
                            <div><?= htmlspecialchars((string)$package["start_date"]) ?> – <?= htmlspecialchars((string)$package["end_date"]) ?></div>
                            <div><?= (int)$package["duration_days"] ?> days</div>
                            <?php if ((int)$package["review_count"] > 0): ?>
                                <div><?= number_format((float)$package["avg_rating"], 1) ?> / 5 · <?= (int)$package["review_count"] ?> reviews</div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
                            <div class="fw-semibold text-primary">Rs. <?= number_format((float)$package["price"], 0) ?></div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$package["package_id"])) ?>">Details</a>
                                <?php if (isTraveler() && $slots > 0): ?>
                                    <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/create.php')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                                        <input type="hidden" name="num_travelers" value="1">
                                        <button class="btn btn-sm btn-primary" type="submit">Book</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="Package pagination">
        <ul class="pagination justify-content-center mb-0">
            <li class="page-item <?= $page <= 1 ? "disabled" : "" ?>">
                <a class="page-link" href="<?= htmlspecialchars(appUrl($listPageBase . ($page - 1))) ?>">Prev</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? "active" : "" ?>">
                    <a class="page-link" href="<?= htmlspecialchars(appUrl($listPageBase . $i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? "disabled" : "" ?>">
                <a class="page-link" href="<?= htmlspecialchars(appUrl($listPageBase . ($page + 1))) ?>">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
