<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

if (isLoggedIn()) {
    redirectTo(dashboardPath());
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $message = "All fields are required.";
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $check->execute(["email" => $email]);

        if ($check->fetch()) {
            $message = "Email already exists.";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, phone, role, status)
                 VALUES (:name, :email, :password_hash, :phone, 'traveler', 'active')"
            );
            $stmt->execute([
                "name" => $name,
                "email" => $email,
                "password_hash" => password_hash($password, PASSWORD_DEFAULT),
                "phone" => $phone !== "" ? $phone : null,
            ]);
            setFlash("success", "Account created. Please log in.");
            redirectTo("/auth/login.php");
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-5">
<div class="row justify-content-center animate-slide-up">
    <div class="col-lg-9 col-xl-7">
        <div class="card card-modern overflow-hidden" style="border-radius: 20px !important;">
          <div class="row g-0">
            <!-- Left decorative panel -->
            <div class="col-md-4 d-none d-md-flex flex-column justify-content-between p-4" style="background: linear-gradient(160deg, #1d4e89 0%, #0d9488 100%); color:#fff;">
                <div>
                    <div class="mb-4" style="width:2.5rem;height:2.5rem;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-suitcase-rolling"></i>
                    </div>
                    <h4 style="font-family:'Fraunces',serif;color:#fff;font-size:1.35rem;line-height:1.25;">Join Trip Ease</h4>
                    <p style="color:rgba(255,255,255,0.75);font-size:0.85rem;margin-top:0.75rem;">Create your traveler account and start exploring Sri Lanka's finest tour packages.</p>
                </div>
                <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);">© <?= date('Y') ?> Trip Ease</div>
            </div>
            <!-- Right form panel -->
            <div class="col-md-8">
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <h3 class="mb-1" style="font-size:1.5rem;font-weight:700;">Create account</h3>
                        <p class="text-muted small mb-0">Sign up as a traveler — it's free</p>
                    </div>

                    <?php if ($message !== ""): ?>
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                            <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                            <span><?= htmlspecialchars($message) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="register-name">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                    <input class="form-control" id="register-name" name="name" placeholder="John Doe" required autocomplete="name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="register-email">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                    <input class="form-control" id="register-email" type="email" name="email" placeholder="john@example.com" required autocomplete="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="register-phone">Phone <span class="text-muted fw-normal" style="text-transform:none;">(optional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                    <input class="form-control" id="register-phone" name="phone" placeholder="+94 7X XXX XXXX" autocomplete="tel">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="register-password">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                    <input class="form-control" id="register-password" type="password" name="password" placeholder="Min. 6 characters" required autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary w-100 py-2 mt-4 mb-3" type="submit" style="font-size:0.95rem;">
                            <i class="fa-solid fa-user-plus me-2"></i>Create Account
                        </button>
                        
                        <div class="text-center">
                            <span class="text-muted small">Already have an account?</span>
                            <a class="small fw-semibold text-decoration-none ms-1" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>" style="color:var(--primary);">Sign in</a>
                        </div>
                    </form>
                </div>
            </div>
          </div>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
