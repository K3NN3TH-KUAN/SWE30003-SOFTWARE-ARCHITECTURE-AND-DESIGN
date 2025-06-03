<?php
// Start the session to track user login and selections
session_start();
// Include required class files for promotions, trips, and database connection
require_once '../classes/Promotion.php';
require_once '../classes/Trip.php';
require_once '../classes/Database.php';
// Create a new database connection
$database = new Database();
$db = $database->getConnection();

// Check if the user is logged in; if not, redirect to login page
if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

// Instantiate Trip and Promotion objects for use in this page
$trip = new Trip();
$promotion = new Promotion($db);

// Define available origins and destinations for trip search
$origins = ['Kuching Sentral', 'Pending', 'Samarahan'];
$destinations = ['Kuching Sentral', 'Pending', 'Samarahan'];

// Handle promotion application from points.php or promotion.php
if (isset($_POST['apply_promotion'])) {
    // Store selected promotion in session for use during booking
    $_SESSION['selected_promotion'] = [
        'promotion_id' => $_POST['promotion_id'],
        'discount_rate' => $_POST['discount_rate']
    ];
    // Optionally redirect to clear POST and avoid resubmission
    header("Location: book_trip.php");
    exit();
}

// Handle trip search logic
$searchResults = [];
if (isset($_GET['search'])) {
    // Get search parameters from GET request
    $date = $_GET['trip_date'] ?? '';
    $time = $_GET['trip_time'] ?? '';
    $origin = $_GET['origin'] ?? '';
    $destination = $_GET['destination'] ?? '';
    // Search for trips based on criteria
    $searchResults = $trip->searchTrips($date, $time, $origin, $destination);
} else {
    // Get all available trips if no search criteria
    $searchResults = $trip->getAvailableTrips();
}

// Get available promotions for display
$availablePromotions = $promotion->getAvailablePromotions('Promotion');

// Handle trip selection and redirect to checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_trip'])) {
    $tripID = $_POST['trip_id'];
    // Store selected trip in session
    $_SESSION['selected_trip'] = $tripID;
    // Redirect to checkout page
    header('Location: checkout_trip.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book a Trip</title>
    <!-- Bootstrap and icon CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%); min-height: 100vh; }
        .trip-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.10);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }
        .trip-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-btn {
            position: absolute;
            top: 24px;
            right: 32px;
            z-index: 10;
        }
        .promo-alert {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container py-4 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-4 fw-bold"><i class="bi bi-train-front"></i> Book a Trip</h2>
            <!-- Dashboard button for navigation -->
            <a href="dashboard.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>

        <!-- Trip search form -->
        <form class="row g-3 mb-4" method="get" action="">
            <div class="col-md-3">
                <label for="trip_date" class="form-label">Date</label>
                <input type="date" class="form-control" id="trip_date" name="trip_date" value="<?php echo htmlspecialchars($_GET['trip_date'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label for="trip_time" class="form-label">Time (after)</label>
                <input type="time" class="form-control" id="trip_time" name="trip_time" value="<?php echo htmlspecialchars($_GET['trip_time'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="origin" class="form-label">Origin</label>
                <select class="form-select" id="origin" name="origin">
                    <option value="">Any</option>
                    <?php foreach ($origins as $o): ?>
                        <option value="<?php echo $o; ?>" <?php if (($_GET['origin'] ?? '') == $o) echo 'selected'; ?>><?php echo $o; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="destination" class="form-label">Destination</label>
                <select class="form-select" id="destination" name="destination">
                    <option value="">Any</option>
                    <?php foreach ($destinations as $d): ?>
                        <option value="<?php echo $d; ?>" <?php if (($_GET['destination'] ?? '') == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" name="search" value="1" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <!-- Display applied promotion if any -->
        <?php if (isset($_SESSION['selected_promotion'])): ?>
        <div class="alert alert-success promo-alert">
            <i class="bi bi-tag"></i> Promotion Applied: <?php echo $_SESSION['selected_promotion']['discount_rate']; ?>% discount
            <a href="book_trip.php?remove_promo=1" class="btn btn-sm btn-outline-danger ms-3">Remove</a>
        </div>
        <?php endif; ?>

        <?php
        // Remove promotion if requested
        if (isset($_GET['remove_promo'])) {
            unset($_SESSION['selected_promotion']);
            echo '<script>window.location="book_trip.php";</script>';
            exit();
        }
        ?>

        <div class="row">
            <?php if (empty($searchResults)): ?>
                <div class="col-12">
                    <!-- No trips found message -->
                    <div class="alert alert-info">No trips found for your criteria.</div>
                </div>
            <?php else: ?>
                <?php foreach ($searchResults as $trip): ?>
                <div class="col-md-6 mb-4">
                    <div class="trip-card card p-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($trip['origin']); ?> to 
                                <?php echo htmlspecialchars($trip['destination']); ?>
                            </h5>
                            <div class="mb-3">
                                <p class="mb-1">
                                    <i class="bi bi-calendar"></i> Date: <?php echo date('d M Y', strtotime($trip['tripDate'])); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-clock"></i> Time: <?php echo date('h:i A', strtotime($trip['tripTime'])); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-currency-dollar"></i> Price: RM<?php echo number_format($trip['totalAmount'], 2); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-people"></i> Seats Available: 
                                    <span class="badge bg-success"><?php echo (int)$trip['availableSeats']; ?></span> 
                                    of <?php echo (int)$trip['maxSeats']; ?>
                                </p>
                            </div>
                            
                            <?php if ($trip['tripStatus'] == 'Available' && $trip['availableSeats'] > 0): ?>
                            <!-- Trip selection form -->
                            <form method="POST" action="">
                                <input type="hidden" name="trip_id" value="<?php echo $trip['tripID']; ?>">
                                <button type="submit" name="select_trip" class="btn btn-primary">
                                    Select Trip
                                </button>
                            </form>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="bi bi-x-circle"></i> 
                                    <?php 
                                    if ($trip['tripStatus'] != 'Available') {
                                        echo 'Not Available';
                                    } else {
                                        echo 'No Seats Available';
                                    }
                                    ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <footer class="bg-light text-center text-lg-start mt-5 border-top shadow-sm">
        <div class="container py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <span class="mx-2">@Group_21 (A3)</span>
                    <span class="mx-2">|</span>
                    Kuching ART Website &copy; <?php echo date('Y'); ?>. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS for UI components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
