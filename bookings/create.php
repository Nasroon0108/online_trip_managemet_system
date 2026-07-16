<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /trips/list.php");
    exit;
}

$tripId = (int)($_POST["trip_id"] ?? 0);
$userId = (int)$_SESSION["user_id"];

if ($tripId <= 0) {
    header("Location: /trips/list.php");
    exit;
}

$pdo->beginTransaction();
try {
    $tripStmt = $pdo->prepare("SELECT id, available_slots FROM trips WHERE id = :id FOR UPDATE");
    $tripStmt->execute(["id" => $tripId]);
    $trip = $tripStmt->fetch();

    if (!$trip || (int)$trip["available_slots"] <= 0) {
        $pdo->rollBack();
        header("Location: /trips/list.php");
        exit;
    }

    $existsStmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = :user_id AND trip_id = :trip_id");
    $existsStmt->execute(["user_id" => $userId, "trip_id" => $tripId]);
    if ($existsStmt->fetch()) {
        $pdo->rollBack();
        header("Location: /bookings/my_bookings.php");
        exit;
    }

    $bookStmt = $pdo->prepare("INSERT INTO bookings (user_id, trip_id) VALUES (:user_id, :trip_id)");
    $bookStmt->execute(["user_id" => $userId, "trip_id" => $tripId]);

    $updateTrip = $pdo->prepare("UPDATE trips SET available_slots = available_slots - 1 WHERE id = :id");
    $updateTrip->execute(["id" => $tripId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

header("Location: /bookings/my_bookings.php");
exit;
