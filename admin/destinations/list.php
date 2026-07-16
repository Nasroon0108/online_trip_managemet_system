<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/header.php";
requireAdmin();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $country = trim($_POST["country"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($name === "" || $country === "") {
        $message = "Name and country are required.";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO destinations (name, country, description, status)
             VALUES (:name, :country, :description, 'active')"
        );
        $stmt->execute([
            "name" => $name,
            "country" => $country,
            "description" => $description !== "" ? $description : null,
        ]);
        $message = "Destination created successfully.";
    }
}

$destinations = $pdo->query(
    "SELECT destination_id, name, country, description, status, created_at
     FROM destinations
     ORDER BY created_at DESC, destination_id DESC"
)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Manage Destinations</h3>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">Back to Dashboard</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Add Destination</h5>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label" for="destination-name">Name</label>
                        <input class="form-control" id="destination-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="destination-country">Country</label>
                        <input class="form-control" id="destination-country" name="country" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="destination-description">Description</label>
                        <textarea class="form-control" id="destination-description" name="description" rows="4"></textarea>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Save Destination</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($destinations as $destination): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($destination["name"]) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars((string)($destination["description"] ?? "")) ?></div>
                            </td>
                            <td><?= htmlspecialchars($destination["country"]) ?></td>
                            <td><?= htmlspecialchars($destination["status"]) ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(appUrl('/admin/destinations/edit.php?id=' . (int)$destination["destination_id"])) ?>">Edit</a>
                                <form method="post" action="<?= htmlspecialchars(appUrl('/admin/destinations/toggle_status.php')) ?>" class="d-inline">
                                    <input type="hidden" name="destination_id" value="<?= (int)$destination["destination_id"] ?>">
                                    <input type="hidden" name="status" value="<?= $destination["status"] === "active" ? "inactive" : "active" ?>">
                                    <button class="btn btn-sm btn-outline-warning" type="submit">
                                        <?= $destination["status"] === "active" ? "Deactivate" : "Activate" ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$destinations): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No destinations yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
