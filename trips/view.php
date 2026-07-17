<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();
require_once __DIR__ . "/../includes/header.php";

const PACKAGES_LIST_PATH = "/trips/list.php";

$packageId = (int)($_GET["id"] ?? 0);
if ($packageId <= 0) {
    redirectTo(PACKAGES_LIST_PATH);
}

$packageStmt = $pdo->prepare(
    "SELECT package_id, title, description, price, duration_days, max_participants, available_slots, start_date, end_date, status
     FROM packages
     WHERE package_id = :id"
);
$packageStmt->execute(["id" => $packageId]);
$package = $packageStmt->fetch();

if (!$package || $package["status"] !== "active") {
    redirectTo(PACKAGES_LIST_PATH);
}

$destinationStmt = $pdo->prepare(
    "SELECT d.destination_id, d.name, d.country, d.description
     FROM package_destinations pd
     INNER JOIN destinations d ON d.destination_id = pd.destination_id
     WHERE pd.package_id = :id AND d.status = 'active'
     ORDER BY d.name ASC"
);
$destinationStmt->execute(["id" => $packageId]);
$destinations = $destinationStmt->fetchAll();

$itineraryStmt = $pdo->prepare(
    "SELECT itinerary_id, day_number, activity_title, description, activity_time, location
     FROM itineraries
     WHERE package_id = :id
     ORDER BY day_number ASC, activity_time ASC, itinerary_id ASC"
);
$itineraryStmt->execute(["id" => $packageId]);
$itineraries = $itineraryStmt->fetchAll();

$reviewsStmt = $pdo->prepare(
    "SELECT r.rating, r.comment, r.review_date, u.name AS reviewer_name
     FROM reviews r
     INNER JOIN users u ON u.user_id = r.user_id
     WHERE r.package_id = :id
     ORDER BY r.review_date DESC"
);
$reviewsStmt->execute(["id" => $packageId]);
$reviews = $reviewsStmt->fetchAll();

$avgRating = 0.0;
if ($reviews) {
    $avgRating = array_sum(array_column($reviews, "rating")) / count($reviews);
}

$canReview = false;
if (isTraveler()) {
    $eligibleStmt = $pdo->prepare(
        "SELECT b.booking_id
         FROM bookings b
         LEFT JOIN reviews r ON r.user_id = b.user_id AND r.package_id = b.package_id
         WHERE b.user_id = :user_id AND b.package_id = :package_id AND b.status = 'completed' AND r.review_id IS NULL
         LIMIT 1"
    );
    $eligibleStmt->execute([
        "user_id" => (int)$_SESSION["user_id"],
        "package_id" => $packageId,
    ]);
    $canReview = (bool)$eligibleStmt->fetch();
}

$slots = (int)$package["available_slots"];
$isStaff = isAdmin();
?>

<div class="page-header">
    <div>
        <h2><?= htmlspecialchars($package["title"]) ?></h2>
        <p class="text-muted mb-0">
            <?= htmlspecialchars((string)$package["start_date"]) ?> – <?= htmlspecialchars((string)$package["end_date"]) ?>
            · <?= (int)$package["duration_days"] ?> days
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (isTraveler()): ?>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/dashboard/index.php')) ?>">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars(appUrl('/admin/itineraries/manage.php?package_id=' . (int)$package["package_id"])) ?>">Edit itinerary</a>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl(PACKAGES_LIST_PATH)) ?>">Back</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-modern mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">About this package</h5>
                <p class="text-muted mb-4" style="white-space: pre-line;"><?= htmlspecialchars($package["description"]) ?></p>

                <h5 class="mb-3">Destinations</h5>
                <?php if ($destinations): ?>
                    <div class="row g-3">
                        <?php foreach ($destinations as $destination): ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100" style="border-color: var(--card-border) !important;">
                                    <div class="fw-semibold"><?= htmlspecialchars($destination["name"]) ?>, <?= htmlspecialchars($destination["country"]) ?></div>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars((string)($destination["description"] ?? "")) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No destination details added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-modern card-panel mb-4">
            <div class="card-body p-0">
                <div class="panel-toolbar">
                    <div>
                        <h5 class="mb-0">Day-by-day itinerary</h5>
                        <p class="text-muted small mb-0"><?= count($itineraries) ?> activity item<?= count($itineraries) === 1 ? "" : "s" ?></p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-app align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Activity</th>
                                <th>Time</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($itineraries as $item): ?>
                            <tr>
                                <td><span class="chip">Day <?= (int)$item["day_number"] ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($item["activity_title"]) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($item["description"] ?? "")) ?></div>
                                </td>
                                <td class="text-nowrap"><?= htmlspecialchars((string)($item["activity_time"] ?? "—")) ?></td>
                                <td><?= htmlspecialchars((string)($item["location"] ?? "—")) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$itineraries): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No itinerary published yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card card-modern card-panel" id="reviews">
            <div class="card-body p-0">
                <div class="panel-toolbar">
                    <div>
                        <h5 class="mb-0">Traveler reviews</h5>
                        <p class="text-muted small mb-0">
                            <?= $reviews ? number_format($avgRating, 1) . " / 5 from " . count($reviews) . " review(s)" : "No reviews yet" ?>
                        </p>
                    </div>
                    <?php if ($canReview): ?>
                        <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/reviews/create.php?package_id=' . (int)$package["package_id"])) ?>">Write review</a>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <?php if ($reviews): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="mb-3 pb-3 border-bottom" style="border-color: var(--card-border) !important;">
                                <div class="d-flex justify-content-between gap-2 flex-wrap mb-1">
                                    <div class="fw-semibold"><?= htmlspecialchars($review["reviewer_name"]) ?></div>
                                    <div class="small text-muted"><?= (int)$review["rating"] ?> / 5 · <?= htmlspecialchars($review["review_date"]) ?></div>
                                </div>
                                <p class="text-muted small mb-0"><?= htmlspecialchars((string)($review["comment"] ?? "")) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No reviews yet for this package.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-modern mb-4">
            <div class="card-body p-4">
                <h5 class="mb-3">Package summary</h5>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--card-border) !important;">
                    <span class="text-muted">Price / traveler</span>
                    <strong>Rs. <?= number_format((float)$package["price"], 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--card-border) !important;">
                    <span class="text-muted">Duration</span>
                    <strong><?= (int)$package["duration_days"] ?> days</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--card-border) !important;">
                    <span class="text-muted">Max travelers</span>
                    <strong><?= (int)$package["max_participants"] ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Available slots</span>
                    <span class="badge badge-soft"><?= $slots > 0 ? $slots . " left" : "Sold out" ?></span>
                </div>
            </div>
        </div>

        <div class="card card-modern">
            <div class="card-body p-4">
                <h5 class="mb-3"><?= $isStaff ? "Staff note" : "Book now" ?></h5>
                <?php if ($isStaff): ?>
                    <p class="text-muted small mb-0">This is a preview of the traveler-facing package. Booking is available to traveler accounts only.</p>
                <?php elseif ($slots <= 0): ?>
                    <div class="alert alert-warning border-0 mb-0">This package is currently sold out.</div>
                <?php else: ?>
                    <p class="text-muted small mb-3">Booking stays pending until mock payment is completed.</p>
                    <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/create.php')) ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="num-travelers">Number of travelers</label>
                            <input
                                class="form-control"
                                id="num-travelers"
                                type="number"
                                name="num_travelers"
                                min="1"
                                max="<?= $slots ?>"
                                value="1"
                                required
                            >
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Confirm & book</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
