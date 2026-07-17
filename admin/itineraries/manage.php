<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

const ADMIN_PACKAGES_PATH = "/admin/packages/list.php";

$packageId = (int)($_GET["package_id"] ?? $_POST["package_id"] ?? 0);
if ($packageId <= 0) {
    redirectTo(ADMIN_PACKAGES_PATH);
}

$backPath = ADMIN_PACKAGES_PATH;

$packageStmt = $pdo->prepare("SELECT package_id, title, duration_days FROM packages WHERE package_id = :id");
$packageStmt->execute(["id" => $packageId]);
$package = $packageStmt->fetch();
if (!$package) {
    redirectTo($backPath);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "save";
    $itineraryId = (int)($_POST["itinerary_id"] ?? 0);

    if ($action === "delete" && $itineraryId > 0) {
        $deleteStmt = $pdo->prepare("DELETE FROM itineraries WHERE itinerary_id = :id AND package_id = :package_id");
        $deleteStmt->execute([
            "id" => $itineraryId,
            "package_id" => $packageId,
        ]);
        $message = "Itinerary item removed.";
    } else {
        $dayNumber = (int)($_POST["day_number"] ?? 0);
        $activityTitle = trim($_POST["activity_title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $activityTime = trim($_POST["activity_time"] ?? "");
        $location = trim($_POST["location"] ?? "");

        if ($dayNumber <= 0 || $activityTitle === "") {
            $message = "Day number and activity title are required.";
        } else {
            if ($itineraryId > 0) {
                $updateStmt = $pdo->prepare(
                    "UPDATE itineraries
                     SET day_number = :day_number,
                         activity_title = :activity_title,
                         description = :description,
                         activity_time = :activity_time,
                         location = :location
                     WHERE itinerary_id = :id AND package_id = :package_id"
                );
                $updateStmt->execute([
                    "day_number" => $dayNumber,
                    "activity_title" => $activityTitle,
                    "description" => $description !== "" ? $description : null,
                    "activity_time" => $activityTime !== "" ? $activityTime : null,
                    "location" => $location !== "" ? $location : null,
                    "id" => $itineraryId,
                    "package_id" => $packageId,
                ]);
                $message = "Itinerary item updated.";
            } else {
                $insertStmt = $pdo->prepare(
                    "INSERT INTO itineraries (package_id, day_number, activity_title, description, activity_time, location)
                     VALUES (:package_id, :day_number, :activity_title, :description, :activity_time, :location)"
                );
                $insertStmt->execute([
                    "package_id" => $packageId,
                    "day_number" => $dayNumber,
                    "activity_title" => $activityTitle,
                    "description" => $description !== "" ? $description : null,
                    "activity_time" => $activityTime !== "" ? $activityTime : null,
                    "location" => $location !== "" ? $location : null,
                ]);
                $message = "Itinerary item added.";
            }
        }
    }
}

$editId = (int)($_GET["edit_id"] ?? 0);
$editItem = null;
if ($editId > 0) {
    $editStmt = $pdo->prepare(
        "SELECT itinerary_id, day_number, activity_title, description, activity_time, location
         FROM itineraries
         WHERE itinerary_id = :id AND package_id = :package_id"
    );
    $editStmt->execute(["id" => $editId, "package_id" => $packageId]);
    $editItem = $editStmt->fetch() ?: null;
}

$itinerariesStmt = $pdo->prepare(
    "SELECT itinerary_id, day_number, activity_title, description, activity_time, location
     FROM itineraries
     WHERE package_id = :package_id
     ORDER BY day_number ASC, activity_time ASC, itinerary_id ASC"
);
$itinerariesStmt->execute(["package_id" => $packageId]);
$itineraries = $itinerariesStmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h2>Manage itinerary</h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($package["title"]) ?> · <?= (int)$package["duration_days"] ?> days</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl($backPath)) ?>">Back to packages</a>
</div>

<?php if ($message !== ""): ?>
    <div class="alert alert-info border-0"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-modern">
            <div class="card-body p-4">
                <h5 class="mb-3"><?= $editItem ? "Edit itinerary item" : "Add itinerary item" ?></h5>
                <form method="post">
                    <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                    <input type="hidden" name="itinerary_id" value="<?= (int)($editItem["itinerary_id"] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="day-number">Day number</label>
                        <input class="form-control" id="day-number" type="number" name="day_number" min="1" value="<?= htmlspecialchars((string)($editItem["day_number"] ?? "")) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="activity-title">Activity title</label>
                        <input class="form-control" id="activity-title" name="activity_title" value="<?= htmlspecialchars((string)($editItem["activity_title"] ?? "")) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="activity-time">Time</label>
                        <input class="form-control" id="activity-time" type="time" name="activity_time" value="<?= htmlspecialchars((string)($editItem["activity_time"] ?? "")) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="location">Location</label>
                        <input class="form-control" id="location" name="location" value="<?= htmlspecialchars((string)($editItem["location"] ?? "")) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars((string)($editItem["description"] ?? "")) ?></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= $editItem ? "Update item" : "Add item" ?></button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/itineraries/manage.php?package_id=' . (int)$package["package_id"])) ?>">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-modern card-panel">
            <div class="card-body p-0">
                <div class="panel-toolbar">
                    <div>
                        <h5 class="mb-0">Day-by-day plan</h5>
                        <p class="text-muted small mb-0"><?= count($itineraries) ?> item<?= count($itineraries) === 1 ? "" : "s" ?></p>
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
                                <th class="text-end">Actions</th>
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
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/itineraries/manage.php?package_id=' . (int)$package["package_id"] . '&edit_id=' . (int)$item["itinerary_id"])) ?>">Edit</a>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="package_id" value="<?= (int)$package["package_id"] ?>">
                                        <input type="hidden" name="itinerary_id" value="<?= (int)$item["itinerary_id"] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$itineraries): ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">No itinerary items yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
