<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/header.php";
requireLogin();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $destination = trim($_POST["destination"] ?? "");
    $price = (float)($_POST["price"] ?? 0);
    $slots = (int)($_POST["available_slots"] ?? 0);
    $startDate = $_POST["start_date"] ?? "";
    $endDate = $_POST["end_date"] ?? "";

    if ($title === "" || $destination === "" || $startDate === "" || $endDate === "") {
        $message = "Please fill all required fields.";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO trips (title, destination, price, available_slots, start_date, end_date)
             VALUES (:title, :destination, :price, :available_slots, :start_date, :end_date)"
        );
        $stmt->execute([
            "title" => $title,
            "destination" => $destination,
            "price" => $price,
            "available_slots" => $slots,
            "start_date" => $startDate,
            "end_date" => $endDate,
        ]);
        header("Location: /trips/list.php");
        exit;
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">Create Trip</h3>
                <?php if ($message !== ""): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Trip Title</label>
                            <input class="form-control" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destination</label>
                            <input class="form-control" name="destination" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price</label>
                            <input class="form-control" type="number" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Available Slots</label>
                            <input class="form-control" type="number" name="available_slots" min="1" required>
                        </div>
                        <div class="col-md-4"></div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input class="form-control" type="date" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input class="form-control" type="date" name="end_date" required>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">Save Trip</button>
                    <a class="btn btn-secondary mt-3" href="/trips/list.php">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
