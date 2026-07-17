<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();
require_once __DIR__ . "/../../includes/header.php";

$users = $pdo->query(
    "SELECT user_id, name, email, phone, role, status, created_at
     FROM users
     ORDER BY FIELD(role, 'admin', 'traveler'), created_at DESC"
)->fetchAll();
?>

<div class="page-header">
    <div>
        <p class="page-kicker mb-1">User management</p>
        <h2>Users & Roles</h2>
        <p class="text-muted mb-0">Activate accounts and assign roles: Traveler or Admin.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">
        <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
    </a>
</div>

<div class="card card-modern card-panel">
    <div class="card-body p-0">
        <div class="panel-toolbar">
            <div>
                <h5 class="mb-0">User directory</h5>
                <p class="text-muted small mb-0"><?= count($users) ?> account<?= count($users) === 1 ? "" : "s" ?></p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-app align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
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
                                        <?php foreach (["traveler", "admin"] as $roleOption): ?>
                                            <option value="<?= $roleOption ?>" <?= $user["role"] === $roleOption ? "selected" : "" ?>>
                                                <?= ucfirst($roleOption) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-soft"><?= htmlspecialchars($user["status"]) ?></span></td>
                        <td class="small text-muted text-nowrap"><?= htmlspecialchars($user["created_at"]) ?></td>
                        <td class="text-end">
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
                    <tr><td colspan="7" class="text-center text-muted py-5">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
