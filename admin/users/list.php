<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/header.php";
requireAdmin();

$users = $pdo->query(
    "SELECT user_id, name, email, phone, role, status, created_at
     FROM users
     ORDER BY created_at DESC, user_id DESC"
)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Manage Users</h3>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">Dashboard</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user["name"]) ?></td>
                    <td><?= htmlspecialchars($user["email"]) ?></td>
                    <td><?= htmlspecialchars((string)($user["phone"] ?? "—")) ?></td>
                    <td><?= htmlspecialchars($user["role"]) ?></td>
                    <td><?= htmlspecialchars($user["status"]) ?></td>
                    <td><?= htmlspecialchars($user["created_at"]) ?></td>
                    <td>
                        <?php if ((int)$user["user_id"] !== (int)$_SESSION["user_id"]): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/admin/users/toggle_status.php')) ?>" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= (int)$user["user_id"] ?>">
                                <input type="hidden" name="status" value="<?= $user["status"] === "active" ? "inactive" : "active" ?>">
                                <button class="btn btn-sm btn-outline-warning" type="submit">
                                    <?= $user["status"] === "active" ? "Deactivate" : "Activate" ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">Current user</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
