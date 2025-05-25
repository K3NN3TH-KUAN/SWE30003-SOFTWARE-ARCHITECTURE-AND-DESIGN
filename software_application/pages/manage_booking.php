<?php
session_start();
require_once '../classes/Trip.php';
require_once '../classes/Account.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$trip = new Trip();
$account = new Account();

// Handle rescheduling
if (isset($_POST['reschedule'])) {
    $bookingID = $_POST['booking_id'];
    $newTripID = $_POST['new_trip_id'];
    
    if ($trip->rescheduleBooking($bookingID, $newTripID)) {
        $_SESSION['success'] = "Trip successfully rescheduled!";
    } else {
        $_SESSION['error'] = "Failed to reschedule trip.";
    }
    header("Location: manage_booking.php");
    exit();
}

// Get user's bookings
$bookings = $trip->getUserBookings($_SESSION['accountID']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h2>My Bookings</h2>
        
        <?php foreach ($bookings as $booking): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <?php echo htmlspecialchars($booking['origin']); ?> to 
                        <?php echo htmlspecialchars($booking['destination']); ?>
                    </h5>
                    <p class="card-text">
                        Date: <?php echo date('d M Y', strtotime($booking['tripDate'])); ?><br>
                        Time: <?php echo date('h:i A', strtotime($booking['tripTime'])); ?><br>
                        Status: <span class="badge bg-<?php echo $booking['bookingStatus'] == 'Confirmed' ? 'success' : 'warning'; ?>">
                            <?php echo $booking['bookingStatus']; ?>
                        </span>
                    </p>
                    
                    <?php if ($booking['bookingStatus'] == 'Confirmed'): ?>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" 
                                data-bs-target="#rescheduleModal<?php echo $booking['bookingID']; ?>">
                            Reschedule
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reschedule Modal -->
            <div class="modal fade" id="rescheduleModal<?php echo $booking['bookingID']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reschedule Trip</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['bookingID']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Select New Trip</label>
                                    <select name="new_trip_id" class="form-select" required>
                                        <?php
                                        $availableTrips = $trip->getAvailableTrips();
                                        foreach ($availableTrips as $trip): ?>
                                            <option value="<?php echo $trip['tripID']; ?>">
                                                <?php echo $trip['origin']; ?> to <?php echo $trip['destination']; ?> - 
                                                <?php echo date('d M Y', strtotime($trip['tripDate'])); ?> 
                                                <?php echo date('h:i A', strtotime($trip['tripTime'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="reschedule" class="btn btn-warning">Confirm Reschedule</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 