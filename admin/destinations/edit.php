<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

$destinationId = (int)($_GET["id"] ?? $_POST["destination_id"] ?? 0);
if ($destinationId <= 0) {
    redirectTo("/admin/destinations/list.php");
}

$message = "";
$stmt = $pdo->prepare("SELECT destination_id, name, country, description, status FROM destinations WHERE destination_id = :id");
$stmt->execute(["id" => $destinationId]);
$destination = $stmt->fetch();

if (!$destination) {
    redirectTo("/admin/destinations/list.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $country = trim($_POST["country"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($name === "" || $country === "") {
        $message = "Name and country are required.";
    } else {
        $updateStmt = $pdo->prepare(
            "UPDATE destinations
             SET name = :name, country = :country, description = :description
             WHERE destination_id = :id"
        );
        $updateStmt->execute([
            "name" => $name,
            "country" => $country,
            "description" => $description !== "" ? $description : null,
            "id" => $destinationId,
        ]);
        redirectTo("/admin/destinations/list.php");
    }
}
?>

<div class="page-header">
    <div>
        <h2>Edit destination</h2>
        <p class="text-muted mb-0">Update destination details used across packages.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>">Back</a>
</div>

<div class="card card-modern">
    <div class="card-body p-4">
        <?php if ($message !== ""): ?>
            <div class="alert alert-warning border-0"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="destination_id" value="<?= (int)$destination["destination_id"] ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="destination-name">Name</label>
                    <input class="form-control" id="destination-name" name="name" value="<?= htmlspecialchars($destination["name"]) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="destination-country">Country</label>
                    <input class="form-control" id="destination-country" name="country" value="<?= htmlspecialchars($destination["country"]) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="destination-description">Description</label>
                    <textarea class="form-control" id="destination-description" name="description" rows="4"><?= htmlspecialchars((string)($destination["description"] ?? "")) ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary" type="submit">Update destination</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
