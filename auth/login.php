<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("SELECT user_id, name, password_hash, role, status FROM users WHERE email = :email");
    $stmt->execute(["email" => $email]);
    $user = $stmt->fetch();

    if ($user && $user["status"] === "active" && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = (int)$user["user_id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_role"] = $user["role"];
        redirectTo("/trips/list.php");
    }

    $message = "Invalid email or password.";
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title mb-3">Login</h3>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label" for="login-email">Email</label>
                        <input class="form-control" id="login-email" type="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="login-password">Password</label>
                        <input class="form-control" id="login-password" type="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
