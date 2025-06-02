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

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $result = $trip->cancelBooking($bookingID);
    if ($result === true) {
        $_SESSION['success'] = "Booking cancelled successfully!";
        header("Location: history.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to cancel booking. Error: " . $result;
    }
}

// Get booking details
$booking = $trip->getBookingDetails($bookingID);
if (!$booking || $booking['accountID'] != $_SESSION['accountID']) {
    header('Location: history.php');
    exit();
}

// Check if cancellation is allowed (at least 24 hours before trip)
$tripDateTime = strtotime($booking['tripDate'] . ' ' . $booking['tripTime']);
$currentDateTime = time();
$hoursDifference = ($tripDateTime - $currentDateTime) / 3600;
$canCancel = $hoursDifference > 24;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cancel Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <a href="history.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to History
        </a>
        
        <h2>Cancel Booking</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                Booking Details
            </div>
            <div class="card-body">
                <p><strong>Booking ID:</strong> #<?= $booking['bookingID'] ?></p>
                <p><strong>Route:</strong> <?= $booking['origin'] ?> to <?= $booking['destination'] ?></p>
                <p><strong>Date:</strong> <?= date('d M Y', strtotime($booking['tripDate'])) ?></p>
                <p><strong>Time:</strong> <?= date('h:i A', strtotime($booking['tripTime'])) ?></p>
                <p><strong>Status:</strong> <span class="badge bg-primary"><?= $booking['bookingStatus'] ?></span></p>
                
                <?php if (!empty($booking['refundAmount'])): ?>
                    <p><strong>Refund Amount:</strong> RM<?= number_format($booking['refundAmount'], 2) ?></p>
                    <p><strong>Refund Date:</strong> <?= $booking['refundDate'] ?></p>
                    <p><strong>Refund Time:</strong> <?= $booking['refundTime'] ?></p>
                <?php endif; ?>
                
                <?php if (!$canCancel): ?>
                    <div class="alert alert-danger mt-3">
                        Cancellation is only allowed at least 24 hours before the trip. 
                        (<?= ceil($hoursDifference) ?> hours remaining)
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($canCancel): ?>
            <div class="card">
                <div class="card-header">
                    Confirm Cancellation
                </div>
                <div class="card-body">
                    <form method="POST">
                        <p>Are you sure you want to cancel this booking?</p>
                        <button type="submit" name="cancel" class="btn btn-danger">Confirm Cancellation</button>
                        <a href="history.php" class="btn btn-secondary">Go Back</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
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