<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

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

<div class="page-header">
    <div>
        <p class="page-kicker mb-1">Location management</p>
        <h2>Destinations</h2>
        <p class="text-muted mb-0">Create and maintain travel destinations linked to packages.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">
        <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-modern">
            <div class="card-body p-4">
                <h5 class="mb-3">Add destination</h5>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-info border-0"><?= htmlspecialchars($message) ?></div>
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
                    <button class="btn btn-primary w-100" type="submit">Save destination</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-modern card-panel">
            <div class="card-body p-0">
                <div class="panel-toolbar">
                    <div>
                        <h5 class="mb-0">All destinations</h5>
                        <p class="text-muted small mb-0"><?= count($destinations) ?> recorded</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-app align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Country</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($destinations as $destination): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($destination["name"]) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth((string)($destination["description"] ?? ""), 0, 80, "…")) ?></div>
                                </td>
                                <td><?= htmlspecialchars($destination["country"]) ?></td>
                                <td><span class="badge badge-soft"><?= htmlspecialchars($destination["status"]) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/destinations/edit.php?id=' . (int)$destination["destination_id"])) ?>">Edit</a>
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
                            <tr><td colspan="4" class="text-center text-muted py-5">No destinations yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
