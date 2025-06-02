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
require_once '../classes/PointRedemption.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

// Check if booking details exist in session
if (!isset($_SESSION['booking_details'])) {
    $_SESSION['error'] = "No booking details found. Please try again.";
    header("Location: checkout_trip.php");
    exit();
}

// Debug logging
error_log("Processing payment with booking details: " . print_r($_SESSION['booking_details'], true));

// Get booking details from session
$tripID = $_SESSION['booking_details']['trip_id'];
$seatQtySelected = $_SESSION['booking_details']['seat_quantity'];
$promotionID = $_SESSION['booking_details']['promotion_id'];
$selectedMerchandise = $_SESSION['booking_details']['merchandise'] ?? [];
$redemptionID = $_SESSION['booking_details']['redemption_id'] ?? null;

$accountID = $_SESSION['accountID'];
$trip = new Trip();
$promotion = new Promotion($db);
$sale = new Sale();
$lineOfSale = new LineOfSale();
$merchandise = new Merchandise($db);
$account = new Account();
$notification = new Notification($db);
$point = new Point();
$pointRedemption = new PointRedemption();

// Initialize DB connection for voucher logic
$database = new Database();
$db = $database->getConnection();

// Helper function to get the first unused voucher for this promotion and account
function getUnusedVoucherRedemption($accountID, $promotionID, $db) {
    $stmt = $db->prepare("SELECT * FROM point_redemption WHERE accountID = ? AND itemID = ? AND itemType = 'Voucher' AND isUsed = 0 ORDER BY redemptionDate ASC, redemptionTime ASC LIMIT 1");
    $stmt->execute([$accountID, $promotionID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Initialize variables
$saleID = null;
$bookingDateTime = date('Y-m-d H:i:s');
$tripOrigin = '';
$tripDestination = '';
$tripDate = '';
$tripTime = '';
$discountedTripPrice = 0;
$totalAmount = 0;
$merchandiseLineItems = [];
$accountInfo = $account->getAccountByID($accountID);
$promotionIDForSale = null; // Will be set to promotionID if used, or null if only voucher is used

// --- PATCH: Ensure IDs are sanitized ---
$promotionID = (!empty($promotionID) && is_numeric($promotionID)) ? (int)$promotionID : null;
$redemptionID = (!empty($redemptionID) && is_numeric($redemptionID)) ? (int)$redemptionID : null;

$promotionIDForSale = null;
$isVoucher = false;
$discountRate = 0;
$promo = null;

// --- PATCH: Voucher/Promotion logic ---
if ($redemptionID) {
    // If redemptionID is set, this is a voucher redemption
    $stmt = $db->prepare("SELECT * FROM point_redemption WHERE redemptionID = ? AND accountID = ? AND itemType = 'Voucher' AND isUsed = 0 LIMIT 1");
    $stmt->execute([$redemptionID, $accountID]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($voucher) {
        $isVoucher = true;
        // Get the promotion info from the voucher's itemID
        $promo = $promotion->getPromotionByID($voucher['itemID']);
        if ($promo) {
            $discountRate = $promo['discountRate'];
        }
        $promotionIDForSale = null; // Only redemptionID is set for voucher
    }
} elseif ($promotionID) {
    // It's a regular promotion
    $promo = $promotion->getPromotionByID($promotionID);
    if ($promo && $promo['promotionQuantity'] > 0) {
        $discountRate = $promo['discountRate'];
        $promotionIDForSale = $promotionID;
    }
}

// --- PATCH: Always apply discount if discountRate > 0 ---
$tripDetails = $trip->getTripByID($tripID);
$tripPrice = $tripDetails['totalAmount'];
$discountedTripPrice = $tripPrice;
if ($discountRate > 0) {
    $discountedTripPrice = $tripPrice * (1 - $discountRate / 100);
}
$tripTotal = $discountedTripPrice * $seatQtySelected;

// Calculate merchandise total
$merchandiseTotal = 0;
$merchandiseError = false;
foreach ($selectedMerchandise as $merchID => $qty) {
    $qty = (int)$qty;
    if ($qty > 0) {
        $item = $merchandise->getMerchandiseByID($merchID);
        if ($item && $item['stockQuantity'] >= $qty) {
            $merchPrice = $item['merchandisePrice'];
            // Always apply discount if discountRate > 0 (voucher or promotion)
            if ($discountRate > 0) {
                $merchPrice = $merchPrice * (1 - $discountRate / 100);
            }
            $merchandiseLineItems[] = [
                'item' => $item,
                'quantity' => $qty,
                'subtotal' => $merchPrice * $qty,
                'discounted_price' => $merchPrice
            ];
            $merchandiseTotal += $merchPrice * $qty;
        } else {
            $message = "Insufficient stock for " . htmlspecialchars($item['merchandiseName']);
            $merchandiseError = true;
            break;
        }
    }
}

if (!$merchandiseError) {
    $totalAmount = $tripTotal + $merchandiseTotal;

    // Check account balance
    $accountInfo = $account->getAccountByID($accountID);
    $balance = $accountInfo['accountBalance'];
    if ($balance < $totalAmount) {
        $message = "Insufficient balance. <a href='topup.php'>Top up now</a>";
    } else {
        // --- PATCH: Sanitize IDs before passing to sale ---
        $promotionIDForSale = (!empty($promotionIDForSale) && is_numeric($promotionIDForSale)) ? (int)$promotionIDForSale : null;
        $redemptionID = (!empty($redemptionID) && is_numeric($redemptionID)) ? (int)$redemptionID : null;

        // Create sale with correct total, promotion ID, and redemption ID
        $lineOfSaleQuantity = 1 + count($merchandiseLineItems);
        $saleID = $sale->initiateNewSale(
            $accountID,
            $totalAmount,
            $lineOfSaleQuantity,
            $totalAmount,
            $promotionIDForSale,
            $redemptionID
        );

        if ($saleID) {
            // Update account balance
            $newBalance = $balance - $totalAmount;
            $account->updateAccountBalance($accountID, $newBalance);

            // Calculate points earned (1 point per RM1 spent)
            $pointsEarned = floor($totalAmount);
            
            // Get current point information
            $pointInfo = $point->getPointByAccountID($accountID);
            
            if ($pointInfo) {
                // Calculate new point values
                $newPointBalance = $pointInfo['pointBalance'] + $pointsEarned;
                $newTotalPointsEarned = $pointInfo['totalPointEarned'] + $pointsEarned;
                
                // Debug logging
                error_log("Processing points update for account {$accountID}:");
                error_log("Current balance: {$pointInfo['pointBalance']}");
                error_log("Points earned: {$pointsEarned}");
                error_log("New balance: {$newPointBalance}");
                
                // Update point balance
                if (!$point->updatePointBalance($accountID, $newPointBalance, $newTotalPointsEarned)) {
                    error_log("Failed to update points for account {$accountID}");
                    // Continue with the transaction even if point update fails
                }
            } else {
                // Create new point record if none exists
                error_log("Creating new point record for account {$accountID} with {$pointsEarned} points");
                if (!$point->updatePointBalance($accountID, $pointsEarned, $pointsEarned)) {
                    error_log("Failed to create point record for account {$accountID}");
                }
            }

            // Create line of sale for trip
            if (empty($saleID) || empty($tripID) || empty($seatQtySelected) || $discountedTripPrice === null) {
                error_log('ERROR: One or more required values for trip line of sale are missing!');
                error_log(print_r([
                    'saleID' => $saleID,
                    'tripID' => $tripID,
                    'seatQtySelected' => $seatQtySelected,
                    'discountedTripPrice' => $discountedTripPrice
                ], true));
                $_SESSION['error'] = "Internal error: missing booking data.";
                header("Location: checkout_trip.php");
                exit();
            }
            error_log('Creating trip line of sale: ' . print_r([
                'saleID' => $saleID,
                'tripID' => $tripID,
                'seatQtySelected' => $seatQtySelected,
                'discountedTripPrice' => $discountedTripPrice,
                'totalAmountPerLineOfSale' => $seatQtySelected * $discountedTripPrice
            ], true));
            if (!$lineOfSale->createNewLineOfSale(
                $saleID,
                'Trip',
                $tripID,
                $seatQtySelected,
                $discountedTripPrice,
                $seatQtySelected * $discountedTripPrice
            )) {
                error_log('Failed to create trip line of sale!');
                $_SESSION['error'] = "Failed to create trip line item. Please try again.";
                header("Location: checkout_trip.php");
                exit();
            }

            // Create line of sale for each merchandise
            foreach ($merchandiseLineItems as $line) {
                if (!$lineOfSale->createNewLineOfSale(
                    $saleID,
                    'Merchandise',
                    $line['item']['merchandiseID'],
                    $line['quantity'],
                    $line['discounted_price'],
                    $line['discounted_price'] * $line['quantity']
                )) {
                    $_SESSION['error'] = "Failed to create merchandise line item. Please try again.";
                    header("Location: checkout_trip.php");
                    exit();
                }
                
                // Update merchandise stock
                $merchandise->updateMerchandiseQuantity(
                    $line['item']['merchandiseID'], 
                    $line['item']['stockQuantity'] - $line['quantity']
                );
            }

            // Update trip seat quantity
            if (!$trip->updateAvailableSeats($tripID, $seatQtySelected)) {
                $_SESSION['error'] = "Failed to update seat availability. Please try again.";
                header("Location: checkout_trip.php");
                exit();
            }

            // Update trip status if no seats left
            if ($tripDetails['availableSeats'] - $seatQtySelected <= 0) {
                $trip->updateTripStatus($tripID, 'Booked');
            }

            // Mark voucher as used if applicable (use class for clarity)
            if ($isVoucher && $redemptionID) {
                $pointRedemption->markAsUsed($redemptionID);
            } elseif ($promotionID && $promo) {
                $promotion->decrementPromotionQuantity($promotionID);
            }

            // --- Generate Notification ---
            $notification->createNotification(
                $accountID,
                "Your trip from {$tripDetails['origin']} to {$tripDetails['destination']} on {$tripDetails['tripDate']} at {$tripDetails['tripTime']} has been successfully booked.",
                'booking'
            );

            // Create notification for points earned
            $notification->createNotification(
                $accountID,
                "You earned " . number_format($pointsEarned) . " points from your recent purchase!",
                'payment'
            );

            // Create the booking
            $trip->createBooking($saleID, $tripID, $accountID);

            $success = true;
            $message = "Trip booked and payment successful!";
        } else {
            $_SESSION['error'] = "Failed to create sale. Please try again.";
            header("Location: checkout_trip.php");
            exit();
        }
    }
}

if ($success) {
    $bookingDateTime = date('Y-m-d H:i:s');
    $tripOrigin = $tripDetails['origin'];
    $tripDestination = $tripDetails['destination'];
    $tripDate = $tripDetails['tripDate'];
    $tripTime = $tripDetails['tripTime'];
    $qrImagePath = "../assets/images/qrcode.png";
    
    // Clear booking details from session
    unset($_SESSION['booking_details']);
    
    // Set success message
    $_SESSION['success'] = "Payment processed successfully!";
} else {
    // Set error message
    $_SESSION['error'] = $message ?? "An error occurred during payment processing.";
    header("Location: checkout_trip.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kuching ART - Invoice Receipt</title>
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
    <h2 class="text-center receipt-title mb-4">Kuching ART - Invoice Receipt</h2>
    <hr>
    <div class="mb-2"><b>Receipt #:</b> <?php echo htmlspecialchars($saleID); ?></div>
    <div class="mb-2"><b>Booking Date & Time:</b> <?php echo htmlspecialchars($bookingDateTime); ?></div>
    <div class="mb-2"><b>Account:</b> <?php echo htmlspecialchars($accountInfo['accountName']); ?> (<?php echo htmlspecialchars($accountInfo['email']); ?>)</div>
    <div class="mb-2"><b>Trip Date & Time:</b> <?php echo htmlspecialchars($tripDate . ' ' . $tripTime); ?></div>
    <div class="mb-2"><b>Origin:</b> <?php echo htmlspecialchars($tripOrigin); ?></div>
    <div class="mb-2"><b>Destination:</b> <?php echo htmlspecialchars($tripDestination); ?></div>
    <div class="mb-2"><b>Seats Booked:</b> <?php echo $seatQtySelected; ?></div>
    <hr>
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Item Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <!-- Trip Line -->
            <tr>
                <td><?php echo htmlspecialchars($tripOrigin . " to " . $tripDestination); ?></td>
                <td>Trip</td>
                <td><?php echo $seatQtySelected; ?></td>
                <td>RM<?php echo number_format($discountedTripPrice, 2); ?></td>
                <td>RM<?php echo number_format($seatQtySelected * $discountedTripPrice, 2); ?></td>
            </tr>
            <!-- Merchandise Lines -->
            <?php if (!empty($merchandiseLineItems)): ?>
                <?php foreach ($merchandiseLineItems as $line): ?>
                <tr>
                    <td><?php echo htmlspecialchars($line['item']['merchandiseName']); ?></td>
                    <td>Merchandise</td>
                    <td><?php echo $line['quantity']; ?></td>
                    <td>RM<?php echo number_format($line['discounted_price'], 2); ?></td>
                    <td>RM<?php echo number_format($line['discounted_price'] * $line['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            <b>Total Paid:</b> <span class="text-success fs-5">RM<?php echo number_format($totalAmount, 2); ?></span>
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