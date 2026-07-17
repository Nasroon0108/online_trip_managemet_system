<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireTraveler();

const BOOKINGS_PAGE_PATH = "/bookings/my_bookings.php";

$packageId = (int)($_GET["package_id"] ?? $_POST["package_id"] ?? 0);
if ($packageId <= 0) {
    setFlash("warning", "Select a package to review.");
    redirectTo(BOOKINGS_PAGE_PATH);
}

$userId = (int)$_SESSION["user_id"];
$message = "";

$packageStmt = $pdo->prepare("SELECT package_id, title FROM packages WHERE package_id = :id");
$packageStmt->execute(["id" => $packageId]);
$package = $packageStmt->fetch();
if (!$package) {
    setFlash("danger", "Package not found.");
    redirectTo(BOOKINGS_PAGE_PATH);
}

$completedStmt = $pdo->prepare(
    "SELECT booking_id FROM bookings
     WHERE user_id = :user_id AND package_id = :package_id AND status = 'completed'
     LIMIT 1"
);
$completedStmt->execute(["user_id" => $userId, "package_id" => $packageId]);
if (!$completedStmt->fetch()) {
    setFlash("warning", "You can only review a package after the trip is marked completed.");
    redirectTo(BOOKINGS_PAGE_PATH);
}

$existingStmt = $pdo->prepare(
    "SELECT review_id FROM reviews WHERE user_id = :user_id AND package_id = :package_id"
);
$existingStmt->execute(["user_id" => $userId, "package_id" => $packageId]);
if ($existingStmt->fetch()) {
    setFlash("info", "You have already reviewed this package.");
    redirectTo(BOOKINGS_PAGE_PATH);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verifyCsrf($_POST["csrf_token"] ?? null);

    $rating = (int)($_POST["rating"] ?? 0);
    $comment = trim($_POST["comment"] ?? "");

    if ($rating < 1 || $rating > 5) {
        $message = "Please select a rating between 1 and 5.";
    } else {
        $insertStmt = $pdo->prepare(
            "INSERT INTO reviews (user_id, package_id, rating, comment)
             VALUES (:user_id, :package_id, :rating, :comment)"
        );
        $insertStmt->execute([
            "user_id" => $userId,
            "package_id" => $packageId,
            "rating" => $rating,
            "comment" => $comment !== "" ? $comment : null,
        ]);
        setFlash("success", "Thank you! Your review has been submitted.");
        redirectTo("/trips/view.php?id=" . $packageId);
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row justify-content-center animate-slide-up">
    <div class="col-lg-6 col-xl-5">
        <div class="page-header" style="border-bottom: none; padding-bottom: 0; margin-bottom: 1.5rem !important;">
            <div>
                <p class="page-kicker mb-1">Post-trip feedback</p>
                <h2 class="mb-0">Write a Review</h2>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(appUrl(BOOKINGS_PAGE_PATH)) ?>">
                <i class="fa-solid fa-arrow-left me-1"></i> My Bookings
            </a>
        </div>

        <div class="card card-modern">
            <div class="panel-toolbar">
                <div>
                    <h5 class="mb-0"><i class="fa-solid fa-star me-2 text-warning" style="font-size:0.9rem;"></i><?= htmlspecialchars($package["title"]) ?></h5>
                    <p class="text-muted small mb-0">Share your experience to help other travelers</p>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                        <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">

                    <div class="mb-4">
                        <label class="form-label" for="rating">Star Rating</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-star text-warning"></i></span>
                            <select class="form-select" id="rating" name="rating" required>
                                <option value="">Choose your rating...</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? "s" : "" ?> — <?= match($i) { 5 => 'Excellent', 4 => 'Good', 3 => 'Average', 2 => 'Poor', 1 => 'Terrible' } ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="comment">Your Review</label>
                        <textarea class="form-control" id="comment" name="comment" rows="5" placeholder="What did you enjoy most? What could be improved? How was the overall experience?" required></textarea>
                    </div>

                    <button class="btn btn-primary w-100 py-2" type="submit">
                        <i class="fa-solid fa-paper-plane me-2"></i>Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
