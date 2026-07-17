<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();

const USERS_PAGE_PATH = "/admin/users/list.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo(USERS_PAGE_PATH);
}

verifyCsrf($_POST["csrf_token"] ?? null);

$userId = (int)($_POST["user_id"] ?? 0);
$role = $_POST["role"] ?? "";

if ($userId > 0
    && $userId !== (int)$_SESSION["user_id"]
    && in_array($role, ["traveler", "admin"], true)
) {
    $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE user_id = :user_id");
    $stmt->execute([
        "role" => $role,
        "user_id" => $userId,
    ]);
    setFlash("success", "User role updated.");
}

redirectTo(USERS_PAGE_PATH);
