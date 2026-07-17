<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/auth.php";
requireTraveler();

$userId = (int)$_SESSION["user_id"];
$userName = (string)($_SESSION["user_name"] ?? "Traveler");

$statsStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) AS pending_bookings,
        SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) AS completed_bookings,
        SUM(CASE WHEN pay.status = 'success' THEN 1 ELSE 0 END) AS paid_bookings,
        SUM(CASE WHEN pay.status = 'pending' AND b.status = 'pending' THEN 1 ELSE 0 END) AS unpaid_bookings,
        COALESCE(SUM(CASE WHEN pay.status = 'success' THEN pay.amount ELSE 0 END), 0) AS total_spent
     FROM bookings b
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     WHERE b.user_id = :user_id"
);
$statsStmt->execute(["user_id" => $userId]);
$stats = $statsStmt->fetch() ?: [
    "total_bookings" => 0,
    "pending_bookings" => 0,
    "confirmed_bookings" => 0,
    "completed_bookings" => 0,
    "paid_bookings" => 0,
    "unpaid_bookings" => 0,
    "total_spent" => 0,
];

$upcomingStmt = $pdo->prepare(
    "SELECT
        b.booking_id,
        b.package_id,
        b.status,
        b.num_travelers,
        b.total_price,
        pay.status AS payment_status,
        p.title,
        p.start_date,
        p.end_date,
        GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS destination_names
     FROM bookings b
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     LEFT JOIN package_destinations pd ON pd.package_id = p.package_id
     LEFT JOIN destinations d ON d.destination_id = pd.destination_id AND d.status = 'active'
     WHERE b.user_id = :user_id
       AND b.status IN ('pending', 'confirmed')
       AND p.end_date >= CURDATE()
     GROUP BY b.booking_id, b.package_id, b.status, b.num_travelers, b.total_price, pay.status, p.title, p.start_date, p.end_date
     ORDER BY p.start_date ASC
     LIMIT 4"
);
$upcomingStmt->execute(["user_id" => $userId]);
$upcomingTrips = $upcomingStmt->fetchAll();

$recentStmt = $pdo->prepare(
    "SELECT
        b.booking_id,
        b.package_id,
        b.booking_date,
        b.status,
        b.total_price,
        pay.status AS payment_status,
        p.title,
        r.review_id
     FROM bookings b
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     LEFT JOIN reviews r ON r.user_id = b.user_id AND r.package_id = b.package_id
     WHERE b.user_id = :user_id
     ORDER BY b.booking_date DESC, b.booking_id DESC
     LIMIT 6"
);
$recentStmt->execute(["user_id" => $userId]);
$recentBookings = $recentStmt->fetchAll();

$activityStmt = $pdo->prepare(
    "SELECT
        b.booking_id,
        b.status,
        b.booking_date,
        pay.status AS payment_status,
        pay.payment_date,
        p.title,
        r.review_id,
        r.review_date
     FROM bookings b
     INNER JOIN packages p ON p.package_id = b.package_id
     INNER JOIN payments pay ON pay.booking_id = b.booking_id
     LEFT JOIN reviews r ON r.user_id = b.user_id AND r.package_id = b.package_id
     WHERE b.user_id = :user_id
     ORDER BY GREATEST(
         COALESCE(r.review_date, '1970-01-01'),
         COALESCE(pay.payment_date, '1970-01-01'),
         b.booking_date
     ) DESC
     LIMIT 5"
);
$activityStmt->execute(["user_id" => $userId]);
$activityRows = $activityStmt->fetchAll();

$reviewDueStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM bookings b
     LEFT JOIN reviews r ON r.user_id = b.user_id AND r.package_id = b.package_id
     WHERE b.user_id = :user_id AND b.status = 'completed' AND r.review_id IS NULL"
);
$reviewDueStmt->execute(["user_id" => $userId]);
$reviewDue = (int)$reviewDueStmt->fetchColumn();

$hour = (int)date("G");
$greeting = $hour < 12 ? "Good morning" : ($hour < 17 ? "Good afternoon" : "Good evening");
$firstName = explode(" ", $userName)[0];

require_once __DIR__ . "/../includes/header.php";
?>

<div class="dash-hero animate-slide-up">
    <div>
        <p class="dash-kicker mb-1"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?></p>
        <h2 class="mb-2">Trip Overview</h2>
        <p class="text-muted mb-0">
            You have <?= (int)$stats["confirmed_bookings"] ?> confirmed trip<?= (int)$stats["confirmed_bookings"] === 1 ? "" : "s" ?>
            and <?= (int)$stats["unpaid_bookings"] ?> unpaid booking<?= (int)$stats["unpaid_bookings"] === 1 ? "" : "s" ?>.
            <?php if ($reviewDue > 0): ?>
                <?= $reviewDue ?> trip<?= $reviewDue === 1 ? "" : "s" ?> waiting for a review.
            <?php endif; ?>
        </p>
    </div>
    <a class="btn btn-brand" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">
        <i class="fa-solid fa-compass me-1"></i> Browse Packages
    </a>
</div>

