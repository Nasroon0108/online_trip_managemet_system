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
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 animate-slide-up">
    <div>
        <h2 class="mb-1 fw-bold"><?= htmlspecialchars($package["title"]) ?></h2>
        <span class="text-muted small">
            <i class="fa-solid fa-calendar-day me-1"></i>
            <?= date("F d, Y", strtotime($package["start_date"])) ?> &mdash; <?= date("F d, Y", strtotime($package["end_date"])) ?>
        </span>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl(PACKAGES_LIST_PATH)) ?>">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Packages
    </a>
</div>

<div class="row g-4 animate-slide-up">
    <div class="col-lg-8">
        <!-- Description & Destinations -->
        <div class="card card-modern mb-4">
            <div class="card-decor-gradient" style="height: 180px;"></div>
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3">About this package</h4>
                <p class="text-muted fs-6 mb-4" style="white-space: pre-line;"><?= htmlspecialchars($package["description"]) ?></p>
                
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-map-location-dot text-primary me-2"></i>Destinations Covered</h5>
                <?php if ($destinations): ?>
                    <div class="row g-3">
                        <?php foreach ($destinations as $destination): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 border border-light">
                                    <div class="fw-bold text-dark"><i class="fa-solid fa-location-dot text-secondary me-2"></i><?= htmlspecialchars($destination["name"]) ?>, <?= htmlspecialchars($destination["country"]) ?></div>
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
        
        <!-- Itinerary Timeline -->
        <div class="card card-modern p-4 mb-4">
            <h4 class="fw-bold mb-4"><i class="fa-solid fa-route text-primary me-2"></i>Day-by-Day Itinerary</h4>
            <?php if ($itineraries): ?>
                <div class="timeline">
                    <?php foreach ($itineraries as $item): ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"><?= (int)$item["day_number"] ?></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-baseline mb-2 flex-wrap">
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($item["activity_title"]) ?></h5>
                                    <div class="text-muted small">
                                        <?php if (!empty($item["activity_time"])): ?>
                                            <i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars(date("h:i A", strtotime($item["activity_time"]))) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item["location"])): ?>
                                            <span class="mx-1">&bull;</span><i class="fa-solid fa-map-pin me-1"></i><?= htmlspecialchars($item["location"]) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted mb-0 small"><?= htmlspecialchars((string)($item["description"] ?? "")) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No itinerary has been published for this package yet.</p>
            <?php endif; ?>
        </div>

        <!-- Reviews -->
        <div class="card card-modern p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h4 class="fw-bold mb-0"><i class="fa-solid fa-comments text-primary me-2"></i>Traveler Reviews</h4>
                <?php if ($canReview): ?>
                    <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/reviews/create.php?package_id=' . (int)$package["package_id"])) ?>">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Write Review
                      </a>
                <?php endif; ?>
            </div>
            
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-bubble">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                            <div class="fw-bold text-dark">
                                <i class="fa-solid fa-user-circle text-secondary me-2"></i><?= htmlspecialchars($review["reviewer_name"]) ?>
                            </div>
                            <div class="text-warning">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa-<?= $i <= (int)$review["rating"] ? 'solid' : 'regular' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="small text-muted mb-2"><?= date("M d, Y", strtotime($review["review_date"])) ?></div>
                        <p class="mb-0 text-muted small" style="font-style: italic;">"<?= htmlspecialchars((string)($review["comment"] ?? "")) ?>"</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted mb-0">No reviews yet for this package.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Package Info sidebar card -->
        <div class="card card-modern p-4 mb-4">
            <h4 class="fw-bold mb-3 text-dark">Package Details</h4>
            <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span class="text-muted"><i class="fa-solid fa-wallet text-primary me-2" style="width: 20px;"></i>Price / Traveler</span>
                    <strong class="text-primary fs-5">Rs. <?= number_format((float)$package["price"], 2) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span class="text-muted"><i class="fa-solid fa-hourglass-half text-primary me-2" style="width: 20px;"></i>Duration</span>
                    <span class="fw-bold"><?= htmlspecialchars((string)$package["duration_days"]) ?> Days</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span class="text-muted"><i class="fa-solid fa-user-group text-primary me-2" style="width: 20px;"></i>Max Slots</span>
                    <span class="fw-bold"><?= htmlspecialchars((string)$package["max_participants"]) ?> Travelers</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span class="text-muted"><i class="fa-solid fa-ticket text-primary me-2" style="width: 20px;"></i>Available Slots</span>
                    <?php 
                    $slots = (int)$package["available_slots"];
                    if ($slots > 5): ?>
                        <span class="badge badge-custom badge-confirmed"><?= $slots ?> left</span>
                    <?php elseif ($slots > 0): ?>
                        <span class="badge badge-custom badge-pending">Only <?= $slots ?> left</span>
                    <?php else: ?>
                        <span class="badge badge-custom badge-cancelled">Sold Out</span>
                    <?php endif; ?>
                </li>
            </ul>
            <?php if ($reviews): ?>
                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3">
                    <i class="fa-solid fa-star text-warning fs-4"></i>
                    <div>
                        <div class="fw-bold text-dark"><?= number_format($avgRating, 1) ?> / 5.0</div>
                        <div class="text-muted small">Based on <?= count($reviews) ?> traveler reviews</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Booking Box -->
        <div class="card card-modern p-4">
            <h4 class="fw-bold mb-3 text-dark">Book Now</h4>
            <?php if (!isTraveler()): ?>
                <div class="alert alert-info py-2 small mb-0"><i class="fa-solid fa-info-circle me-1"></i>Booking is available for traveler accounts only.</div>
            <?php elseif ((int)$package["available_slots"] <= 0): ?>
                <div class="alert alert-danger py-2 small mb-0"><i class="fa-solid fa-circle-exclamation me-1"></i>This package is currently sold out.</div>
            <?php else: ?>
                <p class="text-muted small mb-3"><i class="fa-solid fa-shield-halved me-1 text-primary"></i>Bookings stay pending until mock payment is complete.</p>
                <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/create.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                    <div class="mb-3">
                        <label class="form-label small" for="num-travelers">Number of Travelers</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                            <input
                                class="form-control border-start-0 ps-0"
                                id="num-travelers"
                                type="number"
                                name="num_travelers"
                                min="1"
                                max="<?= (int)$package["available_slots"] ?>"
                                value="1"
                                required
                            >
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 py-2.5" type="submit">
                        <i class="fa-solid fa-bolt me-1"></i> Confirm & Book
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
