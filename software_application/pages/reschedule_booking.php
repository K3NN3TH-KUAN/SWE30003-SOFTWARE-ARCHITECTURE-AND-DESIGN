<?php
session_start();
require_once '../classes/Trip.php';
require_once '../classes/Account.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$bookingID = $_GET['booking_id'] ?? null;
$trip = new Trip();
$account = new Account();

// Handle rescheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule'])) {
    $bookingID = $_POST['booking_id'];
    $newTripID = $_POST['new_trip_id'];
    
    if ($trip->rescheduleBooking($bookingID, $newTripID)) {
        $_SESSION['success'] = "Trip successfully rescheduled!";
        header("Location: history.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to reschedule trip.";
    }
}

// Get booking details
$booking = $trip->getBookingDetails($bookingID);
if (!$booking || $booking['accountID'] != $_SESSION['accountID']) {
    header('Location: history.php');
    exit();
}

// Get available trips for reschedule
$availableTrips = $trip->getAvailableTripsForReschedule($booking['tripID']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reschedule Trip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <a href="history.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to History
        </a>
        
        <h2>Reschedule Trip</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                Current Booking Details
            </div>
            <div class="card-body">
                <p><strong>Booking ID:</strong> #<?= $booking['bookingID'] ?></p>
                <p><strong>Route:</strong> <?= $booking['origin'] ?> to <?= $booking['destination'] ?></p>
                <p><strong>Date:</strong> <?= date('d M Y', strtotime($booking['tripDate'])) ?></p>
                <p><strong>Time:</strong> <?= date('h:i A', strtotime($booking['tripTime'])) ?></p>
                <p><strong>Status:</strong> <span class="badge bg-primary"><?= $booking['bookingStatus'] ?></span></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                Select New Trip
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="booking_id" value="<?= $bookingID ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Available Trips:</label>
                        <?php if (!empty($availableTrips)): ?>
                            <select name="new_trip_id" class="form-select" required>
                                <?php foreach ($availableTrips as $trip): ?>
                                    <option value="<?= $trip['tripID'] ?>">
                                        <?= $trip['origin'] ?> to <?= $trip['destination'] ?> - 
                                        <?= date('d M Y', strtotime($trip['tripDate'])) ?> 
                                        <?= date('h:i A', strtotime($trip['tripTime'])) ?>
                                        (Seats: <?= $trip['availableSeats'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No available trips for rescheduling on this route.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" name="reschedule" class="btn btn-warning" 
                        <?= empty($availableTrips) ? 'disabled' : '' ?>>
                        Confirm Reschedule
                    </button>
                </form>
            </div>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>