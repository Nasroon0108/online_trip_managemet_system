<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/header.php";
requireAdmin();

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

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">Edit Destination</h3>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="destination_id" value="<?= (int)$destination["destination_id"] ?>">
                    <div class="mb-3">
                        <label class="form-label" for="destination-name">Name</label>
                        <input class="form-control" id="destination-name" name="name" value="<?= htmlspecialchars($destination["name"]) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="destination-country">Country</label>
                        <input class="form-control" id="destination-country" name="country" value="<?= htmlspecialchars($destination["country"]) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="destination-description">Description</label>
                        <textarea class="form-control" id="destination-description" name="description" rows="4"><?= htmlspecialchars((string)($destination["description"] ?? "")) ?></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Update Destination</button>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars(appUrl('/admin/destinations/list.php')) ?>">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
