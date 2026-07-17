<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

$message = "";
$destinations = $pdo->query(
    "SELECT destination_id, name, country, status
     FROM destinations
     WHERE status = 'active'
     ORDER BY name ASC"
)->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = (string)($_POST["price"] ?? "");
    $durationDays = (int)($_POST["duration_days"] ?? 0);
    $maxParticipants = (int)($_POST["max_participants"] ?? 0);
    $availableSlots = (int)($_POST["available_slots"] ?? 0);
    $startDate = $_POST["start_date"] ?? "";
    $endDate = $_POST["end_date"] ?? "";
    $selectedDestinations = array_map("intval", $_POST["destination_ids"] ?? []);

    if ($title === "" || $description === "" || $startDate === "" || $endDate === "") {
        $message = "Please fill all required fields.";
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $message = "Price must be a valid number.";
    } elseif ($durationDays <= 0 || $maxParticipants <= 0 || $availableSlots <= 0) {
        $message = "Duration, max participants, and available slots must be greater than 0.";
    } elseif (!$selectedDestinations) {
        $message = "Select at least one destination.";
    } else {
        $pdo->beginTransaction();
        try {
            $pkgStmt = $pdo->prepare(
                "INSERT INTO packages
                    (title, description, price, duration_days, max_participants, available_slots, image, inclusions, exclusions, start_date, end_date, created_by, status)
                 VALUES
                    (:title, :description, :price, :duration_days, :max_participants, :available_slots, NULL, NULL, NULL, :start_date, :end_date, :created_by, 'active')"
            );
            $pkgStmt->execute([
                "title" => $title,
                "description" => $description,
                "price" => (float)$price,
                "duration_days" => $durationDays,
                "max_participants" => $maxParticipants,
                "available_slots" => $availableSlots,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "created_by" => (int)$_SESSION["user_id"],
            ]);
            $packageId = (int)$pdo->lastInsertId();

            $mapStmt = $pdo->prepare(
                "INSERT INTO package_destinations (package_id, destination_id)
                 VALUES (:package_id, :destination_id)"
            );
            foreach ($selectedDestinations as $destinationId) {
                $mapStmt->execute([
                    "package_id" => $packageId,
                    "destination_id" => $destinationId,
                ]);
            }

            $pdo->commit();
            redirectTo("/admin/packages/list.php");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Failed to create package.";
        }
    }
}
?>

<div class="page-header">
    <div>
        <h2>Create package</h2>
        <p class="text-muted mb-0">Define pricing, capacity, dates, and linked destinations.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Back to packages</a>
</div>

<div class="card card-modern">
    <div class="card-body p-4">
        <?php if ($message !== ""): ?>
            <div class="alert alert-warning border-0"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!$destinations): ?>
            <div class="alert alert-info border-0 mb-0">
                Add at least one active destination before creating a package.
                <a href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>">Manage destinations</a>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="package-title">Package title</label>
                        <input class="form-control" id="package-title" name="title" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="package-price">Price</label>
                        <input class="form-control" id="package-price" type="number" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="package-description">Description</label>
                        <textarea class="form-control" id="package-description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="package-duration">Duration (days)</label>
                        <input class="form-control" id="package-duration" type="number" name="duration_days" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="package-max-participants">Max participants</label>
                        <input class="form-control" id="package-max-participants" type="number" name="max_participants" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="package-available-slots">Available slots</label>
                        <input class="form-control" id="package-available-slots" type="number" name="available_slots" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="package-start-date">Start date</label>
                        <input class="form-control" id="package-start-date" type="date" name="start_date" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="package-end-date">End date</label>
                        <input class="form-control" id="package-end-date" type="date" name="end_date" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label d-block">Destinations</label>
                        <div class="row g-2">
                            <?php foreach ($destinations as $destination): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="destination-<?= (int)$destination["destination_id"] ?>" name="destination_ids[]" value="<?= (int)$destination["destination_id"] ?>">
                                        <label class="form-check-label" for="destination-<?= (int)$destination["destination_id"] ?>">
                                            <?= htmlspecialchars($destination["name"]) ?>, <?= htmlspecialchars($destination["country"]) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary" type="submit">Save package</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
