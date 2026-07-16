<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/booking_service.php";
requireTraveler();

const BOOKINGS_PAGE_PATH = "/bookings/my_bookings.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo(BOOKINGS_PAGE_PATH);
}

verifyCsrf($_POST["csrf_token"] ?? null);

$bookingId = (int)($_POST["booking_id"] ?? 0);
if ($bookingId <= 0) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$pdo->beginTransaction();
try {
    $ownerStmt = $pdo->prepare(
        "SELECT booking_id FROM bookings WHERE booking_id = :booking_id AND user_id = :user_id FOR UPDATE"
    );
    $ownerStmt->execute([
        "booking_id" => $bookingId,
        "user_id" => (int)$_SESSION["user_id"],
    ]);

    if (!$ownerStmt->fetch()) {
        $pdo->rollBack();
        setFlash("danger", "Booking not found.");
        redirectTo(BOOKINGS_PAGE_PATH);
    }

    $result = applyBookingStatusChange($pdo, $bookingId, "cancelled", "traveler");
    $pdo->commit();
    setFlash($result["ok"] ? "info" : "warning", $result["message"]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash("danger", "Could not cancel booking.");
}

redirectTo(BOOKINGS_PAGE_PATH);
