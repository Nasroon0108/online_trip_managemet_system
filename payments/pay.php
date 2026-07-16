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

<div class="row justify-content-center animate-slide-up">
    <div class="col-lg-6">
        <div class="card card-modern">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h3 class="fw-bold mb-0"><i class="fa-solid fa-credit-card text-primary me-2"></i>Secure Checkout</h3>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl(BOOKINGS_PAGE_PATH)) ?>">
                        <i class="fa-solid fa-chevron-left me-1"></i> My Bookings
                    </a>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-3 fs-4 text-warning"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Booking Details Breakdown -->
                <div class="p-3 bg-light rounded-3 mb-4 border border-light">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-file-invoice me-2 text-muted"></i>Booking Summary</h5>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Package Title:</span>
                        <span class="fw-bold text-dark text-end"><?= htmlspecialchars($booking["title"]) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Travelers:</span>
                        <span class="fw-bold text-dark"><?= htmlspecialchars((string)$booking["num_travelers"]) ?> Guests</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Status:</span>
                        <span class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1"><?= ucfirst(htmlspecialchars($booking["status"])) ?></span>
                    </div>
                    <hr class="my-2 text-muted">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="fw-bold text-dark">Total Amount:</span>
                        <span class="fs-4 fw-bold text-primary">Rs. <?= number_format((float)$booking["total_price"], 2) ?></span>
                    </div>
                </div>

                <?php if ($booking["payment_status"] !== "pending"): ?>
                    <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-0">
                        <i class="fa-solid fa-circle-check me-3 fs-4 text-success"></i>
                        <div>This payment has already been processed successfully.</div>
                    </div>
                <?php else: ?>
                    <!-- Mock Credit Card Preview Widget -->
                    <div class="p-4 rounded-4 mb-4 text-white position-relative shadow" style="background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%); overflow: hidden; min-height: 180px;">
                        <!-- Decors -->
                        <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 10rem; transform: translate(30px, -40px);"><i class="fa-solid fa-wifi"></i></div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold fs-5 tracking-wide"><i class="fa-solid fa-compass me-2"></i>TripEase Card</span>
                            <i class="fa-brands fa-cc-visa fs-2 opacity-75"></i>
                        </div>
                        
                        <div class="mb-4">
                            <div class="opacity-75 small">Card Number</div>
                            <h4 class="mb-0 letter-spacing" id="card-number-preview" style="font-family: monospace; letter-spacing: 2px;">•••• •••• •••• ••••</h4>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="opacity-75 small" style="font-size: 0.75rem;">Card Holder</div>
                                <div class="fw-bold text-truncate" id="card-name-preview" style="max-width: 180px; font-size: 0.9rem;">YOUR NAME</div>
                            </div>
                            <div class="text-end">
                                <div class="opacity-75 small" style="font-size: 0.75rem;">Expires</div>
                                <div class="fw-bold" style="font-size: 0.9rem;">12 / 29</div>
                            </div>
                        </div>
                    </div>

                    <form method="post" id="payment-form">
                        <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="card-name">Card Holder Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                                <input class="form-control border-start-0 ps-0" id="card-name" name="card_name" onkeyup="document.getElementById('card-name-preview').innerText = this.value.toUpperCase() || 'YOUR NAME';" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="card-number">Mock Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-credit-card text-muted"></i></span>
                                <input class="form-control border-start-0 ps-0" id="card-number" name="card_number" inputmode="numeric" placeholder="4111 1111 1111 1111" onkeyup="formatCardNumber(this)" required>
                            </div>
                            <div class="form-text text-muted small"><i class="fa-solid fa-shield-halved me-1"></i>This is a sandbox simulation. No real money or actual card data is used or stored.</div>
                        </div>
                        <div class="d-flex gap-3 mt-4">
                            <button class="btn btn-success flex-fill py-2.5" type="submit" name="payment_action" value="success">
                                <i class="fa-solid fa-circle-check me-1"></i> Pay Success
                            </button>
                            <button class="btn btn-outline-danger flex-fill py-2.5" type="submit" name="payment_action" value="failed">
                                <i class="fa-solid fa-circle-xmark me-1"></i> Simulate Failure
                            </button>
                        </div>
                    </form>

                    <script>
                    function formatCardNumber(input) {
                        let value = input.value.replace(/\D/g, '');
                        let formattedValue = '';
                        for (let i = 0; i < value.length; i++) {
                            if (i > 0 && i % 4 === 0) {
                                formattedValue += ' ';
                            }
                            formattedValue += value[i];
                        }
                        input.value = formattedValue.slice(0, 19);
                        document.getElementById('card-number-preview').innerText = input.value || '•••• •••• •••• ••••';
                    }
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
