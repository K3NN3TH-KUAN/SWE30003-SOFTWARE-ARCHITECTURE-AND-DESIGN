<?php
session_start();
require_once '../classes/Account.php';
require_once '../classes/Sale.php';
require_once '../classes/LineOfSale.php';
require_once '../classes/Merchandise.php';
require_once '../classes/Notification.php';
require_once '../classes/Point.php';
require_once '../classes/Promotion.php';
require_once '../classes/PointRedemption.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['accountID']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$account = new Account();
$sale = new Sale();
$lineOfSale = new LineOfSale();
$merchandise = new Merchandise($db);
$notification = new Notification($db);
$point = new Point();
$promotion = new Promotion($db);
$pointRedemption = new PointRedemption();

$accountID = $_SESSION['accountID'];
$info = $account->getAccountByID($accountID);
$currentBalance = isset($info['accountBalance']) ? $info['accountBalance'] : 0;

// Get promotion/voucher selection from session
$promotionID = null;
$redemptionID = null;
$discountRate = 0;

if (isset($_SESSION['checkout_discount'])) {
    $promotionID = $_SESSION['checkout_discount']['promotionID'] ?? null;
    $redemptionID = $_SESSION['checkout_discount']['redemptionID'] ?? null;
}

// Debug log
error_log("Session checkout_discount: " . print_r($_SESSION['checkout_discount'], true));
error_log("PromotionID: " . $promotionID);
error_log("RedemptionID: " . $redemptionID);

// If voucher is selected, get discount from voucher's promotion
if ($redemptionID) {
    $voucher = $pointRedemption->getRedemptionByID($redemptionID);
    if ($voucher && $voucher['itemType'] === 'Voucher' && isset($voucher['itemID'])) {
        $promo = $promotion->getPromotionByID($voucher['itemID']);
        if ($promo) {
            $discountRate = $promo['discountRate'];
            $promotionID = $promo['promotionID']; // For sale record
        }
    }
} elseif ($promotionID) {
    $promo = $promotion->getPromotionByID($promotionID);
    if ($promo) {
        $discountRate = $promo['discountRate'];
    }
}

// Calculate total and discount
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['merchandisePrice'] * $item['quantity'];
}
$discountAmount = $discountRate > 0 ? ($total * $discountRate / 100) : 0;
$totalAfterDiscount = $total - $discountAmount;

// Debug log
error_log("Total before discount: " . $total);
error_log("Discount rate: " . $discountRate);
error_log("Discount amount: " . $discountAmount);
error_log("Total after discount: " . $totalAfterDiscount);

// Check if user has sufficient balance
if ($currentBalance < $totalAfterDiscount) {
    $_SESSION['payment_message'] = "Insufficient balance. Please top up your account.";
    $_SESSION['payment_type'] = "danger";
    $_SESSION['payment_redirect'] = "topup.php";
    header('Location: payment_status.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // Sanitize IDs before sale creation
    $promotionID = ($promotionID === '' || $promotionID === null) ? null : (int)$promotionID;
    $redemptionID = ($redemptionID === '' || $redemptionID === null) ? null : (int)$redemptionID;

    // 1. Create sale record
    $saleID = $sale->initiateNewSale(
        $accountID,
        $total,                    // lineOfSaleAmount (before discount)
        count($_SESSION['cart']),  // lineOfSaleQuantity
        $totalAfterDiscount,       // totalAmountPay (after discount)
        $promotionID,
        $redemptionID
    );

    if (!$saleID) {
        error_log("Failed to create sale. Params: " . print_r([
            $accountID, $total, count($_SESSION['cart']), $totalAfterDiscount, $promotionID, $redemptionID
        ], true));
        throw new Exception("Failed to create sale record. Please try again.");
    }

    // 2. For each cart item, create line of sale record
    foreach ($_SESSION['cart'] as $item) {
        $merchandiseDetails = $merchandise->getMerchandiseByID($item['merchandiseID']);
        if (!$merchandiseDetails) {
            throw new Exception("Merchandise not found: " . $item['merchandiseID']);
        }

        $quantity = $item['quantity'];
        $merchandiseID = $item['merchandiseID'];
        $itemAmount = $merchandiseDetails['merchandisePrice'];
        $totalAmountPerLineOfSale = $itemAmount * $quantity;

        $lineOfSaleResult = $lineOfSale->createNewLineOfSale(
            $saleID,
            'Merchandise',
            $merchandiseID,
            $quantity,
            $itemAmount,
            $totalAmountPerLineOfSale
        );

        if (!$lineOfSaleResult) {
            throw new Exception("Failed to create line of sale for merchandise ID: " . $merchandiseID);
        }

        // 3. Update merchandise stock
        $stockUpdateResult = $merchandise->updateStockQuantity($merchandiseID, $quantity);
        if (!$stockUpdateResult) {
            throw new Exception("Failed to update stock for merchandise ID: " . $merchandiseID);
        }
    }

    // 4. Update account balance
    $newBalance = $currentBalance - $totalAfterDiscount;
    if (!$account->updateAccountBalance($accountID, $newBalance)) {
        throw new Exception("Failed to update account balance.");
    }

    // 5. Mark voucher as used if applicable
    if ($redemptionID) {
        $pointRedemption->markAsUsed($redemptionID);
    }

    // 6. Create notification, add points, etc.
    // Create success notification
    $notification->createNotification(
        $accountID,
        "Your purchase of RM" . number_format($totalAfterDiscount, 2) . " has been completed successfully.",
        'payment'
    );

    // Calculate and add points
    $pointsToAdd = (int)$totalAfterDiscount;
    $pointInfo = $point->getPointByAccountID($accountID);

    if ($pointInfo) {
        $newPointBalance = $pointInfo['pointBalance'] + $pointsToAdd;
        $newTotalEarned = $pointInfo['totalPointEarned'] + $pointsToAdd;
        $stmt = $db->prepare("UPDATE point SET pointBalance = ?, totalPointEarned = ? WHERE accountID = ?");
        $stmt->execute([$newPointBalance, $newTotalEarned, $accountID]);
    } else {
        $stmt = $db->prepare("INSERT INTO point (accountID, pointBalance, totalPointEarned, pointRedeemed, pointQuantity) VALUES (?, ?, ?, 0, 0)");
        $stmt->execute([$accountID, $pointsToAdd, $pointsToAdd]);
    }

    // 7. Commit transaction
    $db->commit();

    // 8. Clear cart and session discount
    unset($_SESSION['cart']);
    unset($_SESSION['checkout_discount']);

    // 9. Redirect to receipt/success
    $_SESSION['last_sale_id'] = $saleID;
    $_SESSION['payment_message'] = "Payment successful! Your order has been processed.";
    $_SESSION['payment_type'] = "success";
    $_SESSION['payment_redirect'] = "receipt.php?saleID=" . $saleID;
    header('Location: payment_status.php');
    exit();

} catch (Exception $e) {
    if (isset($db)) {
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
 