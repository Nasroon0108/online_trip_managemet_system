<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo("/admin/packages/list.php");
}

$packageId = (int)($_POST["package_id"] ?? 0);
$status = $_POST["status"] ?? "";

if ($packageId > 0 && in_array($status, ["active", "inactive"], true)) {
    $stmt = $pdo->prepare("UPDATE packages SET status = :status WHERE package_id = :id");
    $stmt->execute([
        "status" => $status,
        "id" => $packageId,
    ]);
}

redirectTo("/admin/packages/list.php");
