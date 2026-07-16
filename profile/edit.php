<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireLogin();

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

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">My Profile</h3>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label" for="profile-name">Name</label>
                        <input class="form-control" id="profile-name" name="name" value="<?= htmlspecialchars($user["name"]) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="profile-email">Email</label>
                        <input class="form-control" id="profile-email" type="email" value="<?= htmlspecialchars($user["email"]) ?>" disabled>
                        <div class="form-text">Email cannot be changed in this version.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="profile-phone">Phone</label>
                        <input class="form-control" id="profile-phone" name="phone" value="<?= htmlspecialchars((string)($user["phone"] ?? "")) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="profile-role">Role</label>
                        <input class="form-control" id="profile-role" value="<?= htmlspecialchars($user["role"]) ?>" disabled>
                    </div>

                    <hr>
                    <h5 class="mb-3">Change Password (optional)</h5>
                    <div class="mb-3">
                        <label class="form-label" for="current-password">Current Password</label>
                        <input class="form-control" id="current-password" type="password" name="current_password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new-password">New Password</label>
                        <input class="form-control" id="new-password" type="password" name="new_password">
                    </div>

                    <button class="btn btn-primary" type="submit">Save Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
