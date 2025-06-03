<?php
// Start the session to access user data
session_start();
require_once '../classes/Sale.php';
require_once '../classes/LineOfSale.php';
require_once '../classes/Merchandise.php';
require_once '../classes/Trip.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$accountID = $_SESSION['accountID'];
$sale = new Sale();
$sales = $sale->getSalesByAccountID($accountID);

$reorderSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder_sale_id'])) {
    $reorderSaleID = $_POST['reorder_sale_id'];
    $lineOfSale = new LineOfSale();
    $items = $lineOfSale->getLineOfSaleBySaleID($reorderSaleID);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    foreach ($items as $item) {
        if ($item['type'] === 'Merchandise' && !empty($item['merchandiseID'])) {
            $merchandise = new Merchandise($db);
            $merch = $merchandise->getMerchandiseByID($item['merchandiseID']);
            if ($merch) {
                $found = false;
                foreach ($_SESSION['cart'] as &$cartItem) {
                    if ($cartItem['merchandiseID'] == $merch['merchandiseID']) {
                        $cartItem['quantity'] += $item['itemQuantity'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'merchandiseID' => $merch['merchandiseID'],
                        'merchandiseName' => $merch['merchandiseName'],
                        'merchandisePrice' => $merch['merchandisePrice'],
                        'merchandiseImage' => $merch['merchandiseImage'],
                        'merchandiseCategory' => $merch['merchandiseCategory'],
                        'quantity' => $item['itemQuantity']
                    ];
                }
            }
        }
    }
    $reorderSuccess = true;
}

$statusColors = [
    'Booked' => 'success',
    'Rescheduled' => 'warning',
    'Cancelled' => 'secondary'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%); min-height: 100vh; }
        .history-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.10);
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .history-card:hover {
            box-shadow: 0 6px 24px rgba(99,102,241,0.18);
            transform: translateY(-2px) scale(1.01);
        }
        .status-badge {
            font-size: 1em;
            padding: 0.5em 1em;
        }
        .dashboard-btn {
            position: absolute;
            top: 24px;
            right: 32px;
            z-index: 10;
        }
        @media (max-width: 576px) {
            .dashboard-btn { right: 12px; top: 12px; }
        }
    </style>
</head>
<body>
    <div class="container py-4 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-4 fw-bold"><i class="bi bi-clock-history"></i> Purchase History</h2>
            <a href="dashboard.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>
        
        <?php if ($reorderSuccess): ?>
            <div class="alert alert-success">Items from this sale have been added to your cart! <a href="cart.php" class="alert-link">View Cart</a></div>
        <?php endif; ?>
        <?php if (empty($sales)): ?>
            <div class="alert alert-info">You have no purchase history yet.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($sales as $sale): 
                    $lineOfSale = new LineOfSale();
                    $lineItems = $lineOfSale->getLineOfSaleBySaleID($sale['saleID']);
                    $hasTrip = false;
                    $tripDetails = null;
                    $bookingDetails = null;
                    
                    foreach ($lineItems as $item) {
                        if ($item['type'] === 'Trip' && $item['tripID']) {
                            $hasTrip = true;
                            $trip = new Trip();
                            $tripDetails = $trip->getTripByID($item['tripID']);
                            $bookingDetails = $trip->getBookingBySaleID($sale['saleID']);
                            break;
                        }
                    }
                ?>
                    <div class="col-12">
                        <div class="card history-card h-100 mx-auto position-relative" style="max-width: 800px;">
                            <span class="badge status-badge bg-<?php echo $sale['saleStatus'] === 'Completed' ? 'success' : 'secondary'; ?> position-absolute" style="top: 18px; right: 18px;">
                                <?php echo htmlspecialchars($sale['saleStatus']); ?>
                            </span>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="fw-bold text-primary me-3">#<?php echo htmlspecialchars($sale['saleID']); ?></span>
                                </div>
                                <div class="mb-2">
                                    <i class="bi bi-calendar-event"></i>
                                    <?php echo htmlspecialchars($sale['saleDate']); ?>
                                    <span class="ms-2"><i class="bi bi-clock"></i> <?php echo htmlspecialchars($sale['saleTime']); ?></span>
                                </div>
                                <div>
                                    <span class="fw-bold text-success fs-5">RM<?php echo number_format($sale['totalAmountPay'], 2); ?></span>
                                </div>
                                
                                <?php if ($sale['saleStatus'] === 'Completed' && $hasTrip && $tripDetails): ?>
                                    <div class="mt-2">
                                        <strong>Booking Status:</strong> 
                                        <?php if ($bookingDetails): ?>
                                            <span class="badge bg-<?= $statusColors[$bookingDetails['bookingStatus']] ?>">
                                                <?= $bookingDetails['bookingStatus'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Processing</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($sale['saleStatus'] === 'Completed' && $hasTrip && $tripDetails): ?>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <h6 class="mb-2">Trip Details:</h6>
                                    <p class="mb-2">
                                        <strong>From:</strong> <?php echo htmlspecialchars($tripDetails['origin']); ?><br>
                                        <strong>To:</strong> <?php echo htmlspecialchars($tripDetails['destination']); ?><br>
                                        <strong>Date:</strong> <?php echo htmlspecialchars($tripDetails['tripDate']); ?><br>
                                        <strong>Time:</strong> <?php echo htmlspecialchars($tripDetails['tripTime']); ?>
                                    </p>
                                    <div class="text-center">
                                        <img src="../assets/images/qrcode.png" alt="Trip QR Code" style="width: 150px; height: 150px;" class="border p-2 rounded">
                                        <p class="text-muted mt-2 small">Show this QR code when boarding the ART</p>
                                    </div>
                                    
                                    <?php if ($bookingDetails && $bookingDetails['bookingStatus'] !== 'Cancelled'): ?>
                                        <div class="d-flex mt-3 gap-2">
                                            <a href="reschedule_booking.php?booking_id=<?= $bookingDetails['bookingID'] ?>" 
                                               class="btn btn-warning btn-sm flex-fill">
                                                <i class="bi bi-calendar-event"></i> Reschedule
                                            </a>
                                            <a href="cancel_booking.php?booking_id=<?= $bookingDetails['bookingID'] ?>" 
                                               class="btn btn-outline-danger btn-sm flex-fill">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <?php if ($hasTrip && $sale['saleStatus'] === 'Completed'): ?>
                                        <a href="trip_receipt.php?view_receipt=<?php echo urlencode($sale['saleID']); ?>" class="btn btn-outline-primary btn-sm me-2" target="_blank">
                                            <i class="bi bi-receipt"></i> View Trip Receipt
                                        </a>
                                    <?php else: ?>
                                        <a href="receipt.php?saleID=<?php echo urlencode($sale['saleID']); ?>" class="btn btn-outline-primary btn-sm me-2" target="_blank">
                                            <i class="bi bi-receipt"></i> View Receipt
                                        </a>
                                    <?php endif; ?>
                                    <form action="history.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="reorder_sale_id" value="<?php echo $sale['saleID']; ?>">
                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-arrow-repeat"></i> Reorder
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
</body>
</html>