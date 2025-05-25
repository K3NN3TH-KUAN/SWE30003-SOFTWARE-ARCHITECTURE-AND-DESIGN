<?php
session_start();
require_once '../classes/Account.php';
require_once '../classes/Sale.php';
require_once '../classes/LineOfSale.php';
require_once '../classes/Merchandise.php';
require_once '../classes/Notification.php';
require_once '../classes/Point.php';

if (!isset($_SESSION['accountID']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$account = new Account();
$sale = new Sale();
$lineOfSale = new LineOfSale();
$merchandise = new Merchandise();
$notification = new Notification();
$point = new Point();

$accountID = $_SESSION['accountID'];
$info = $account->getAccountByID($accountID);
$currentBalance = isset($info['accountBalance']) ? $info['accountBalance'] : 0;

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['merchandisePrice'] * $item['quantity'];
}

// Check if user has sufficient balance
if ($currentBalance < $total) {
    $_SESSION['payment_message'] = "Insufficient balance. Please top up your account.";
    $_SESSION['payment_type'] = "danger";
    $_SESSION['payment_redirect'] = "topup.php";
    header('Location: payment_status.php');
    exit();
}

try {
    // Start transaction
    $database = new Database();
    $db = $database->getConnection();
    $transactionStarted = false;
    $db->beginTransaction();
    $transactionStarted = true;

    // Create sale record using initiateNewSale
    $saleID = $sale->initiateNewSale($accountID, $total, count($_SESSION['cart']), $total);
    
    if (!$saleID) {
        throw new Exception("Failed to create sale record.");
    }

    // Process each item in cart
    foreach ($_SESSION['cart'] as $item) {
        // Create line of sale record using createNewLineOfSale
        $merchandiseDetails = $merchandise->getMerchandiseByID($item['merchandiseID']);
        $quantity = $item['quantity'];
        $merchandiseID = $item['merchandiseID'];

        if ($merchandiseDetails) {
            $itemAmount = $merchandiseDetails['merchandisePrice'];
            $totalAmountPerLineOfSale = $itemAmount * $quantity; // Calculate total amount for this line

            if (!$lineOfSale->createNewLineOfSale(
                $saleID,
                'Merchandise',
                $merchandiseID,
                $quantity,
                $itemAmount,
                $totalAmountPerLineOfSale  // Add this parameter
            )) {
                throw new Exception("Failed to create line of sale for merchandise");
            }
        }

        // Update merchandise stock
        if (!$merchandise->updateStockQuantity($item['merchandiseID'], $item['quantity'])) {
            throw new Exception("Failed to update merchandise stock.");
        }
    }

    // Update account balance
    $newBalance = $currentBalance - $total;
    if (!$account->updateAccountBalance($accountID, $newBalance)) {
        throw new Exception("Failed to update account balance.");
    }

    // Create success notification
    $notification->createNotification(
        $accountID,
        "Your purchase of RM" . number_format($total, 2) . " has been completed successfully.",
        'payment'
    );

    // Calculate points to add (1 point per RM1 spent)
    $pointsToAdd = (int)$total;

    // Check if user already has a point record
    $pointInfo = $point->getPointByAccountID($accountID);

    if ($pointInfo) {
        // Update existing point record
        $newPointBalance = $pointInfo['pointBalance'] + $pointsToAdd;
        $newTotalEarned = $pointInfo['totalPointEarned'] + $pointsToAdd;
        $stmt = $db->prepare("UPDATE point SET pointBalance = ?, totalPointEarned = ? WHERE accountID = ?");
        $stmt->execute([$newPointBalance, $newTotalEarned, $accountID]);
    } else {
        // Create new point record
        $stmt = $db->prepare("INSERT INTO point (accountID, pointBalance, totalPointEarned, pointRedeemed, pointQuantity) VALUES (?, ?, ?, 0, 0)");
        $stmt->execute([$accountID, $pointsToAdd, $pointsToAdd]);
    }

    // Commit transaction
    $db->commit();
    $transactionStarted = false;

    // Clear cart
    unset($_SESSION['cart']);

    // Set success message and redirect
    $_SESSION['last_sale_id'] = $saleID;
    $_SESSION['payment_message'] = "Payment successful! Your order has been processed.";
    $_SESSION['payment_type'] = "success";
    $_SESSION['payment_redirect'] = "dashboard.php";
    header('Location: payment_status.php');
    exit();

} catch (Exception $e) {
    // Rollback transaction only if it was started and is still active
    if (isset($db) && isset($transactionStarted) && $transactionStarted) {
        $db->rollBack();
    }

    // Create failure notification
    $notification->createNotification(
        $accountID,
        "Payment failed: " . $e->getMessage(),
        'payment'
    );

    $_SESSION['payment_message'] = "Payment failed: " . $e->getMessage();
    $_SESSION['payment_type'] = "danger";
    $_SESSION['payment_redirect'] = "checkout.php";
    header('Location: payment_status.php');
    exit();
}
?> 