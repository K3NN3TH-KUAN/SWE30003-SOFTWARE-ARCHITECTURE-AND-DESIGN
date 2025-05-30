<?php
session_start();
require_once '../classes/Trip.php';
$trip = new Trip();

// Handle add trip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $date = $_POST['tripDate'];
    $time = $_POST['tripTime'];
    $amount = $_POST['totalAmount'];
    $maxSeats = $_POST['maxSeats'];
    $trip->createNewTrip($origin, $destination, $date, $time, $amount, $maxSeats);
    header('Location: manage_trip.php');
    exit();
}

// Handle cancel trip
if (isset($_GET['cancel_trip'])) {
    $trip->cancelTrip($_GET['cancel_trip']);
    header('Location: manage_trip.php');
    exit();
}

$trips = $trip->searchTrips(); // Get all trips
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Trips</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage ART Trips</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="bi bi-house"></i> Back to Dashboard
        </a>
    </div>
    <form method="POST" class="row g-3 mb-4">
        <input type="hidden" name="add_trip" value="1">
        <div class="col-md-2"><input type="text" name="origin" class="form-control" placeholder="Origin" required></div>
        <div class="col-md-2"><input type="text" name="destination" class="form-control" placeholder="Destination" required></div>
        <div class="col-md-2"><input type="date" name="tripDate" class="form-control" required></div>
        <div class="col-md-2"><input type="time" name="tripTime" class="form-control" required></div>
        <div class="col-md-2"><input type="number" name="totalAmount" class="form-control" placeholder="Amount" required></div>
        <div class="col-md-2"><input type="number" name="maxSeats" class="form-control" placeholder="Seats" required></div>
        <div class="col-md-12 mt-2"><button type="submit" class="btn btn-success">Add Trip</button></div>
    </form>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Origin</th><th>Destination</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($trips as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['origin']) ?></td>
                <td><?= htmlspecialchars($t['destination']) ?></td>
                <td><?= htmlspecialchars($t['tripDate']) ?></td>
                <td><?= htmlspecialchars($t['tripTime']) ?></td>
                <td><?= htmlspecialchars($t['tripStatus']) ?></td>
                <td>
                    <?php if ($t['tripStatus'] !== 'Cancelled'): ?>
                        <a href="?cancel_trip=<?= $t['tripID'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Cancel this trip?')">Cancel</a>
                    <?php else: ?>
                        <span class="text-muted">Cancelled</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
