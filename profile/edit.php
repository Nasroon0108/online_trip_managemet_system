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
?>

<div class="row justify-content-center animate-slide-up">
    <div class="col-lg-6">
        <div class="card card-modern">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-modern mb-2">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <h3 class="fw-bold text-dark">My Profile</h3>
                    <p class="text-muted small">Update your personal account credentials and password</p>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-3 fs-4 text-warning"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label small" for="profile-name">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-signature text-muted"></i></span>
                            <input class="form-control border-start-0 ps-0" id="profile-name" name="name" value="<?= htmlspecialchars($user["name"]) ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small" for="profile-email">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input class="form-control border-start-0 ps-0 bg-light" id="profile-email" type="email" value="<?= htmlspecialchars($user["email"]) ?>" disabled>
                        </div>
                        <div class="form-text small text-muted"><i class="fa-solid fa-lock me-1"></i>Email address cannot be changed.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small" for="profile-phone">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                            <input class="form-control border-start-0 ps-0" id="profile-phone" name="phone" value="<?= htmlspecialchars((string)($user["phone"] ?? "")) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small" for="profile-role">Account Role</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-shield text-muted"></i></span>
                            <input class="form-control border-start-0 ps-0 bg-light" id="profile-role" value="<?= ucfirst(htmlspecialchars($user["role"])) ?>" disabled>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-4 border border-light">
                        <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-key me-2 text-muted"></i>Change Password</h5>
                        
                        <div class="mb-3">
                            <label class="form-label small" for="current-password">Current Password</label>
                            <input class="form-control bg-white" id="current-password" type="password" name="current_password" placeholder="Enter current password">
                        </div>
                        
                        <div class="mb-0">
                            <label class="form-label small" for="new-password">New Password</label>
                            <input class="form-control bg-white" id="new-password" type="password" name="new_password" placeholder="Enter new password (min. 6 chars)">
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-2.5" type="submit">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Details
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
