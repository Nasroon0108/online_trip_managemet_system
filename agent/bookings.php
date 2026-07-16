<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireAgent();

$agentId = (int)$_SESSION["user_id"];

$bookingsStmt = $pdo->prepare(
    "SELECT
        b.booking_id,
        b.booking_date,
        b.num_travelers,
        b.total_price,
        b.status,
        p.package_id,
        p.title AS package_title,
        u.name AS traveler_name,
        u.email AS traveler_email,
        pay.status AS payment_status
     FROM bookings b
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN agent_assignments aa ON aa.package_id = p.package_id AND aa.agent_id = :agent_id
     INNER JOIN users u ON u.user_id = b.user_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     ORDER BY b.booking_date DESC"
);
$bookingsStmt->execute(["agent_id" => $agentId]);
$bookings = $bookingsStmt->fetchAll();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="page-header mb-4">
    <div>
        <h2 class="mb-1">Assigned Bookings</h2>
        <p class="text-muted mb-0">Complete or cancel bookings for packages assigned to you.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/agent/index.php')) ?>">Workspace</a>
</div>

<div class="card card-modern">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Traveler</th>
                    <th>Package</th>
                    <th>Travelers</th>
                    <th>Total</th>
                    <th>Booking</th>
                    <th>Payment</th>
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
                    <td><?= (int)$booking["num_travelers"] ?></td>
                    <td>Rs. <?= number_format((float)$booking["total_price"], 2) ?></td>
                    <td><span class="badge badge-soft"><?= htmlspecialchars($booking["status"]) ?></span></td>
                    <td><?= htmlspecialchars($booking["payment_status"]) ?></td>
                    <td class="text-nowrap">
                        <?php if ($booking["status"] === "confirmed"): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/agent/update_booking.php')) ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                <input type="hidden" name="status" value="completed">
                                <button class="btn btn-sm btn-outline-success" type="submit">Complete</button>
                            </form>
                        <?php endif; ?>
                        <?php if (in_array($booking["status"], ["pending", "confirmed"], true)): ?>
                            <form method="post" action="<?= htmlspecialchars(appUrl('/agent/update_booking.php')) ?>" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No bookings for your assigned packages yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
