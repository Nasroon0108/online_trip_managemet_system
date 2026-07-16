<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireLogin();

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><?= htmlspecialchars($package["title"]) ?></h3>
    <a class="btn btn-secondary" href="<?= htmlspecialchars(appUrl(PACKAGES_LIST_PATH)) ?>">Back to Packages</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="text-muted mb-3"><?= nl2br(htmlspecialchars($package["description"])) ?></p>
        <div class="row g-3">
            <div class="col-md-3"><strong>Price:</strong> Rs. <?= htmlspecialchars((string)$package["price"]) ?></div>
            <div class="col-md-3"><strong>Duration:</strong> <?= htmlspecialchars((string)$package["duration_days"]) ?> days</div>
            <div class="col-md-3"><strong>Max Participants:</strong> <?= htmlspecialchars((string)$package["max_participants"]) ?></div>
            <div class="col-md-3"><strong>Available Slots:</strong> <?= htmlspecialchars((string)$package["available_slots"]) ?></div>
            <div class="col-md-6"><strong>Start Date:</strong> <?= htmlspecialchars((string)$package["start_date"]) ?></div>
            <div class="col-md-6"><strong>End Date:</strong> <?= htmlspecialchars((string)$package["end_date"]) ?></div>
            <?php if ($reviews): ?>
                <div class="col-md-12">
                    <strong>Average Rating:</strong>
                    <?= number_format($avgRating, 1) ?> / 5
                    <span class="text-muted">(<?= count($reviews) ?> review<?= count($reviews) === 1 ? "" : "s" ?>)</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Destinations</h5>
                <?php if ($destinations): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($destinations as $destination): ?>
                            <li class="list-group-item px-0">
                                <div class="fw-semibold"><?= htmlspecialchars($destination["name"]) ?>, <?= htmlspecialchars($destination["country"]) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars((string)($destination["description"] ?? "")) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No destination details added yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Book This Package</h5>
                <?php if (!isTraveler()): ?>
                    <p class="text-muted mb-0">Booking is available for traveler accounts only.</p>
                <?php elseif ((int)$package["available_slots"] <= 0): ?>
                    <p class="text-danger mb-0">This package is currently sold out.</p>
                <?php else: ?>
                    <p class="text-muted small">Bookings stay pending until you complete the sandbox payment step.</p>
                    <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/create.php')) ?>">
                        <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="num-travelers">Number of Travelers</label>
                            <input
                                class="form-control"
                                id="num-travelers"
                                type="number"
                                name="num_travelers"
                                min="1"
                                max="<?= (int)$package["available_slots"] ?>"
                                value="1"
                                required
                            >
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Book Now</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body">
        <h5 class="mb-3">Day-by-Day Itinerary</h5>
        <?php if ($itineraries): ?>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Activity</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($itineraries as $item): ?>
                        <tr>
                            <td><?= (int)$item["day_number"] ?></td>
                            <td><?= htmlspecialchars($item["activity_title"]) ?></td>
                            <td><?= htmlspecialchars((string)($item["activity_time"] ?? "—")) ?></td>
                            <td><?= htmlspecialchars((string)($item["location"] ?? "—")) ?></td>
                            <td><?= htmlspecialchars((string)($item["description"] ?? "")) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No itinerary has been published for this package yet.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Traveler Reviews</h5>
            <?php if ($canReview): ?>
                <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/reviews/create.php?package_id=' . (int)$package["package_id"])) ?>">Write Review</a>
            <?php endif; ?>
        </div>

        <?php if ($reviews): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($review["reviewer_name"]) ?></strong>
                        <span class="text-warning"><?= str_repeat("★", (int)$review["rating"]) ?><?= str_repeat("☆", 5 - (int)$review["rating"]) ?></span>
                    </div>
                    <div class="small text-muted mb-1"><?= htmlspecialchars($review["review_date"]) ?></div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars((string)($review["comment"] ?? ""))) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted mb-0">No reviews yet for this package.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
