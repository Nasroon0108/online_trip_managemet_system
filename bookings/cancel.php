<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireTraveler();

const BOOKINGS_PAGE_PATH = "/bookings/my_bookings.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$bookingId = (int)($_POST["booking_id"] ?? 0);
if ($bookingId <= 0) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        "SELECT b.booking_id, b.package_id, b.num_travelers, b.status, pay.payment_id, pay.status AS payment_status
         FROM bookings b
         INNER JOIN payments pay ON pay.booking_id = b.booking_id
         WHERE b.booking_id = :booking_id AND b.user_id = :user_id
         FOR UPDATE"
    );
    $stmt->execute([
        "booking_id" => $bookingId,
        "user_id" => (int)$_SESSION["user_id"],
    ]);
    $booking = $stmt->fetch();

    if ($booking && in_array($booking["status"], ["pending", "confirmed"], true)) {
        $bookingStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :booking_id");
        $bookingStmt->execute(["booking_id" => $bookingId]);

        if ($booking["payment_status"] === "pending") {
            $paymentStmt = $pdo->prepare(
                "UPDATE payments
                 SET status = 'failed', transaction_ref = :transaction_ref
                 WHERE payment_id = :payment_id"
            );
            $paymentStmt->execute([
                "transaction_ref" => "CANCELLED-" . bin2hex(random_bytes(4)),
                "payment_id" => (int)$booking["payment_id"],
            ]);
        } elseif ($booking["status"] === "confirmed") {
            $slotsStmt = $pdo->prepare(
                "UPDATE packages
                 SET available_slots = available_slots + :num_travelers
                 WHERE package_id = :package_id"
            );
            $slotsStmt->execute([
                "num_travelers" => (int)$booking["num_travelers"],
                "package_id" => (int)$booking["package_id"],
            ]);
        }
    }

    $pdo->commit();
    setFlash("info", "Booking cancelled successfully.");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

redirectTo(BOOKINGS_PAGE_PATH);
