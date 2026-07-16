<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/header.php";
requireAdmin();

$packageId = (int)($_GET["id"] ?? $_POST["package_id"] ?? 0);
if ($packageId <= 0) {
    redirectTo("/admin/packages/list.php");
}

$message = "";

$packageStmt = $pdo->prepare(
    "SELECT package_id, title, description, price, duration_days, max_participants, available_slots, start_date, end_date, status
     FROM packages
     WHERE package_id = :id"
);
$packageStmt->execute(["id" => $packageId]);
$package = $packageStmt->fetch();

if (!$package) {
    redirectTo("/admin/packages/list.php");
}

$destinations = $pdo->query(
    "SELECT destination_id, name, country, status
     FROM destinations
     WHERE status = 'active'
     ORDER BY name ASC"
)->fetchAll();

$selectedDestinationIds = $pdo->prepare("SELECT destination_id FROM package_destinations WHERE package_id = :id");
$selectedDestinationIds->execute(["id" => $packageId]);
$selectedDestinationIds = array_map("intval", array_column($selectedDestinationIds->fetchAll(), "destination_id"));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = (string)($_POST["price"] ?? "");
    $durationDays = (int)($_POST["duration_days"] ?? 0);
    $maxParticipants = (int)($_POST["max_participants"] ?? 0);
    $availableSlots = (int)($_POST["available_slots"] ?? 0);
    $startDate = $_POST["start_date"] ?? "";
    $endDate = $_POST["end_date"] ?? "";
    $selectedDestinationIds = array_map("intval", $_POST["destination_ids"] ?? []);

    if ($title === "" || $description === "" || $startDate === "" || $endDate === "") {
        $message = "Please fill all required fields.";
    } elseif (!is_numeric($price) || (float)$price < 0) {
        $message = "Price must be a valid number.";
    } elseif ($durationDays <= 0 || $maxParticipants <= 0 || $availableSlots <= 0) {
        $message = "Duration, max participants, and available slots must be greater than 0.";
    } elseif (!$selectedDestinationIds) {
        $message = "Select at least one destination.";
    } else {
        $pdo->beginTransaction();
        try {
            $updateStmt = $pdo->prepare(
                "UPDATE packages
                 SET title = :title,
                     description = :description,
                     price = :price,
                     duration_days = :duration_days,
                     max_participants = :max_participants,
                     available_slots = :available_slots,
                     start_date = :start_date,
                     end_date = :end_date
                 WHERE package_id = :id"
            );
            $updateStmt->execute([
                "title" => $title,
                "description" => $description,
                "price" => (float)$price,
                "duration_days" => $durationDays,
                "max_participants" => $maxParticipants,
                "available_slots" => $availableSlots,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "id" => $packageId,
            ]);

            $pdo->prepare("DELETE FROM package_destinations WHERE package_id = :id")->execute(["id" => $packageId]);

            $mapStmt = $pdo->prepare(
                "INSERT INTO package_destinations (package_id, destination_id)
                 VALUES (:package_id, :destination_id)"
            );
            foreach ($selectedDestinationIds as $destinationId) {
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
            $message = "Failed to update package.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Edit Package</h3>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Back</a>
                </div>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if (!$destinations): ?>
                    <div class="alert alert-info mb-0">No active destinations available.</div>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="package-title">Package Title</label>
                                <input class="form-control" id="package-title" name="title" value="<?= htmlspecialchars($package["title"]) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="package-price">Price</label>
                                <input class="form-control" id="package-price" type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars((string)$package["price"]) ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="package-description">Description</label>
                                <textarea class="form-control" id="package-description" name="description" rows="3" required><?= htmlspecialchars($package["description"]) ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="package-duration">Duration (Days)</label>
                                <input class="form-control" id="package-duration" type="number" name="duration_days" min="1" value="<?= (int)$package["duration_days"] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="package-max-participants">Max Participants</label>
                                <input class="form-control" id="package-max-participants" type="number" name="max_participants" min="1" value="<?= (int)$package["max_participants"] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="package-available-slots">Available Slots</label>
                                <input class="form-control" id="package-available-slots" type="number" name="available_slots" min="1" value="<?= (int)$package["available_slots"] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="package-start-date">Start Date</label>
                                <input class="form-control" id="package-start-date" type="date" name="start_date" value="<?= htmlspecialchars((string)$package["start_date"]) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="package-end-date">End Date</label>
                                <input class="form-control" id="package-end-date" type="date" name="end_date" value="<?= htmlspecialchars((string)$package["end_date"]) ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label d-block">Destinations</label>
                                <?php foreach ($destinations as $destination): ?>
                                    <?php $isChecked = in_array((int)$destination["destination_id"], $selectedDestinationIds, true); ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="destination-<?= (int)$destination["destination_id"] ?>" name="destination_ids[]" value="<?= (int)$destination["destination_id"] ?>" <?= $isChecked ? "checked" : "" ?>>
                                        <label class="form-check-label" for="destination-<?= (int)$destination["destination_id"] ?>">
                                            <?= htmlspecialchars($destination["name"]) ?>, <?= htmlspecialchars($destination["country"]) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">Update Package</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
