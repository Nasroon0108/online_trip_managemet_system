<?php
declare(strict_types=1);

/**
 * Shared booking status transitions for admin/traveler cancel flows.
 *
 * @return array{ok:bool,message:string}
 */
function applyBookingStatusChange(PDO $pdo, int $bookingId, string $newStatus, string $actor = "system"): array
{
    if (!in_array($newStatus, ["cancelled", "completed"], true)) {
        return ["ok" => false, "message" => "Unsupported status."];
    }

    $stmt = $pdo->prepare(
        "SELECT b.booking_id, b.package_id, b.num_travelers, b.status, pay.payment_id, pay.status AS payment_status
         FROM bookings b
         INNER JOIN payments pay ON pay.booking_id = b.booking_id
         WHERE b.booking_id = :booking_id
         FOR UPDATE"
    );
    $stmt->execute(["booking_id" => $bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        return ["ok" => false, "message" => "Booking not found."];
    }

    if ($newStatus === "completed") {
        if ($booking["status"] !== "confirmed") {
            return ["ok" => false, "message" => "Only confirmed bookings can be completed."];
        }
        $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE booking_id = :booking_id")
            ->execute(["booking_id" => $bookingId]);
        return ["ok" => true, "message" => "Booking marked as completed."];
    }

    // cancelled
    if (!in_array($booking["status"], ["pending", "confirmed"], true)) {
        return ["ok" => false, "message" => "This booking cannot be cancelled."];
    }

    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :booking_id")
        ->execute(["booking_id" => $bookingId]);

    if ($booking["payment_status"] === "pending") {
        $pdo->prepare(
            "UPDATE payments
             SET status = 'failed', transaction_ref = :transaction_ref
             WHERE payment_id = :payment_id"
        )->execute([
            "transaction_ref" => strtoupper($actor) . "-CANCEL-" . bin2hex(random_bytes(4)),
            "payment_id" => (int)$booking["payment_id"],
        ]);
    }

    // Slots were held at booking creation for pending/confirmed; always restore on cancel.
    $pdo->prepare(
        "UPDATE packages
         SET available_slots = available_slots + :num_travelers
         WHERE package_id = :package_id"
    )->execute([
        "num_travelers" => (int)$booking["num_travelers"],
        "package_id" => (int)$booking["package_id"],
    ]);

    return ["ok" => true, "message" => "Booking cancelled successfully."];
}
