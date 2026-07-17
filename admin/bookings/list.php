<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAdmin();

$paymentFilter = trim((string)($_GET["payment"] ?? ""));
$search = trim((string)($_GET["q"] ?? ""));
$allowedPayments = ["success", "pending", "failed", ""];
if (!in_array($paymentFilter, $allowedPayments, true)) {
    $paymentFilter = "";
}

$sql = "SELECT
        b.booking_id,
        b.booking_date,
        b.num_travelers,
        b.total_price,
        b.status,
        u.name AS traveler_name,
        u.email AS traveler_email,
        p.title AS package_title,
        pay.status AS payment_status,
        pay.transaction_ref
     FROM bookings b
     INNER JOIN users u ON u.user_id = b.user_id
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     WHERE 1=1";
$params = [];

if ($paymentFilter !== "") {
    $sql .= " AND pay.status = :payment_status";
    $params["payment_status"] = $paymentFilter;
}

if ($search !== "") {
    $sql .= " AND (
        u.name LIKE :q
        OR u.email LIKE :q
        OR p.title LIKE :q
        OR CAST(b.booking_id AS CHAR) LIKE :q
        OR pay.transaction_ref LIKE :q
    )";
    $params["q"] = "%" . $search . "%";
}

$sql .= " ORDER BY b.booking_date DESC, b.booking_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = $paymentFilter === "success" ? "Paid Bookings" : "Bookings";
$pageSubtitle = $paymentFilter === "success"
    ? "Successful payments across all travelers."
    : "Review traveler bookings, payments, and trip status changes.";

require_once __DIR__ . "/../../includes/header.php";
?>

<div class="page-header">
    <div>
        <p class="page-kicker mb-1">Booking management</p>
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($pageSubtitle) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a class="btn btn-sm <?= $paymentFilter === "" ? "btn-primary" : "btn-outline-secondary" ?>" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php')) ?>">All</a>
        <a class="btn btn-sm <?= $paymentFilter === "success" ? "btn-primary" : "btn-outline-secondary" ?>" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php?payment=success')) ?>">Paid</a>
        <a class="btn btn-sm <?= $paymentFilter === "pending" ? "btn-primary" : "btn-outline-secondary" ?>" href="<?= htmlspecialchars(appUrl('/admin/bookings/list.php?payment=pending')) ?>">Unpaid</a>
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars(appUrl('/admin/index.php')) ?>">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<div class="card card-modern card-panel">
    <div class="card-body p-0">
        <div class="panel-toolbar">
            <div>
                <h5 class="mb-0">Booking ledger</h5>
                <p class="text-muted small mb-0">
                    <?= count($bookings) ?> record<?= count($bookings) === 1 ? "" : "s" ?>
                    <?php if ($search !== ""): ?>
                        · filtered by “<?= htmlspecialchars($search) ?>”
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-app align-middle mb-0">
                <thead>
                    <tr>
                        <th>Traveler</th>
                        <th>Package</th>
                        <th>Travelers</th>
                        <th>Total</th>
                        <th>Booking</th>
                        <th>Payment</th>
                        <th>Booked on</th>
                        <th class="text-end">Actions</th>
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
                        <td class="text-nowrap">Rs. <?= number_format((float)$booking["total_price"], 2) ?></td>
                        <td><span class="badge badge-soft"><?= htmlspecialchars($booking["status"]) ?></span></td>
                        <td>
                            <?= htmlspecialchars($booking["payment_status"]) ?>
                            <?php if (!empty($booking["transaction_ref"])): ?>
                                <div class="small text-muted"><?= htmlspecialchars((string)$booking["transaction_ref"]) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted text-nowrap"><?= htmlspecialchars($booking["booking_date"]) ?></td>
                        <td class="text-end text-nowrap">
                            <?php if ($booking["status"] === "confirmed"): ?>
                                <form method="post" action="<?= htmlspecialchars(appUrl('/admin/bookings/update_status.php')) ?>" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button class="btn btn-sm btn-outline-success" type="submit">Complete</button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($booking["status"], ["pending", "confirmed"], true)): ?>
                                <form method="post" action="<?= htmlspecialchars(appUrl('/admin/bookings/update_status.php')) ?>" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="booking_id" value="<?= (int)$booking["booking_id"] ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Cancel this booking?');">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$bookings): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No bookings found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
