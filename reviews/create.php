<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireTraveler();

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

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">Write a Review</h3>
                <p class="text-muted">Package: <strong><?= htmlspecialchars($package["title"]) ?></strong></p>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                    <div class="mb-3">
                        <label class="form-label" for="rating">Rating (1–5)</label>
                        <select class="form-select" id="rating" name="rating" required>
                            <option value="">Select rating</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? "s" : "" ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="comment">Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Share your experience..."></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Submit Review</button>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars(appUrl(BOOKINGS_PAGE_PATH)) ?>">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
