<?php
// statistic.php
session_start();
require_once '../classes/Trip.php';
require_once '../classes/Database.php';

// Prepare DB connection
$database = new Database();
$db = $database->getConnection();

// Add at the top after DB connection and before statistics calculation
if (isset($_POST['generate_report'])) {
    $statDate = $_POST['stat_date'];
    $adminID = $_SESSION['adminID'] ?? null;
    // Get all trips on the selected date
    $tripsStmt = $db->prepare("SELECT * FROM trip WHERE tripDate = ?");
    $tripsStmt->execute([$statDate]);
    $trips = $tripsStmt->fetchAll(PDO::FETCH_ASSOC);
    $routeStats = [];
    foreach ($trips as $trip) {
        $routeKey = $trip['origin'] . " → " . $trip['destination'];
        $passengerStmt = $db->prepare("
            SELECT SUM(los.itemQuantity) as totalPassengers
            FROM line_of_sale los
            WHERE los.tripID = ?
        ");
        $passengerStmt->execute([$trip['tripID']]);
        $passengerRow = $passengerStmt->fetch(PDO::FETCH_ASSOC);
        $tripPassengers = $passengerRow['totalPassengers'] ?? 0;
        $cancelStmt = $db->prepare("
            SELECT COUNT(*) as totalCancellations
            FROM trip_booking
            WHERE tripID = ? AND bookingStatus = 'Cancelled'
        ");
        $cancelStmt->execute([$trip['tripID']]);
        $cancelRow = $cancelStmt->fetch(PDO::FETCH_ASSOC);
        $tripCancellations = $cancelRow['totalCancellations'] ?? 0;
        if (!isset($routeStats[$routeKey])) {
            $routeStats[$routeKey] = [
                'route' => $routeKey,
                'totalPassengers' => 0,
                'totalCancellations' => 0
            ];
        }
        $routeStats[$routeKey]['totalPassengers'] += $tripPassengers;
        $routeStats[$routeKey]['totalCancellations'] += $tripCancellations;
    }
    // Find most and least popular
    $mostPopular = null;
    $leastPopular = null;
    foreach ($routeStats as $route => $data) {
        if ($mostPopular === null || $data['totalPassengers'] > $routeStats[$mostPopular]['totalPassengers']) {
            $mostPopular = $route;
        }
        if ($leastPopular === null || $data['totalPassengers'] < $routeStats[$leastPopular]['totalPassengers']) {
            $leastPopular = $route;
        }
    }
    $insertStmt = $db->prepare("INSERT INTO statistic (adminID, reportType, route, statDate, totalPassengers, totalCancellations) VALUES (?, ?, ?, ?, ?, ?)");
    if ($mostPopular !== null) {
        $data = $routeStats[$mostPopular];
        $insertStmt->execute([$adminID, 'most_popular', $mostPopular, $statDate, $data['totalPassengers'], $data['totalCancellations']]);
    }
    if ($leastPopular !== null && $leastPopular !== $mostPopular) {
        $data = $routeStats[$leastPopular];
        $insertStmt->execute([$adminID, 'least_popular', $leastPopular, $statDate, $data['totalPassengers'], $data['totalCancellations']]);
    }
    $reportSuccess = true;
}

// 1. Get all trips
$tripsStmt = $db->prepare("SELECT * FROM trip");
$tripsStmt->execute();
$trips = $tripsStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Aggregate passenger counts and cancellations per route
$routeStats = [];
foreach ($trips as $trip) {
    $routeKey = $trip['origin'] . " → " . $trip['destination'];

    // Get total passengers for this trip (sum of itemQuantity in line_of_sale for this trip)
    $passengerStmt = $db->prepare("
        SELECT SUM(los.itemQuantity) as totalPassengers
        FROM line_of_sale los
        WHERE los.tripID = ?
    ");
    $passengerStmt->execute([$trip['tripID']]);
    $passengerRow = $passengerStmt->fetch(PDO::FETCH_ASSOC);
    $totalPassengers = $passengerRow['totalPassengers'] ?? 0;

    // Get total cancellations for this trip (count in trip_booking with bookingStatus = 'Cancelled')
    $cancelStmt = $db->prepare("
        SELECT COUNT(*) as totalCancellations
        FROM trip_booking
        WHERE tripID = ? AND bookingStatus = 'Cancelled'
    ");
    $cancelStmt->execute([$trip['tripID']]);
    $cancelRow = $cancelStmt->fetch(PDO::FETCH_ASSOC);
    $totalCancellations = $cancelRow['totalCancellations'] ?? 0;

    // Aggregate by route
    if (!isset($routeStats[$routeKey])) {
        $routeStats[$routeKey] = [
            'route' => $routeKey,
            'totalPassengers' => 0,
            'totalCancellations' => 0,
            'trips' => []
        ];
    }
    $routeStats[$routeKey]['totalPassengers'] += $totalPassengers;
    $routeStats[$routeKey]['totalCancellations'] += $totalCancellations;
    $routeStats[$routeKey]['trips'][] = [
        'tripID' => $trip['tripID'],
        'date' => $trip['tripDate'],
        'time' => $trip['tripTime'],
        'passengers' => $totalPassengers,
        'cancellations' => $totalCancellations,
        'status' => $trip['tripStatus']
    ];
}

// Find most and least popular routes
$mostPopular = null;
$leastPopular = null;
foreach ($routeStats as $route => $data) {
    if ($mostPopular === null || $data['totalPassengers'] > $routeStats[$mostPopular]['totalPassengers']) {
        $mostPopular = $route;
    }
    if ($leastPopular === null || $data['totalPassengers'] < $routeStats[$leastPopular]['totalPassengers']) {
        $leastPopular = $route;
    }
}

// Prepare data for chart
$chartLabels = [];
$chartData = [];
foreach ($routeStats as $route => $data) {
    $chartLabels[] = $route;
    $chartData[] = $data['totalPassengers'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>ART Route Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f8fafc; }
        .highlight { font-weight: bold; color: #2563eb; }
        .table thead { background: #e0e7ff; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">ART Route Statistics</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="bi bi-house"></i> Back to Dashboard
        </a>
    </div>
    <?php if (isset($reportSuccess) && $reportSuccess): ?>
        <div class="alert alert-success">Statistical report generated and saved successfully!</div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="post" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label for="stat_date" class="form-label mb-0">Date</label>
                    <input type="date" class="form-control" name="stat_date" id="stat_date" required>
                </div>
                <div class="col-auto">
                    <button type="submit" name="generate_report" class="btn btn-primary">Generate Statistical Report</button>
                </div>
            </form>
        </div>
    </div>
    <div class="mb-3">
        <span class="highlight">Most Popular Route:</span>
        <?= htmlspecialchars($mostPopular) ?> (<?= $routeStats[$mostPopular]['totalPassengers'] ?> passengers)
        <br>
        <span class="highlight">Least Popular Route:</span>
        <?= htmlspecialchars($leastPopular) ?> (<?= $routeStats[$leastPopular]['totalPassengers'] ?> passengers)
    </div>
    <div class="mb-4">
        <canvas id="routeChart" height="100"></canvas>
    </div>
    <h3>Trip Details</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Route</th>
                <th>Date</th>
                <th>Time</th>
                <th>Passengers</th>
                <th>Cancellations</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($routeStats as $route => $data): ?>
            <?php foreach ($data['trips'] as $trip): ?>
                <tr>
                    <td><?= htmlspecialchars($route) ?></td>
                    <td><?= htmlspecialchars($trip['date']) ?></td>
                    <td><?= htmlspecialchars($trip['time']) ?></td>
                    <td><?= htmlspecialchars($trip['passengers']) ?></td>
                    <td><?= htmlspecialchars($trip['cancellations']) ?></td>
                    <td><?= htmlspecialchars($trip['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
const ctx = document.getElementById('routeChart').getContext('2d');
const routeChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Total Passengers',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(37, 99, 235, 0.6)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Passengers per ART Route' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
