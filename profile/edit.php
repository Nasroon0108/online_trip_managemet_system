<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();
require_once __DIR__ . "/../includes/header.php";

$userId = (int)$_SESSION["user_id"];
$message = "";

$stmt = $pdo->prepare("SELECT user_id, name, email, phone, role FROM users WHERE user_id = :id");
$stmt->execute(["id" => $userId]);
$user = $stmt->fetch();

if (!$user) {
    redirectTo("/index.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword = $_POST["new_password"] ?? "";

    if ($name === "") {
        $message = "Name is required.";
    } elseif ($newPassword !== "" && strlen($newPassword) < 6) {
        $message = "New password must be at least 6 characters.";
    } else {
        $passwordStmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :id");
        $passwordStmt->execute(["id" => $userId]);
        $passwordHash = (string)$passwordStmt->fetchColumn();

        if ($newPassword !== "" && !password_verify($currentPassword, $passwordHash)) {
            $message = "Current password is incorrect.";
        } else {
            if ($newPassword !== "") {
                $updateStmt = $pdo->prepare(
                    "UPDATE users SET name = :name, phone = :phone, password_hash = :password_hash WHERE user_id = :id"
                );
                $updateStmt->execute([
                    "name" => $name,
                    "phone" => $phone !== "" ? $phone : null,
                    "password_hash" => password_hash($newPassword, PASSWORD_DEFAULT),
                    "id" => $userId,
                ]);
            } else {
                $updateStmt = $pdo->prepare(
                    "UPDATE users SET name = :name, phone = :phone WHERE user_id = :id"
                );
                $updateStmt->execute([
                    "name" => $name,
                    "phone" => $phone !== "" ? $phone : null,
                    "id" => $userId,
                ]);
            }

            $_SESSION["user_name"] = $name;
            setFlash("success", "Profile updated successfully.");
            redirectTo("/profile/edit.php");
        }
    }
}

$backHref = appUrl(dashboardPath());
?>

<div class="page-header">
    <div>
        <p class="page-kicker mb-1">Account</p>
        <h2>Profile Settings</h2>
        <p class="text-muted mb-0">Update your personal details and change your password.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($backHref) ?>">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to workspace
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-modern">
            <div class="panel-toolbar">
                <div>
                    <h5 class="mb-0"><i class="fa-solid fa-user me-2 text-primary" style="font-size:0.9rem;"></i>Personal Information</h5>
                    <p class="text-muted small mb-0">Update your name and contact details</p>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="profile-name">Full Name</label>
                            <input class="form-control" id="profile-name" name="name" value="<?= htmlspecialchars($user["name"]) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="profile-email">Email Address</label>
                            <input class="form-control" id="profile-email" type="email" value="<?= htmlspecialchars($user["email"]) ?>" disabled>
                            <div class="form-text"><i class="fa-solid fa-lock me-1"></i>Email cannot be changed.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="profile-phone">Phone Number</label>
                            <input class="form-control" id="profile-phone" name="phone" value="<?= htmlspecialchars((string)($user["phone"] ?? "")) ?>" placeholder="e.g. 0712345678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="profile-role">Role</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                                <input class="form-control" id="profile-role" value="<?= htmlspecialchars(ucfirst($user["role"])) ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="stat-icon" style="width:2rem;height:2rem;font-size:0.8rem;flex-shrink:0;">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Change Password</h5>
                            <p class="text-muted small mb-0">Leave blank to keep your current password</p>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="current-password">Current Password</label>
                            <input class="form-control" id="current-password" type="password" name="current_password" autocomplete="current-password" placeholder="Enter current password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-password">New Password</label>
                            <input class="form-control" id="new-password" type="password" name="new_password" autocomplete="new-password" placeholder="Minimum 6 characters">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                        </button>
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($backHref) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-modern">
            <div class="card-body p-4 text-center">
                <div class="avatar-circle mx-auto mb-3" style="width:4.5rem;height:4.5rem;font-size:1.75rem;">
                    <?= strtoupper(substr($user["name"], 0, 1)) ?>
                </div>
                <h5 class="mb-1"><?= htmlspecialchars($user["name"]) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($user["email"]) ?></p>
                <span class="badge badge-role badge-role-<?= htmlspecialchars($user["role"]) ?> px-3 py-2">
                    <?= htmlspecialchars(ucfirst($user["role"])) ?>
                </span>
                <?php if ($user["phone"] ?? ""): ?>
                    <div class="mt-3 pt-3 border-top text-muted small">
                        <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars((string)$user["phone"]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
