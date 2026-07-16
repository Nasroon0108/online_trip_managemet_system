<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireLogin();

$stmt = $pdo->prepare(
    "SELECT b.id, b.booking_date, t.title, t.destination, t.start_date, t.end_date, t.price
     FROM bookings b
     INNER JOIN trips t ON t.id = b.trip_id
     WHERE b.user_id = :user_id
     ORDER BY b.booking_date DESC"
);
$stmt->execute(["user_id" => (int)$_SESSION["user_id"]]);
$bookings = $stmt->fetchAll();
?>

<h3 class="mb-3">My Bookings</h3>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
            <tr>
                <th>Trip</th>
                <th>Destination</th>
                <th>Travel Date</th>
                <th>Price</th>
                <th>Booked On</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking["title"]) ?></td>
                    <td><?= htmlspecialchars($booking["destination"]) ?></td>
                    <td><?= htmlspecialchars($booking["start_date"]) ?> to <?= htmlspecialchars($booking["end_date"]) ?></td>
                    <td>Rs. <?= htmlspecialchars((string)$booking["price"]) ?></td>
                    <td><?= htmlspecialchars($booking["booking_date"]) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No bookings yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
