<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo("/admin/destinations/list.php");
}

$destinationId = (int)($_POST["destination_id"] ?? 0);
$status = $_POST["status"] ?? "";

if ($destinationId > 0 && in_array($status, ["active", "inactive"], true)) {
    $stmt = $pdo->prepare("UPDATE destinations SET status = :status WHERE destination_id = :id");
    $stmt->execute([
        "status" => $status,
        "id" => $destinationId,
    ]);
}

redirectTo("/admin/destinations/list.php");
