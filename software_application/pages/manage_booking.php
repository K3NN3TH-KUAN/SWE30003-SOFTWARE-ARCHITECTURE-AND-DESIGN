<?php
session_start();
require_once '../classes/Database.php';

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = "";

// Handle Refund
if (isset($_POST['refund_booking'])) {
    $bookingID = $_POST['bookingID'];
    // Example: update booking status and mark as refunded (adjust as per your schema)
    $stmt = $db->prepare("UPDATE trip_booking SET bookingStatus='Cancelled' WHERE bookingID=?");
    $stmt->execute([$bookingID]);
    // You may want to update sale/payment/refund tables as well
    $message = "Refund processed for booking ID $bookingID!";
}

// Fetch all bookings (adjust query as per your schema)
$stmt = $db->query("SELECT b.*, t.origin, t.destination, t.tripDate, t.tripTime, t.tripStatus, a.accountName, a.email
    FROM trip_booking b
    JOIN trip t ON b.tripID = t.tripID
    JOIN account a ON b.accountID = a.accountID
    ORDER BY b.bookingID DESC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .booking-card {
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            background: #fff;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .status-Booked {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-Rescheduled {
            background-color: #fef08a;
            color: #92400e;
        }
        .status-Cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Manage Bookings</h1>
            <a href="admin_dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="alert alert-warning text-center">No bookings found.</div>
        <?php endif; ?>

        <?php foreach ($bookings as $booking): ?>
            <div class="booking-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="mb-0">Booking #<?= $booking['bookingID'] ?></h4>
                    <span class="status-badge status-<?= $booking['bookingStatus'] ?>">
                        <?= $booking['bookingStatus'] ?>
                    </span>
                </div>
                <hr>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <p><strong>User:</strong> <?= htmlspecialchars($booking['accountName']) ?> (<?= htmlspecialchars($booking['email']) ?>)</p>
                        <p><strong>Trip:</strong> <?= htmlspecialchars($booking['origin']) ?> → <?= htmlspecialchars($booking['destination']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date:</strong> <?= htmlspecialchars($booking['tripDate']) ?></p>
                        <p><strong>Time:</strong> <?= htmlspecialchars($booking['tripTime']) ?></p>
                    </div>
                </div>
                <div>
                    <?php if ($booking['bookingStatus'] === 'Cancelled'): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="bookingID" value="<?= $booking['bookingID'] ?>">
                            <input type="hidden" name="refund_booking" value="1">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-cash"></i> Process Refund
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 