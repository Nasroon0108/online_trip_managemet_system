<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/header.php";
requireAdmin();

$bookings = $pdo->query(
    "SELECT
        b.booking_id,
        b.booking_date,
        b.num_travelers,
        b.total_price,
        b.status,
        u.name AS traveler_name,
        u.email AS traveler_email,
        p.title AS package_title,
        pay.status AS payment_status
     FROM bookings b
     INNER JOIN users u ON u.user_id = b.user_id
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     ORDER BY b.booking_date DESC, b.booking_id DESC"
)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Manage Bookings</h3>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">Dashboard</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Traveler</th>
                    <th>Package</th>
                    <th>Travelers</th>
                    <th>Total</th>
                    <th>Booking Status</th>
                    <th>Payment</th>
                    <th>Booked On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($booking["traveler_name"]) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($booking["traveler_email"]) ?></div>
                    </td>
                    <td><?= htmlspecialchars($booking["package_title"]) ?></td>
                    <td><?= htmlspecialchars((string)$booking["num_travelers"]) ?></td>
                    <td>Rs. <?= htmlspecialchars((string)$booking["total_price"]) ?></td>
                    <td><?= htmlspecialchars($booking["status"]) ?></td>
                    <td><?= htmlspecialchars($booking["payment_status"]) ?></td>
                    <td><?= htmlspecialchars($booking["booking_date"]) ?></td>
                    <td class="text-nowrap">
                        <?php if ($booking["status"] === "confirmed"): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/admin/bookings/update_status.php')) ?>" class="d-inline">
                                <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                <input type="hidden" name="status" value="completed">
                                <button class="btn btn-sm btn-outline-success" type="submit">Complete</button>
                            </form>
                        <?php endif; ?>

                        <?php if (in_array($booking["status"], ["pending", "confirmed"], true)): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/admin/bookings/update_status.php')) ?>" class="d-inline">
                                <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">No bookings yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
