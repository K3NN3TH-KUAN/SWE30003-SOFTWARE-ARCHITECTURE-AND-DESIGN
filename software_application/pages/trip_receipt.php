<?php
session_start();
require_once '../classes/Trip.php';
require_once '../classes/Promotion.php';
require_once '../classes/Sale.php';
require_once '../classes/LineOfSale.php';
require_once '../classes/Merchandise.php';
require_once '../classes/Account.php';
require_once '../classes/Notification.php';
require_once '../classes/Point.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$accountID = $_SESSION['accountID'];
$saleID = $_GET['view_receipt'] ?? null;

if (!$saleID) {
    header('Location: history.php');
    exit();
}

$sale = new Sale();
$saleDetails = $sale->getSaleByID($saleID);

if (!$saleDetails || $saleDetails['accountID'] != $accountID) {
    header('Location: history.php');
    exit();
}

$lineOfSale = new LineOfSale();
$lineItems = $lineOfSale->getLineOfSaleBySaleID($saleID);

// Find the trip line item
$tripID = null;
$seatQtySelected = 0;
foreach ($lineItems as $item) {
    if ($item['type'] === 'Trip') {
        $tripID = $item['tripID'];
        $seatQtySelected = $item['itemQuantity'];
        break;
    }
}

if (!$tripID) {
    header('Location: history.php');
    exit();
}

$trip = new Trip();
$tripDetails = $trip->getTripByID($tripID);
$account = new Account();
$accountInfo = $account->getAccountByID($accountID);

// Set variables for the receipt view
$bookingDateTime = $saleDetails['saleDate'] . ' ' . $saleDetails['saleTime'];
$tripOrigin = $tripDetails['origin'];
$tripDestination = $tripDetails['destination'];
$tripDate = $tripDetails['tripDate'];
$tripTime = $tripDetails['tripTime'];
$qrImagePath = "../assets/images/qrcode.png";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kuching ART - Trip Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-box { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 1rem; box-shadow: 0 2px 12px rgba(99,102,241,0.10); padding: 2rem; }
        .receipt-title { color: #3b5bdb; }
        .qr-ticket { text-align: center; margin-top: 2rem; }
        .qr-ticket img { border: 8px solid #e0e7ff; border-radius: 1rem; }
    </style>
</head>
<body>
<div class="receipt-box">
    <h2 class="text-center receipt-title mb-4">Kuching ART - Trip Receipt</h2>
    <hr>
    <div class="mb-2"><b>Receipt #:</b> <?php echo htmlspecialchars($saleID); ?></div>
    <div class="mb-2"><b>Booking Date & Time:</b> <?php echo htmlspecialchars($bookingDateTime); ?></div>
    <div class="mb-2"><b>Account:</b> <?php echo htmlspecialchars($accountInfo['accountName']); ?> </div>
    
    <!-- Trip Details Section -->
    <div class="mt-4">
        <h5 class="mb-3">Trip Details</h5>
        <div class="mb-2"><b>Trip Date & Time:</b> <?php echo htmlspecialchars($tripDate . ' ' . $tripTime); ?></div>
        <div class="mb-2"><b>Origin:</b> <?php echo htmlspecialchars($tripOrigin); ?></div>
        <div class="mb-2"><b>Destination:</b> <?php echo htmlspecialchars($tripDestination); ?></div>
        <div class="mb-2"><b>Seats Booked:</b> <?php echo $seatQtySelected; ?></div>
    </div>

    <!-- Items Purchased Section -->
    <div class="mt-4">
        <h5 class="mb-3">Items Purchased</h5>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <!-- Trip Line -->
                <tr>
                    <td><?php echo htmlspecialchars($tripOrigin . " to " . $tripDestination); ?></td>
                    <td>Trip</td>
                    <td><?php echo $seatQtySelected; ?></td>
                    <td>RM<?php echo number_format($tripDetails['totalAmount'], 2); ?></td>
                    <td>RM<?php echo number_format($tripDetails['totalAmount'] * $seatQtySelected, 2); ?></td>
                </tr>
                <!-- Merchandise Lines -->
                <?php 
                $merchandise = new Merchandise();
                foreach ($lineItems as $item) {
                    if ($item['type'] === 'Merchandise' && $item['merchandiseID']) {
                        $merch = $merchandise->getMerchandiseByID($item['merchandiseID']);
                        if ($merch) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($merch['merchandiseName']) . '</td>';
                            echo '<td>Merchandise</td>';
                            echo '<td>' . $item['itemQuantity'] . '</td>';
                            echo '<td>RM' . number_format($merch['merchandisePrice'], 2) . '</td>';
                            echo '<td>RM' . number_format($item['totalAmountPerLineOfSale'], 2) . '</td>';
                            echo '</tr>';
                        }
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <hr>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            <b>Total Paid:</b> <span class="text-success fs-5">RM<?php echo number_format($saleDetails['totalAmountPay'], 2); ?></span>
        </div>
        <div>
            <b>Status:</b> <span class="text-success">Completed</span>
        </div>
    </div>

    <hr>
    <div class="qr-ticket">
        <h5 class="mb-2">Your Trip Ticket QR</h5>
        <img src="<?php echo $qrImagePath; ?>" alt="Trip Ticket QR Code" style="width:200px;height:200px;">
        <div class="mt-2 text-muted">Show this QR code when boarding the ART!</div>
    </div>
    <hr>
    <div class="text-center text-muted mt-3">
        Thank you for your purchase with Kuching ART!
    </div>
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        <button onclick="window.print()" class="btn btn-outline-secondary ms-2">Print Receipt</button>
    </div>
</div>
</body>
</html> 