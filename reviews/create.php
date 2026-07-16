<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireTraveler();
require_once __DIR__ . "/../includes/header.php";

const BOOKINGS_PAGE_PATH = "/bookings/my_bookings.php";

$packageId = (int)($_GET["package_id"] ?? $_POST["package_id"] ?? 0);
if ($packageId <= 0) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$userId = (int)$_SESSION["user_id"];
$message = "";

$packageStmt = $pdo->prepare("SELECT package_id, title FROM packages WHERE package_id = :id");
$packageStmt->execute(["id" => $packageId]);
$package = $packageStmt->fetch();
if (!$package) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$completedStmt = $pdo->prepare(
    "SELECT booking_id FROM bookings
     WHERE user_id = :user_id AND package_id = :package_id AND status = 'completed'
     LIMIT 1"
);
$completedStmt->execute(["user_id" => $userId, "package_id" => $packageId]);
if (!$completedStmt->fetch()) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$existingStmt = $pdo->prepare(
    "SELECT review_id FROM reviews WHERE user_id = :user_id AND package_id = :package_id"
);
$existingStmt->execute(["user_id" => $userId, "package_id" => $packageId]);
if ($existingStmt->fetch()) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
?>

<div class="row justify-content-center animate-slide-up">
    <div class="col-lg-6">
        <div class="card card-modern">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-modern mb-2">
                        <i class="fa-solid fa-star-half-stroke"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Write a Review</h3>
                    <p class="text-muted small">Share your thoughts on the package <strong><?= htmlspecialchars($package["title"]) ?></strong></p>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-3 fs-4 text-warning"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small" for="rating">Star Rating</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-star text-warning"></i></span>
                            <select class="form-select border-start-0 ps-0" id="rating" name="rating" required>
                                <option value="">Select your rating...</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>"><?= $i ?> Star<?= $i > 1 ? "s" : "" ?> &mdash; <?= match($i) { 5 => 'Excellent', 4 => 'Good', 3 => 'Average', 2 => 'Poor', 1 => 'Terrible' } ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small" for="comment">Your Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="5" placeholder="Tell us what you liked, what could be improved, and your overall experience..." required></textarea>
                    </div>

                    <div class="d-flex gap-3">
                        <button class="btn btn-primary flex-fill py-2.5" type="submit">
                            <i class="fa-solid fa-paper-plane me-1"></i> Submit Review
                        </button>
                        <a class="btn btn-outline-secondary flex-fill py-2.5" href="<?= htmlspecialchars(appUrl(BOOKINGS_PAGE_PATH)) ?>">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
