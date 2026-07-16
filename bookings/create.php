<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireTraveler();

const PACKAGES_LIST_PATH = "/trips/list.php";
const MY_BOOKINGS_PATH = "/bookings/my_bookings.php";
const PAYMENT_PAGE_PATH = "/payments/pay.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectTo(PACKAGES_LIST_PATH);
}

verifyCsrf($_POST["csrf_token"] ?? null);

$packageId = (int)($_POST["package_id"] ?? 0);
$numTravelers = (int)($_POST["num_travelers"] ?? 1);
$userId = (int)$_SESSION["user_id"];

if ($packageId <= 0 || $numTravelers <= 0) {
    setFlash("warning", "Invalid booking request.");
    redirectTo(PACKAGES_LIST_PATH);
}

$pdo->beginTransaction();
try {
    $pkgStmt = $pdo->prepare(
        "SELECT package_id, available_slots, max_participants, price, status
         FROM packages
         WHERE package_id = :id
         FOR UPDATE"
    );
    $pkgStmt->execute(["id" => $packageId]);
    $pkg = $pkgStmt->fetch();

    if (!$pkg || $pkg["status"] !== "active") {
        $pdo->rollBack();
        setFlash("warning", "This package is not available for booking.");
        redirectTo(PACKAGES_LIST_PATH);
    }

    if ((int)$pkg["available_slots"] < $numTravelers) {
        $pdo->rollBack();
        setFlash("warning", "Not enough available slots for this package.");
        redirectTo(PACKAGES_LIST_PATH);
    }

    if ((int)$pkg["max_participants"] < $numTravelers) {
        $pdo->rollBack();
        setFlash("warning", "Traveler count exceeds the package limit.");
        redirectTo(PACKAGES_LIST_PATH);
    }

    $totalPrice = (float)$pkg["price"] * $numTravelers;

    $bookStmt = $pdo->prepare(
        "INSERT INTO bookings (user_id, package_id, num_travelers, total_price, status)
         VALUES (:user_id, :package_id, :num_travelers, :total_price, 'pending')"
    );
    $bookStmt->execute([
        "user_id" => $userId,
        "package_id" => $packageId,
        "num_travelers" => $numTravelers,
        "total_price" => $totalPrice,
    ]);
    $bookingId = (int)$pdo->lastInsertId();

    $payStmt = $pdo->prepare(
        "INSERT INTO payments (booking_id, amount, payment_method, status, transaction_ref)
         VALUES (:booking_id, :amount, :payment_method, 'pending', NULL)"
    );
    $payStmt->execute([
        "booking_id" => $bookingId,
        "amount" => $totalPrice,
        "payment_method" => "mock_card",
    ]);

    // Soft-hold slots until payment success/failure or cancel.
    $holdStmt = $pdo->prepare(
        "UPDATE packages
         SET available_slots = available_slots - :num_travelers
         WHERE package_id = :id"
    );
    $holdStmt->execute([
        "id" => $packageId,
        "num_travelers" => $numTravelers,
    ]);

    $pdo->commit();
    setFlash("info", "Booking created. Complete payment to confirm your trip.");
    redirectTo(PAYMENT_PAGE_PATH . "?booking_id=" . $bookingId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash("danger", "Could not create booking. Please try again.");
}

redirectTo(MY_BOOKINGS_PATH);
