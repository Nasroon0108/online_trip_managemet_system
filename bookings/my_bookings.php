<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireTraveler();
require_once __DIR__ . "/../includes/header.php";

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

<div class="page-header animate-slide-up">
    <div>
        <p class="page-kicker mb-1">My Account</p>
        <h2>My Bookings</h2>
        <p class="text-muted mb-0">Track booked itineraries, payments, and trip statuses</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/dashboard/index.php')) ?>">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/bookings/paid.php')) ?>">
            <i class="fa-solid fa-wallet me-1"></i> Paid Bookings
        </a>
    </div>
</div>

<div class="card card-modern card-panel animate-slide-up">
    <div class="card-body p-0">
        <div class="panel-toolbar">
            <div>
                <h5 class="mb-0">Booking ledger</h5>
                <p class="text-muted small mb-0"><?= count($bookings) ?> record<?= count($bookings) === 1 ? "" : "s" ?></p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-app align-middle mb-0">
            <thead>
                <tr>
                    <th>Package</th>
                    <th>Destination</th>
                    <th>Travel Dates</th>
                    <th class="text-center">Travelers</th>
                    <th>Total Price</th>
                    <th>Booking Status</th>
                    <th>Payment</th>
                    <th>Booked On</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($booking["title"]) ?></div>
                        <span class="text-muted small">ID: #<?= (int)$booking["booking_id"] ?></span>
                    </td>
                    <td>
                        <div class="small text-muted text-truncate" style="max-width: 180px;">
                            <i class="fa-solid fa-map-pin me-1 text-primary"></i><?= htmlspecialchars($booking["destination_names"] ?? "—") ?>
                        </div>
                    </td>
                    <td class="small">
                        <div><?= date("M d, Y", strtotime($booking["start_date"])) ?></div>
                        <div class="text-muted">to <?= date("M d, Y", strtotime($booking["end_date"])) ?></div>
                    </td>
                    <td class="text-center fw-semibold"><?= htmlspecialchars((string)$booking["num_travelers"]) ?></td>
                    <td class="fw-bold text-primary">Rs. <?= number_format((float)$booking["total_price"], 2) ?></td>
                    <td>
                        <?php 
                        $status = $booking["status"] ?? "pending";
                        $badgeClass = match ($status) {
                            "confirmed" => "badge-confirmed",
                            "completed" => "badge-completed",
                            "cancelled" => "badge-cancelled",
                            default => "badge-pending",
                        };
                        ?>
                        <span class="badge badge-custom <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($status)) ?></span>
                    </td>
                    <td>
                        <?php 
                        $payStatus = $booking["payment_status"] ?? "pending";
                        if ($payStatus === "success"): ?>
                            <span class="badge bg-success-subtle text-success py-1.5 px-2.5 rounded-pill small"><i class="fa-solid fa-circle-check me-1"></i>Paid</span>
                        <?php elseif ($payStatus === "failed"): ?>
                            <span class="badge bg-danger-subtle text-danger py-1.5 px-2.5 rounded-pill small"><i class="fa-solid fa-circle-xmark me-1"></i>Failed</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning py-1.5 px-2.5 rounded-pill small"><i class="fa-solid fa-clock me-1"></i>Unpaid</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= date("M d, Y", strtotime($booking["booking_date"])) ?></td>
                    <td class="text-end text-nowrap">
                        <div class="d-flex gap-2 justify-content-end">
                            <?php if ($booking["status"] === "pending" && $booking["payment_status"] === "pending"): ?>
                                <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/payments/pay.php?booking_id=' . (int)$booking["booking_id"])) ?>">
                                    <i class="fa-solid fa-credit-card me-1"></i> Pay Now
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($booking["status"], ["pending", "confirmed"], true)): ?>
                                <form method="post" action="<?= htmlspecialchars(appUrl('/bookings/cancel.php')) ?>" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                        <i class="fa-solid fa-ban me-1"></i> Cancel
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($booking["status"] === "completed" && empty($booking["review_id"])): ?>
                                <a class="btn btn-sm btn-outline-success" href="<?= htmlspecialchars(appUrl('/reviews/create.php?package_id=' . (int)$booking["package_id"])) ?>">
                                    <i class="fa-solid fa-star me-1"></i> Review
                                </a>
                            <?php elseif ($booking["status"] === "completed" && !empty($booking["review_id"])): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$booking["package_id"] . '#reviews')) ?>">
                                    <i class="fa-solid fa-circle-check me-1"></i> Reviewed
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <div class="mb-2"><i class="fa-solid fa-receipt fs-2"></i></div>
                        <div>No bookings found.</div>
                        <a href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>" class="btn btn-primary btn-sm mt-3">Book Your First Trip</a>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
