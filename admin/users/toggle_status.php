<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();

const USERS_PAGE_PATH = "/admin/users/list.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo(USERS_PAGE_PATH);
}

$userId = (int)($_POST["user_id"] ?? 0);
$status = $_POST["status"] ?? "";

if ($userId > 0 && $userId !== (int)$_SESSION["user_id"] && in_array($status, ["active", "inactive"], true)) {
    $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE user_id = :user_id");
    $stmt->execute([
        "status" => $status,
        "user_id" => $userId,
    ]);
}

redirectTo(USERS_PAGE_PATH);
