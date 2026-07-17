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
        pay.transaction_ref,
        pay.payment_date,
        r.review_id
     FROM bookings b
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     LEFT JOIN reviews r ON r.user_id = b.user_id AND r.package_id = b.package_id
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id AND d.status = 'active'
     WHERE b.user_id = :user_id AND pay.status = 'success'
     GROUP BY b.booking_id, b.booking_date, b.package_id, p.title, p.start_date, p.end_date, b.num_travelers, b.total_price, b.status, pay.status, pay.transaction_ref, pay.payment_date, r.review_id
     ORDER BY pay.payment_date DESC, b.booking_id DESC"
);
$stmt->execute(["user_id" => (int)$_SESSION["user_id"]]);
$bookings = $stmt->fetchAll();

$totalPaid = 0.0;
foreach ($bookings as $row) {
    $totalPaid += (float)$row["total_price"];
}
?>

<div class="page-header animate-slide-up">
    <div>
        <p class="page-kicker mb-1">Payment history</p>
        <h2>Paid Bookings</h2>
        <p class="text-muted mb-0">Trips with successful sandbox payments · <strong>Rs. <?= number_format($totalPaid, 2) ?></strong> total</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/dashboard/index.php')) ?>">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>">
            <i class="fa-solid fa-list me-1"></i> All Bookings
    </a>
</div>

<div class="card card-modern card-panel animate-slide-up">
    <div class="card-body p-0">
        <div class="panel-toolbar">
            <div>
                <h5 class="mb-0">Payment ledger</h5>
                <p class="text-muted small mb-0"><?= count($bookings) ?> paid booking<?= count($bookings) === 1 ? "" : "s" ?></p>
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
                        <th>Amount</th>
                        <th>Booking Status</th>
                        <th>Transaction</th>
                        <th>Paid On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <?php
                    $status = $booking["status"] ?? "pending";
                    $badgeClass = match ($status) {
                        "confirmed" => "badge-confirmed",
                        "completed" => "badge-completed",
                        "cancelled" => "badge-cancelled",
                        default => "badge-pending",
                    };
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($booking["title"]) ?></div>
                            <span class="text-muted small">ID: #<?= (int)$booking["booking_id"] ?></span>
                        </td>
                        <td>
                            <div class="small text-muted text-truncate" style="max-width: 180px;">
                                <i class="fa-solid fa-map-pin me-1 text-primary"></i><?= htmlspecialchars($booking["destination_names"] ?? "—") ?>
                            </div>
                        </td>
                        <td class="small">
                            <div><?= date("M d, Y", strtotime((string)$booking["start_date"])) ?></div>
                            <div class="text-muted">to <?= date("M d, Y", strtotime((string)$booking["end_date"])) ?></div>
                        </td>
                        <td class="text-center fw-semibold"><?= (int)$booking["num_travelers"] ?></td>
                        <td class="fw-bold text-primary text-nowrap">Rs. <?= number_format((float)$booking["total_price"], 2) ?></td>
                        <td><span class="badge badge-custom <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($status)) ?></span></td>
                        <td class="small">
                            <span class="badge bg-success-subtle text-success rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Paid</span>
                            <div class="text-muted mt-1"><?= htmlspecialchars((string)($booking["transaction_ref"] ?? "—")) ?></div>
                        </td>
                        <td class="small text-muted"><?= $booking["payment_date"] ? date("M d, Y", strtotime((string)$booking["payment_date"])) : "—" ?></td>
                        <td class="text-end text-nowrap">
                            <div class="d-flex gap-2 justify-content-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$booking["package_id"])) ?>">
                                    View
                                </a>
                                <?php if ($status === "completed" && empty($booking["review_id"])): ?>
                                    <a class="btn btn-sm btn-outline-success" href="<?= htmlspecialchars(appUrl('/reviews/create.php?package_id=' . (int)$booking["package_id"])) ?>">
                                        <i class="fa-solid fa-star me-1"></i> Review
                                    </a>
                                <?php elseif ($status === "completed" && !empty($booking["review_id"])): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$booking["package_id"] . '#reviews')) ?>">
                                        Reviewed
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$bookings): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <div class="mb-2"><i class="fa-solid fa-wallet fs-2"></i></div>
                            <div>No paid bookings yet.</div>
                            <a href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>" class="btn btn-primary btn-sm mt-3">Go to My Bookings</a>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
