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
    $role = $_POST["role"] ?? "traveler";

    if (!in_array($role, ["traveler", "agent"], true)) {
        $role = "traveler";
    }

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
                 VALUES (:name, :email, :password_hash, :phone, :role, 'active')"
            );
            $stmt->execute([
                "name" => $name,
                "email" => $email,
                "password_hash" => password_hash($password, PASSWORD_DEFAULT),
                "phone" => $phone !== "" ? $phone : null,
                "role" => $role,
            ]);
            $roleLabel = $role === "agent" ? "agent" : "traveler";
            setFlash("success", "Account created as {$roleLabel}. Please log in.");
            redirectTo("/auth/login.php");
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">
<div class="row justify-content-center animate-slide-up" style="margin-top: 3vh;">
    <div class="col-md-6">
        <div class="card card-modern">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="feature-icon-modern mb-2">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Create Account</h3>
                    <p class="text-muted small">Sign up as a traveler or trip agent</p>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-3 fs-4 text-warning"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label small" for="register-role">Account type</label>
                        <select class="form-select" id="register-role" name="role" required>
                            <option value="traveler" selected>Traveler — browse and book trips</option>
                            <option value="agent">Trip Agent — manage assigned packages</option>
                        </select>
                        <div class="form-text">Admin accounts are created by an existing admin only.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-1">
                            <label class="form-label small" for="register-name">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-signature text-muted"></i></span>
                                <input class="form-control border-start-0 ps-0" id="register-name" name="name" placeholder="John Doe" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small" for="register-email">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                                <input class="form-control border-start-0 ps-0" id="register-email" type="email" name="email" placeholder="john@example.com" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-1">
                            <label class="form-label small" for="register-phone">Phone Number <span class="text-muted">(optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                                <input class="form-control border-start-0 ps-0" id="register-phone" name="phone" placeholder="+123456789">
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small" for="register-password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                                <input class="form-control border-start-0 ps-0" id="register-password" type="password" name="password" placeholder="••••••••" required>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-2.5 mt-4 mb-3" type="submit">
                        <i class="fa-solid fa-user-plus me-1"></i> Register Account
                    </button>
                    
                    <div class="text-center">
                        <span class="text-muted small">Already have an account?</span>
                        <a class="small fw-semibold text-primary text-decoration-none ms-1" href="<?= htmlspecialchars(appUrl('/auth/login.php')) ?>">Login here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
