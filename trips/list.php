<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireLogin();

$trips = $pdo->query("SELECT id, title, destination, price, available_slots, start_date, end_date FROM trips ORDER BY start_date ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Available Trips</h3>
    <a class="btn btn-success" href="/trips/create.php">+ Add Trip</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
            <tr>
                <th>Title</th>
                <th>Destination</th>
                <th>Date</th>
                <th>Price</th>
                <th>Slots</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($trips as $trip): ?>
                <tr>
                    <td><?= htmlspecialchars($trip["title"]) ?></td>
                    <td><?= htmlspecialchars($trip["destination"]) ?></td>
                    <td><?= htmlspecialchars($trip["start_date"]) ?> to <?= htmlspecialchars($trip["end_date"]) ?></td>
                    <td>Rs. <?= htmlspecialchars((string)$trip["price"]) ?></td>
                    <td><?= htmlspecialchars((string)$trip["available_slots"]) ?></td>
                    <td>
                        <form method="post" action="/bookings/create.php" class="d-inline">
                            <input type="hidden" name="trip_id" value="<?= (int)$trip["id"] ?>">
                            <button class="btn btn-primary btn-sm" type="submit">Book</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$trips): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No trips yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
