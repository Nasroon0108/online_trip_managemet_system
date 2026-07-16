<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();
require_once __DIR__ . "/../includes/header.php";

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

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="mb-1 fw-bold">Explore Packages</h3>
        <p class="text-muted mb-0">Find your next perfect destination from our curated travel list</p>
    </div>
    <?php if (isAdmin()): ?>
        <a class="btn btn-primary shadow-sm" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">
            <i class="fa-solid fa-cubes me-1"></i> Manage Packages
        </a>
    <?php endif; ?>
</div>

<?php if (!$packages): ?>
    <div class="card card-modern p-5 text-center my-4">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-suitcase-rolling fs-1"></i>
        </div>
        <h4 class="fw-bold">No Packages Available</h4>
        <p class="text-muted">Check back later or contact admin to list packages.</p>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-4">
        <?php foreach ($packages as $package): ?>
            <div class="col">
                <div class="card card-modern h-100 d-flex flex-column">
                    <div class="card-decor-gradient position-relative">
                        <div class="position-absolute top-0 start-0 m-3 badge bg-white text-dark shadow-sm py-2 px-3 rounded-pill d-flex align-items-center gap-1">
                            <i class="fa-solid fa-location-dot text-primary"></i>
                            <span class="fw-bold text-truncate" style="max-width: 140px;">
                                <?= htmlspecialchars(explode(',', $package["destination_names"] ?? "World")[0]) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-3 d-flex flex-column flex-grow-1">
                        <div class="mb-2 text-muted small d-flex align-items-center gap-1">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span><?= date("M d, Y", strtotime($package["start_date"])) ?> - <?= date("M d, Y", strtotime($package["end_date"])) ?></span>
                        </div>
                        
                        <h5 class="fw-bold mb-2 text-truncate-2" style="min-height: 48px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars($package["title"]) ?>
                        </h5>
                        
                        <p class="card-text text-muted small mb-3 text-truncate-2" style="min-height: 38px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <i class="fa-solid fa-map me-1 text-secondary"></i>
                            <?= htmlspecialchars($package["destination_names"] ?? "—") ?>
                        </p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <?php if ((int)$package["review_count"] > 0): ?>
                                    <div class="text-warning small fw-bold">
                                        <i class="fa-solid fa-star"></i>
                                        <span class="text-dark"><?= number_format((float)$package["avg_rating"], 1) ?></span>
                                        <span class="text-muted font-normal">(<?= (int)$package["review_count"] ?>)</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="fa-regular fa-star me-1"></i>No reviews</span>
                                <?php endif; ?>
                                
                                <?php 
                                $slots = (int)$package["available_slots"];
                                if ($slots > 5): ?>
                                    <span class="badge badge-custom badge-confirmed"><?= $slots ?> slots left</span>
                                <?php elseif ($slots > 0): ?>
                                    <span class="badge badge-custom badge-pending">Only <?= $slots ?> left</span>
                                <?php else: ?>
                                    <span class="badge badge-custom badge-cancelled">Sold Out</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-baseline mb-3">
                                <span class="text-muted small">Price / traveler</span>
                                <span class="fs-4 fw-bold text-primary">Rs. <?= number_format((float)$package["price"], 0) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent border-0 p-3 pt-0 mt-auto d-flex gap-2">
                        <a class="btn btn-outline-primary btn-sm flex-fill" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$package["package_id"])) ?>">
                            <i class="fa-solid fa-eye me-1"></i> Details
                        </a>
                        <?php if (isTraveler()): ?>
                            <?php if ($slots > 0): ?>
                                <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/create.php')) ?>" class="d-inline flex-fill">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                                    <input type="hidden" name="num_travelers" value="1">
                                    <button class="btn btn-primary btn-sm w-100" type="submit">
                                        <i class="fa-solid fa-bolt me-1"></i> Book
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm flex-fill" disabled>Sold Out</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="Package pagination">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? "disabled" : "" ?>">
                <a class="page-link" href="<?= htmlspecialchars(appUrl(LIST_PAGE_PATH . ($page - 1))) ?>">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? "active" : "" ?>">
                    <a class="page-link" href="<?= htmlspecialchars(appUrl(LIST_PAGE_PATH . $i)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? "disabled" : "" ?>">
                <a class="page-link" href="<?= htmlspecialchars(appUrl(LIST_PAGE_PATH . ($page + 1))) ?>">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
