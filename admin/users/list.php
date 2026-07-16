<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

$users = $pdo->query(
    "SELECT user_id, name, email, phone, role, status, created_at
     FROM users
     ORDER BY FIELD(role, 'admin', 'agent', 'traveler'), created_at DESC"
)->fetchAll();
?>

<div class="page-header mb-4">
    <div>
        <h2 class="mb-1">Manage Users</h2>
        <p class="text-muted mb-0">Activate accounts and assign roles: Traveler, Agent, or Admin.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">Dashboard</a>
</div>

<div class="card card-modern">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                    <td class="fw-semibold"><?= htmlspecialchars($user["name"]) ?></td>
                    <td><?= htmlspecialchars($user["email"]) ?></td>
                    <td><?= htmlspecialchars((string)($user["phone"] ?? "—")) ?></td>
                    <td>
                        <?php if ((int)$user["user_id"] === (int)$_SESSION["user_id"]): ?>
                            <span class="badge badge-role badge-role-<?= htmlspecialchars($user["role"]) ?>"><?= htmlspecialchars($user["role"]) ?></span>
                        <?php else: ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/admin/users/update_role.php')) ?>" class="d-flex gap-2 align-items-center">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= (int)$user["user_id"] ?>">
                                <select class="form-select form-select-sm" name="role" style="width: 120px;">
                                    <?php foreach (["traveler", "agent", "admin"] as $roleOption): ?>
                                        <option value="<?= $roleOption ?>" <?= $user["role"] === $roleOption ? "selected" : "" ?>>
                                            <?= ucfirst($roleOption) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $user["status"] === "active" ? "text-bg-success" : "text-bg-secondary" ?>">
                            <?= htmlspecialchars($user["status"]) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= htmlspecialchars($user["created_at"]) ?></td>
                    <td>
                        <?php if ((int)$user["user_id"] !== (int)$_SESSION["user_id"]): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/admin/users/toggle_status.php')) ?>" class="d-inline">
                                <?= csrfField() ?>
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
