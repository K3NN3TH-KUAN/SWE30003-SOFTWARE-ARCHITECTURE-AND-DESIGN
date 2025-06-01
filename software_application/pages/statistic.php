<?php
// statistic.php
session_start();
require_once '../classes/Trip.php';
require_once '../classes/Database.php';

// Prepare DB connection
$database = new Database();
$db = $database->getConnection();

// Get selected month from GET parameter
$selectedMonth = $_GET['stat_month'] ?? date('Y-m');
$monthStart = $selectedMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

// 1. Get all trips for the selected month
$tripsStmt = $db->prepare("SELECT * FROM trip WHERE tripDate BETWEEN ? AND ?");
$tripsStmt->execute([$monthStart, $monthEnd]);
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

// Add CSV Export Handler
if (isset($_POST['generate_report'])) {
    // Store report data in the statistic table
    $insertStmt = $db->prepare("
        INSERT INTO statistic (route, date, time, passengers, cancellations, status, stat_month)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($routeStats as $route => $data) {
        foreach ($data['trips'] as $trip) {
            $insertStmt->execute([
                $route,
                $trip['date'],
                $trip['time'],
                $trip['passengers'],
                $trip['cancellations'],
                $trip['status'],
                $selectedMonth
            ]);
        }
    }

    // CSV output code
    $monthLabel = date('F Y', strtotime($selectedMonth . '-01'));
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="art_route_statistics_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ["ART Route Statistics for $monthLabel"]);
    fputcsv($output, []);
    fputcsv($output, ['Route', 'Date', 'Time', 'Passengers', 'Cancellations', 'Status']);
    foreach ($routeStats as $route => $data) {
        foreach ($data['trips'] as $trip) {
            fputcsv($output, [
                $route,
                $trip['date'],
                $trip['time'],
                $trip['passengers'],
                $trip['cancellations'],
                $trip['status']
            ]);
        }
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>ART Route Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .highlight { 
            font-weight: bold; 
            color: #2563eb; 
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: #fff;
            margin-bottom: 2rem;
        }
        .card-header {
            background: #e0e7ff;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead { 
            background: #e0e7ff; 
        }
        .table th {
            font-weight: 600;
            color: #1e40af;
        }
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .status-Available {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-Booked {
            background-color: #fef08a;
            color: #92400e;
        }
        .status-Cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .chart-container {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0 fw-bold">ART Route Statistics</h1>
        <div>
            <a href="admin_dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>
    </div>

    <form method="get" class="row g-3 align-items-end mb-4">
        <div class="col-auto">
            <label for="stat_month" class="form-label mb-0">Select Month</label>
            <input type="month" class="form-control" name="stat_month" id="stat_month"
                   value="<?= htmlspecialchars($selectedMonth) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="bi bi-graph-up"></i> Route Performance</h5>
                    <p class="mb-2">
                        <span class="highlight">Most Popular Route:</span><br>
                        <?= htmlspecialchars($mostPopular) ?> 
                        <small class="text-muted">(<?= $routeStats[$mostPopular]['totalPassengers'] ?> passengers)</small>
                    </p>
                    <p class="mb-0">
                        <span class="highlight">Least Popular Route:</span><br>
                        <?= htmlspecialchars($leastPopular) ?>
                        <small class="text-muted">(<?= $routeStats[$leastPopular]['totalPassengers'] ?> passengers)</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-container">
        <canvas id="routeChart" height="100"></canvas>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-table"></i> Trip Details</h3>
            <form method="post" class="d-inline me-2">
                <button type="submit" name="generate_report" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Generate Report
                </button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
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
                                <td>
                                    <span class="status-badge status-<?= $trip['status'] ?>">
                                        <?= htmlspecialchars($trip['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
            backgroundColor: 'rgba(37, 99, 235, 0.6)',
            borderColor: 'rgba(37, 99, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { 
                display: true, 
                text: 'Passengers per ART Route',
                font: {
                    size: 16,
                    weight: 'bold'
                }
            }
        },
        scales: {
            y: { 
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Passengers'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Routes'
                }
            }
        }
    }
});
</script>
</body>
</html>
