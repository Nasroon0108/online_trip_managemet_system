<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireTraveler();

const BOOKINGS_PAGE_PATH = "/bookings/my_bookings.php";

$bookingId = (int)($_GET["booking_id"] ?? $_POST["booking_id"] ?? 0);
if ($bookingId <= 0) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$bookingStmt = $pdo->prepare(
    "SELECT
        b.booking_id,
        b.user_id,
        b.package_id,
        b.num_travelers,
        b.total_price,
        b.status,
        p.title,
        p.available_slots,
        pay.payment_id,
        pay.status AS payment_status,
        pay.payment_method
     FROM bookings b
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     WHERE b.booking_id = :booking_id AND b.user_id = :user_id"
);
$bookingStmt->execute([
    "booking_id" => $bookingId,
    "user_id" => (int)$_SESSION["user_id"],
]);
$booking = $bookingStmt->fetch();

if (!$booking) {
    redirectTo(BOOKINGS_PAGE_PATH);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $booking["payment_status"] === "pending") {
    $cardName = trim($_POST["card_name"] ?? "");
    $cardNumber = preg_replace('/\D+/', '', $_POST["card_number"] ?? "");
    $paymentAction = $_POST["payment_action"] ?? "success";

    if ($cardName === "" || strlen($cardNumber) < 12) {
        $message = "Enter a valid mock card name and card number.";
    } else {
        $pdo->beginTransaction();
        try {
            $freshStmt = $pdo->prepare(
                "SELECT b.booking_id, b.package_id, b.num_travelers, b.status, pay.payment_id, pay.status AS payment_status, p.available_slots
                 FROM bookings b
                 INNER JOIN payments pay ON pay.booking_id = b.booking_id
                 INNER JOIN packages p ON p.package_id = b.package_id
                 WHERE b.booking_id = :booking_id AND b.user_id = :user_id
                 FOR UPDATE"
            );
            $freshStmt->execute([
                "booking_id" => $bookingId,
                "user_id" => (int)$_SESSION["user_id"],
            ]);
            $freshBooking = $freshStmt->fetch();

            if (!$freshBooking || $freshBooking["payment_status"] !== "pending") {
                $pdo->rollBack();
                redirectTo(BOOKINGS_PAGE_PATH);
            }

            if ($paymentAction === "success") {
                if ((int)$freshBooking["available_slots"] < (int)$freshBooking["num_travelers"]) {
                    $failBookingStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :booking_id");
                    $failBookingStmt->execute(["booking_id" => $bookingId]);

                    $failPaymentStmt = $pdo->prepare(
                        "UPDATE payments
                         SET status = 'failed', transaction_ref = :transaction_ref
                         WHERE payment_id = :payment_id"
                    );
                    $failPaymentStmt->execute([
                        "transaction_ref" => "FAILED-" . bin2hex(random_bytes(4)),
                        "payment_id" => (int)$freshBooking["payment_id"],
                    ]);

                    $pdo->commit();
                    setFlash("warning", "Payment failed because the package no longer has enough slots.");
                    redirectTo(BOOKINGS_PAGE_PATH);
                }

                $paymentStmt = $pdo->prepare(
                    "UPDATE payments
                     SET status = 'success', transaction_ref = :transaction_ref
                     WHERE payment_id = :payment_id"
                );
                $paymentStmt->execute([
                    "transaction_ref" => "TXN-" . bin2hex(random_bytes(6)),
                    "payment_id" => (int)$freshBooking["payment_id"],
                ]);

                $bookingUpdateStmt = $pdo->prepare(
                    "UPDATE bookings
                     SET status = 'confirmed'
                     WHERE booking_id = :booking_id"
                );
                $bookingUpdateStmt->execute(["booking_id" => $bookingId]);

                $slotsStmt = $pdo->prepare(
                    "UPDATE packages
                     SET available_slots = available_slots - :num_travelers
                     WHERE package_id = :package_id"
                );
                $slotsStmt->execute([
                    "num_travelers" => (int)$freshBooking["num_travelers"],
                    "package_id" => (int)$freshBooking["package_id"],
                ]);
            } else {
                $paymentStmt = $pdo->prepare(
                    "UPDATE payments
                     SET status = 'failed', transaction_ref = :transaction_ref
                     WHERE payment_id = :payment_id"
                );
                $paymentStmt->execute([
                    "transaction_ref" => "FAILED-" . bin2hex(random_bytes(4)),
                    "payment_id" => (int)$freshBooking["payment_id"],
                ]);

                $bookingUpdateStmt = $pdo->prepare(
                    "UPDATE bookings
                     SET status = 'cancelled'
                     WHERE booking_id = :booking_id"
                );
                $bookingUpdateStmt->execute(["booking_id" => $bookingId]);
            }

            $pdo->commit();
            if ($paymentAction === "success") {
                setFlash("success", "Payment successful. Your booking is now confirmed.");
            } else {
                setFlash("danger", "Payment failed. The booking was cancelled.");
            }
            redirectTo(BOOKINGS_PAGE_PATH);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Payment processing failed. Please try again.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">Mock Payment</h3>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars(appUrl(BOOKINGS_PAGE_PATH)) ?>">Back to My Bookings</a>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <div class="mb-4">
                    <div><strong>Package:</strong> <?= htmlspecialchars($booking["title"]) ?></div>
                    <div><strong>Travelers:</strong> <?= htmlspecialchars((string)$booking["num_travelers"]) ?></div>
                    <div><strong>Total Amount:</strong> Rs. <?= htmlspecialchars((string)$booking["total_price"]) ?></div>
                    <div><strong>Booking Status:</strong> <?= htmlspecialchars($booking["status"]) ?></div>
                    <div><strong>Payment Status:</strong> <?= htmlspecialchars($booking["payment_status"]) ?></div>
                </div>

                <?php if ($booking["payment_status"] !== "pending"): ?>
                    <div class="alert alert-info mb-0">This payment has already been processed.</div>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="card-name">Card Holder Name</label>
                            <input class="form-control" id="card-name" name="card_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="card-number">Mock Card Number</label>
                            <input class="form-control" id="card-number" name="card_number" inputmode="numeric" placeholder="4111111111111111" required>
                            <div class="form-text">This is a sandbox form. Card details are not stored.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success flex-fill" type="submit" name="payment_action" value="success">Pay Success</button>
                            <button class="btn btn-outline-danger flex-fill" type="submit" name="payment_action" value="failed">Simulate Failure</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
