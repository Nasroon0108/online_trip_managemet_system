<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireLogin();

$stmt = $pdo->prepare(
    "SELECT
        b.booking_id,
        b.booking_date,
        b.package_id,
        p.title,
        GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS destination_names,
        p.start_date,
        p.end_date,
        b.num_travelers,
        b.total_price,
        b.status,
        pay.status AS payment_status,
        r.review_id
     FROM bookings b
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     LEFT JOIN reviews r ON r.user_id = b.user_id AND r.package_id = b.package_id
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id AND d.status = 'active'
     WHERE b.user_id = :user_id
     GROUP BY b.booking_id, b.booking_date, b.package_id, p.title, p.start_date, p.end_date, b.num_travelers, b.total_price, b.status, pay.status, r.review_id
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
                    <th>Package</th>
                    <th>Destination</th>
                    <th>Travel Date</th>
                    <th>Travelers</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Booked On</th>
                    <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking["title"]) ?></td>
                    <td><?= htmlspecialchars($booking["destination_names"] ?? "—") ?></td>
                    <td><?= htmlspecialchars($booking["start_date"]) ?> to <?= htmlspecialchars($booking["end_date"]) ?></td>
                    <td><?= htmlspecialchars((string)$booking["num_travelers"]) ?></td>
                    <td>Rs. <?= htmlspecialchars((string)$booking["total_price"]) ?></td>
                    <td>
                        <?php $status = $booking["status"] ?? "pending"; ?>
                        <span class="<?= bookingStatusClass($status) ?>"><?= htmlspecialchars($status) ?></span>
                    </td>
                    <td><?= htmlspecialchars($booking["payment_status"]) ?></td>
                    <td><?= htmlspecialchars($booking["booking_date"]) ?></td>
                    <td class="text-nowrap">
                        <?php if ($booking["status"] === "pending" && $booking["payment_status"] === "pending"): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(appUrl('/payments/pay.php?booking_id=' . (int)$booking["booking_id"])) ?>">Pay Now</a>
                        <?php endif; ?>

                        <?php if (in_array($booking["status"], ["pending", "confirmed"], true)): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/cancel.php')) ?>" class="d-inline">
                                <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($booking["status"] === "completed" && empty($booking["review_id"])): ?>
                            <a class="btn btn-sm btn-outline-success" href="<?= htmlspecialchars(appUrl('/reviews/create.php?package_id=' . (int)$booking["package_id"])) ?>">Review</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">No bookings yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
