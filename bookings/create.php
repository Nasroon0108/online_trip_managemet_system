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

$packageId = (int)($_POST["package_id"] ?? 0);
$numTravelers = (int)($_POST["num_travelers"] ?? 1);
$userId = (int)$_SESSION["user_id"];

if ($packageId <= 0 || $numTravelers <= 0) {
    redirectTo(PACKAGES_LIST_PATH);
}

$pdo->beginTransaction();
try {
    $pkgStmt = $pdo->prepare("SELECT package_id, available_slots, max_participants, price FROM packages WHERE package_id = :id FOR UPDATE");
    $pkgStmt->execute(["id" => $packageId]);
    $pkg = $pkgStmt->fetch();

    if (!$pkg || (int)$pkg["available_slots"] < $numTravelers) {
        $pdo->rollBack();
        redirectTo(PACKAGES_LIST_PATH);
    }

    if ((int)$pkg["max_participants"] < $numTravelers) {
        $pdo->rollBack();
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

    $pdo->commit();
    setFlash("info", "Booking created. Please complete payment to confirm your trip.");
    redirectTo(PAYMENT_PAGE_PATH . "?booking_id=" . $bookingId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

redirectTo(MY_BOOKINGS_PATH);
