<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
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
        pay.payment_id,
        pay.status AS payment_status
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
    setFlash("danger", "Booking not found.");
    redirectTo(BOOKINGS_PAGE_PATH);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $booking["payment_status"] === "pending") {
    verifyCsrf($_POST["csrf_token"] ?? null);

    $cardName = trim($_POST["card_name"] ?? "");
    $cardNumber = preg_replace('/\D+/', '', $_POST["card_number"] ?? "") ?? "";
    $paymentAction = $_POST["payment_action"] ?? "success";

    if ($cardName === "" || strlen((string)$cardNumber) < 12) {
        $message = "Enter a valid mock card name and card number.";
    } else {
        $pdo->beginTransaction();
        try {
            $freshStmt = $pdo->prepare(
                "SELECT b.booking_id, b.package_id, b.num_travelers, b.status,
                        pay.payment_id, pay.status AS payment_status
                 FROM bookings b
                 INNER JOIN payments pay ON pay.booking_id = b.booking_id
                 WHERE b.booking_id = :booking_id AND b.user_id = :user_id
                 FOR UPDATE"
            );
            $freshStmt->execute([
                "booking_id" => $bookingId,
                "user_id" => (int)$_SESSION["user_id"],
            ]);
            $freshBooking = $freshStmt->fetch();

            // Lock package row for slot restoration safety.
            $pkgLock = $pdo->prepare("SELECT package_id FROM packages WHERE package_id = :id FOR UPDATE");
            $pkgLock->execute(["id" => (int)$freshBooking["package_id"]]);

            if (!$freshBooking || $freshBooking["payment_status"] !== "pending") {
                $pdo->rollBack();
                redirectTo(BOOKINGS_PAGE_PATH);
            }

            if ($paymentAction === "success") {
                $pdo->prepare(
                    "UPDATE payments
                     SET status = 'success', transaction_ref = :transaction_ref
                     WHERE payment_id = :payment_id"
                )->execute([
                    "transaction_ref" => "TXN-" . bin2hex(random_bytes(6)),
                    "payment_id" => (int)$freshBooking["payment_id"],
                ]);

                $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = :booking_id")
                    ->execute(["booking_id" => $bookingId]);

                // Slots already held at booking creation — do not deduct again.
                $pdo->commit();
                setFlash("success", "Payment successful. Your booking is now confirmed.");
                redirectTo(BOOKINGS_PAGE_PATH);
            }

            $pdo->prepare(
                "UPDATE payments
                 SET status = 'failed', transaction_ref = :transaction_ref
                 WHERE payment_id = :payment_id"
            )->execute([
                "transaction_ref" => "FAILED-" . bin2hex(random_bytes(4)),
                "payment_id" => (int)$freshBooking["payment_id"],
            ]);

            $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :booking_id")
                ->execute(["booking_id" => $bookingId]);

            $pdo->prepare(
                "UPDATE packages
                 SET available_slots = available_slots + :num_travelers
                 WHERE package_id = :package_id"
            )->execute([
                "num_travelers" => (int)$freshBooking["num_travelers"],
                "package_id" => (int)$freshBooking["package_id"],
            ]);

            $pdo->commit();
            setFlash("danger", "Payment failed. The booking was cancelled and slots were released.");
            redirectTo(BOOKINGS_PAGE_PATH);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Payment processing failed. Please try again.";
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="row justify-content-center animate-slide-up">
    <div class="col-lg-6">
        <div class="card card-modern">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h3 class="fw-bold mb-0">Secure Checkout</h3>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl(BOOKINGS_PAGE_PATH)) ?>">My Bookings</a>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Package</span>
                        <span class="fw-semibold"><?= htmlspecialchars($booking["title"]) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Travelers</span>
                        <span class="fw-semibold"><?= (int)$booking["num_travelers"] ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="fw-semibold">Total</span>
                        <span class="fs-4 fw-bold text-primary">Rs. <?= number_format((float)$booking["total_price"], 2) ?></span>
                    </div>
                </div>

                <?php if ($booking["payment_status"] !== "pending"): ?>
                    <div class="alert alert-info mb-0">This payment has already been processed.</div>
                <?php else: ?>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="card-name">Card Holder Name</label>
                            <input class="form-control" id="card-name" name="card_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="card-number">Mock Card Number</label>
                            <input class="form-control" id="card-number" name="card_number" inputmode="numeric" placeholder="4111111111111111" required>
                            <div class="form-text">Sandbox only — card details are not stored.</div>
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
