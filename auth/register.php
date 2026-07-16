<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";

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

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title mb-3">Register</h3>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label" for="register-name">Name</label>
                        <input class="form-control" id="register-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="register-email">Email</label>
                        <input class="form-control" id="register-email" type="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="register-phone">Phone (optional)</label>
                        <input class="form-control" id="register-phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="register-password">Password</label>
                        <input class="form-control" id="register-password" type="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
