<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

$packageId = (int)($_GET["package_id"] ?? $_POST["package_id"] ?? 0);
if ($packageId <= 0) {
    redirectTo("/admin/packages/list.php");
}

$packageStmt = $pdo->prepare("SELECT package_id, title FROM packages WHERE package_id = :id");
$packageStmt->execute(["id" => $packageId]);
$package = $packageStmt->fetch();
if (!$package) {
    redirectTo("/admin/packages/list.php");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $agentId = (int)($_POST["agent_id"] ?? 0);

    if ($action === "assign" && $agentId > 0) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE user_id = :id AND role = 'agent' AND status = 'active'");
        $check->execute(["id" => $agentId]);
        if ($check->fetch()) {
            try {
                $insert = $pdo->prepare(
                    "INSERT INTO agent_assignments (agent_id, package_id)
                     VALUES (:agent_id, :package_id)"
                );
                $insert->execute([
                    "agent_id" => $agentId,
                    "package_id" => $packageId,
                ]);
                $message = "Agent assigned successfully.";
            } catch (Throwable $e) {
                $message = "Agent is already assigned to this package.";
            }
        }
    }

    if ($action === "remove" && $agentId > 0) {
        $delete = $pdo->prepare(
            "DELETE FROM agent_assignments
             WHERE agent_id = :agent_id AND package_id = :package_id"
        );
        $delete->execute([
            "agent_id" => $agentId,
            "package_id" => $packageId,
        ]);
        $message = "Agent removed from package.";
    }
}

$agents = $pdo->query(
    "SELECT user_id, name, email
     FROM users
     WHERE role = 'agent' AND status = 'active'
     ORDER BY name ASC"
)->fetchAll();

$assignedStmt = $pdo->prepare(
    "SELECT u.user_id, u.name, u.email, aa.assignment_id
     FROM agent_assignments aa
     INNER JOIN users u ON u.user_id = aa.agent_id
     WHERE aa.package_id = :package_id
     ORDER BY u.name ASC"
);
$assignedStmt->execute(["package_id" => $packageId]);
$assignedAgents = $assignedStmt->fetchAll();
$assignedIds = array_map("intval", array_column($assignedAgents, "user_id"));
?>

<div class="page-header mb-4">
    <div>
        <h2 class="mb-1">Assign Agents</h2>
        <p class="text-muted mb-0">Package: <strong><?= htmlspecialchars($package["title"]) ?></strong></p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/packages/list.php')) ?>">Back</a>
</div>

<?php if ($message !== ""): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-modern">
            <div class="card-body">
                <h5 class="mb-3">Assign Agent</h5>
                <?php if (!$agents): ?>
                    <p class="text-muted mb-0">No active agents found. Create an agent user first from Manage Users.</p>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="assign">
                        <div class="mb-3">
                            <label class="form-label" for="agent-id">Select Agent</label>
                            <select class="form-select" id="agent-id" name="agent_id" required>
                                <option value="">Choose agent...</option>
                                <?php foreach ($agents as $agent): ?>
                                    <?php if (!in_array((int)$agent["user_id"], $assignedIds, true)): ?>
                                        <option value="<?= (int)$agent["user_id"] ?>">
                                            <?= htmlspecialchars($agent["name"]) ?> (<?= htmlspecialchars($agent["email"]) ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">Assign Agent</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-modern">
            <div class="card-body">
                <h5 class="mb-3">Assigned Agents</h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($assignedAgents as $agent): ?>
                            <tr>
                                <td><?= htmlspecialchars($agent["name"]) ?></td>
                                <td><?= htmlspecialchars($agent["email"]) ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="agent_id" value="<?= (int)$agent["user_id"] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$assignedAgents): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">No agents assigned yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
