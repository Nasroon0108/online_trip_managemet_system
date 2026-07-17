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
    <div class="col-lg-6 col-xl-5">
        <div class="page-header" style="border-bottom: none; padding-bottom: 0; margin-bottom: 1.5rem !important;">
            <div>
                <p class="page-kicker mb-1">Sandbox Payment</p>
                <h2 class="mb-0">Secure Checkout</h2>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(appUrl(BOOKINGS_PAGE_PATH)) ?>">
                <i class="fa-solid fa-arrow-left me-1"></i> My Bookings
            </a>
        </div>

        <?php if ($message !== ""): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Order summary -->
        <div class="card card-modern mb-4">
            <div class="panel-toolbar">
                <div>
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <span class="badge badge-custom badge-confirmed">
                    <i class="fa-solid fa-shield-check me-1"></i>Sandbox
                </span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color: var(--card-border) !important;">
                    <span class="text-muted small">Package</span>
                    <span class="fw-semibold"><?= htmlspecialchars($booking["title"]) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color: var(--card-border) !important;">
                    <span class="text-muted small">Travelers</span>
                    <span class="fw-semibold"><?= (int)$booking["num_travelers"] ?> person<?= (int)$booking["num_travelers"] > 1 ? 's' : '' ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-3">
                    <span class="fw-bold">Total Amount</span>
                    <span class="fw-bold" style="font-size:1.4rem;color:var(--primary);">Rs. <?= number_format((float)$booking["total_price"], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Payment form -->
        <div class="card card-modern">
            <div class="panel-toolbar">
                <div>
                    <h5 class="mb-0"><i class="fa-solid fa-credit-card me-2 text-primary" style="font-size:0.9rem;"></i>Mock Card Details</h5>
                    <p class="text-muted small mb-0">This is a sandbox — no real payment is processed</p>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($booking["payment_status"] !== "pending"): ?>
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-0">
                        <i class="fa-solid fa-circle-info flex-shrink-0"></i>
                        <span>This payment has already been processed.</span>
                    </div>
                <?php else: ?>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="card-name">Card Holder Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input class="form-control" id="card-name" name="card_name" placeholder="Full name on card" required autocomplete="cc-name">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="card-number">Mock Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                                <input class="form-control" id="card-number" name="card_number" inputmode="numeric" pattern="[0-9\s]{12,19}" minlength="12" placeholder="4111 1111 1111 1111" title="Enter at least 12 digits" required>
                            </div>
                            <div class="form-text"><i class="fa-solid fa-lock me-1"></i>Enter any 12+ digits. Card details are not stored.</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-primary w-100 py-2" type="submit" name="payment_action" value="success">
                                    <i class="fa-solid fa-circle-check me-1"></i>Pay &amp; Confirm
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-danger w-100 py-2" type="submit" name="payment_action" value="failed">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>Simulate Failure
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
