<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

if (isLoggedIn()) {
    redirectTo(dashboardPath());
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("SELECT user_id, name, password_hash, role, status, profile_image FROM users WHERE email = :email");
    $stmt->execute(["email" => $email]);
    $user = $stmt->fetch();

    if ($user && $user["status"] === "active" && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = (int)$user["user_id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_role"] = $user["role"];
        $_SESSION["user_photo"] = $user["profile_image"];
        redirectTo(dashboardPath());
    }

    $message = "Invalid email or password.";
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-5">
<div class="row justify-content-center align-items-center animate-slide-up" style="min-height: 72vh;">
    <div class="col-md-10 col-lg-8 col-xl-6">
        <div class="row g-0 card card-modern overflow-hidden" style="border-radius: 20px !important;">
            <!-- Left decorative panel -->
            <div class="col-md-5 d-none d-md-flex flex-column justify-content-between p-4" style="background: linear-gradient(160deg, #0d9488 0%, #0f766e 50%, #134e4a 100%); color:#fff;">
                <div>
                    <div class="mb-4" style="width:2.5rem;height:2.5rem;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-plane-departure"></i>
                    </div>
                    <h4 style="font-family:'Fraunces',serif;color:#fff;font-size:1.5rem;line-height:1.2;">Plan your next journey</h4>
                    <p style="color:rgba(255,255,255,0.75);font-size:0.88rem;margin-top:0.75rem;">Browse curated Sri Lankan tour packages, book trips, and manage your travel all in one place.</p>
                </div>
                <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);">© <?= date('Y') ?> Trip Ease</div>
            </div>
            <!-- Right form panel -->
            <div class="col-md-7">
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <h3 class="mb-1" style="font-size:1.5rem;font-weight:700;">Welcome back</h3>
                        <p class="text-muted small mb-0">Sign in to your Trip Ease account</p>
                    </div>

                    <?php if ($message !== ""): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                            <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                            <span><?= htmlspecialchars($message) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label" for="login-email">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input class="form-control" id="login-email" type="email" name="email" placeholder="name@example.com" required autocomplete="email">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label" for="login-password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                <input class="form-control" id="login-password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            </div>
                        </div>

                        <button class="btn btn-primary w-100 py-2 mb-3" type="submit" style="font-size:0.95rem;">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                        </button>
                        
                        <div class="text-center">
                            <span class="text-muted small">No account yet?</span>
                            <a class="small fw-semibold text-decoration-none ms-1" href="<?= htmlspecialchars(appUrl('/auth/register.php')) ?>" style="color:var(--primary);">Create one</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
