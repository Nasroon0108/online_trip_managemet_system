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

<div class="row justify-content-center animate-slide-up" style="margin-top: 5vh;">
    <div class="col-md-5">
        <div class="card card-modern">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-modern mb-2">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Welcome Back</h3>
                    <p class="text-muted small">Login to manage and book your TripEase trips</p>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
                        <i class="fa-solid fa-circle-exclamation me-3 fs-4"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label small" for="login-email">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input class="form-control border-start-0 ps-0" id="login-email" type="email" name="email" placeholder="name@example.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small mb-0" for="login-password">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                            <input class="form-control border-start-0 ps-0" id="login-password" type="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-2.5 mb-3" type="submit">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Sign In
                    </button>
                    
                    <div class="text-center">
                        <span class="text-muted small">Don't have an account?</span>
                        <a class="small fw-semibold text-primary text-decoration-none ms-1" href="<?= htmlspecialchars(appUrl('/auth/register.php')) ?>">Register here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
