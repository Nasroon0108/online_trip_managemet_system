<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/booking_service.php";
requireAgent();

const BOOKINGS_PAGE_PATH = "/agent/bookings.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo(BOOKINGS_PAGE_PATH);
}

verifyCsrf($_POST["csrf_token"] ?? null);

$bookingId = (int)($_POST["booking_id"] ?? 0);
$status = $_POST["status"] ?? "";

if ($bookingId <= 0 || !in_array($status, ["cancelled", "completed"], true)) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

if (!agentCanAccessBooking($pdo, $bookingId, (int)$_SESSION["user_id"])) {
    setFlash("danger", "You can only manage bookings for packages assigned to you.");
    redirectTo(BOOKINGS_PAGE_PATH);
}

$pdo->beginTransaction();
try {
    $result = applyBookingStatusChange($pdo, $bookingId, $status, "agent");
    $pdo->commit();
    setFlash($result["ok"] ? "success" : "warning", $result["message"]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash("danger", "Could not update booking.");
}

redirectTo(BOOKINGS_PAGE_PATH);