<div class="row g-3 stats-row animate-slide-up">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
            <div>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-value"><?= (int)$stats["total_bookings"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="stat-label">Paid Trips</div>
                <div class="stat-value"><?= (int)$stats["paid_bookings"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-flag-checkered"></i></div>
            <div>
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?= (int)$stats["completed_bookings"] ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-card-accent">
            <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div>
                <div class="stat-label">Total Spent</div>
                <div class="stat-value" style="font-size:1.35rem;">Rs. <?= number_format((float)$stats["total_spent"], 0) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Upcoming Trips</h5>
                <p class="text-muted small mb-0">Pending and confirmed bookings still on the calendar</p>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>">View all</a>
        </div>
        <div class="row g-3">
            <?php foreach ($upcomingTrips as $trip): ?>
                <?php
                $status = $trip["status"];
                $badgeClass = match ($status) {
                    "confirmed" => "badge-confirmed",
                    default => "badge-pending",
                };
                ?>
                <div class="col-md-6">
                    <article class="trip-card h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="badge badge-custom <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($status)) ?></span>
                            <?php if ($trip["payment_status"] === "success"): ?>
                                <span class="small text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i>Paid</span>
                            <?php else: ?>
                                <span class="small text-warning fw-semibold"><i class="fa-solid fa-clock me-1"></i>Unpaid</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="trip-card-title"><?= htmlspecialchars($trip["title"]) ?></h3>
                        <p class="trip-card-meta mb-2">#<?= (int)$trip["booking_id"] ?> · <?= (int)$trip["num_travelers"] ?> traveler<?= (int)$trip["num_travelers"] === 1 ? "" : "s" ?></p>
                        <p class="trip-card-route mb-3">
                            <i class="fa-solid fa-map-pin me-1"></i><?= htmlspecialchars($trip["destination_names"] ?: "Sri Lanka") ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-end gap-2">
                            <div class="small text-muted">
                                <div><?= date("M d, Y", strtotime((string)$trip["start_date"])) ?></div>
                                <div>to <?= date("M d, Y", strtotime((string)$trip["end_date"])) ?></div>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($trip["status"] === "pending" && $trip["payment_status"] === "pending"): ?>
                                    <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/payments/pay.php?booking_id=' . (int)$trip["booking_id"])) ?>">Pay</a>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$trip["package_id"])) ?>">Open</a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>

            <div class="col-md-6">
                <a class="trip-card trip-card-new h-100 text-decoration-none" href="<?= htmlspecialchars(appUrl('/trips/list.php')) ?>">
                    <div class="trip-card-new-inner">
                        <i class="fa-solid fa-plus"></i>
                        <span>Browse a new package</span>
                    </div>
                </a>
            </div>

            <?php if (!$upcomingTrips): ?>
                <div class="col-12">
                    <div class="alert alert-light border mb-0">No upcoming trips yet. Browse packages to book your next journey.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="activity-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">Recent Activity</h5>
                    <p class="text-muted small mb-0">Bookings, payments, and reviews</p>
                </div>
            </div>
            <ul class="activity-list">
                <?php foreach ($activityRows as $row): ?>
                    <?php
                    if (!empty($row["review_id"])) {
                        $icon = "fa-star text-warning";
                        $label = "Review submitted";
                        $when = $row["review_date"];
                    } elseif ($row["payment_status"] === "success") {
                        $icon = "fa-circle-check text-success";
                        $label = "Payment successful";
                        $when = $row["payment_date"] ?: $row["booking_date"];
                    } elseif ($row["payment_status"] === "failed") {
                        $icon = "fa-triangle-exclamation text-danger";
                        $label = "Payment failed";
                        $when = $row["payment_date"] ?: $row["booking_date"];
                    } else {
                        $icon = "fa-ticket text-primary";
                        $label = "Booking " . $row["status"];
                        $when = $row["booking_date"];
                    }
                    ?>
                    <li class="activity-item">
                        <div class="activity-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($label) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($row["title"]) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string)$when) ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if (!$activityRows): ?>
                    <li class="text-muted small">No activity yet.</li>
                <?php endif; ?>
            </ul>
            <a class="btn btn-sm btn-outline-secondary w-100 mt-3" href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>">Open booking ledger</a>
        </div>
    </div>
</div>

<div class="card card-modern card-panel animate-slide-up">
    <div class="card-body p-0">
        <div class="panel-toolbar">
            <div>
                <h5 class="mb-0">Booking Ledger</h5>
                <p class="text-muted small mb-0">Latest trips across all statuses</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/bookings/paid.php')) ?>">Paid only</a>
                <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars(appUrl('/bookings/my_bookings.php')) ?>">All bookings</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-app align-middle mb-0">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentBookings as $booking): ?>
                    <tr>
                        <td class="fw-semibold">#<?= (int)$booking["booking_id"] ?></td>
                        <td><?= htmlspecialchars($booking["title"]) ?></td>
                        <td><span class="badge badge-soft"><?= htmlspecialchars($booking["status"]) ?></span></td>
                        <td><?= htmlspecialchars($booking["payment_status"]) ?></td>
                        <td class="text-nowrap">Rs. <?= number_format((float)$booking["total_price"], 2) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(appUrl('/trips/view.php?id=' . (int)$booking["package_id"])) ?>">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentBookings): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No bookings yet.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
