<?php
session_start();
require_once '../classes/Point.php';
require_once '../classes/Account.php';
require_once '../classes/Promotion.php';
require_once '../classes/PointRedemption.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$point = new Point();
$account = new Account();
$promotion = new Promotion();
$pointRedemption = new PointRedemption();

$accountID = $_SESSION['accountID'];
$pointInfo = $point->getPointByAccountID($accountID);
$accountInfo = $account->getAccountByID($accountID);
$availableItems = $pointRedemption->getAvailableRedemptionItems();
$redeemedItems = $pointRedemption->getRedemptionsByAccountID($accountID);

// Handle redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_points'])) {
    $promotionID = $_POST['item_id'];
    $pointsCost = $_POST['points_cost'];
    
    try {
        if ($pointRedemption->canRedeem($accountID, $pointsCost)) {
            // Process the redemption using the Promotion class's method
            if ($promotion->processRedemption($accountID, $promotionID, $pointsCost, $point, $pointRedemption)) {
                $_SESSION['success'] = "Voucher redeemed successfully!";
            }
        } else {
            $_SESSION['error'] = "Insufficient points for redemption.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to process redemption: " . $e->getMessage();
    }
    
    header("Location: points.php");
    exit();
}

// When redeeming a voucher
if (isset($_POST['redeem_voucher'])) {
    $accountID = $_SESSION['accountID'];
    $promotionID = $_POST['promotion_id'];
    $pointsCost = $_POST['points_cost'];

    // Get current point information
    $pointInfo = $point->getPointByAccountID($accountID);
    
    if ($pointInfo) {
        // Calculate new point values
        $newPointBalance = $pointInfo['pointBalance'] - $pointsCost;
        $newTotalPointsEarned = $pointInfo['totalPointEarned']; // Keep total earned the same
        
        // Update point balance
        if ($point->updatePointBalance($accountID, $newPointBalance, $newTotalPointsEarned)) {
            // Create point redemption record
            $pointRedemption = new PointRedemption();
            $pointRedemption->createRedemption(
                $accountID,
                $promotionID,
                'Voucher',
                $pointsCost,
                1,
                date('Y-m-d'),
                date('H:i:s')
            );
            
            $_SESSION['success'] = "Voucher redeemed successfully!";
        } else {
            $_SESSION['error'] = "Failed to update points balance.";
        }
    } else {
        $_SESSION['error'] = "No point record found for your account.";
    }
    
    header("Location: points.php");
    exit();
}

// Get available promotions (only Voucher type)
$availablePromotions = $promotion->getAvailablePromotions('Voucher');

// Get redeemed vouchers
$redeemedVouchers = $promotion->getRedeemedVouchers($accountID);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Points & Rewards - Kuching ART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .points-balance {
            background: linear-gradient(135deg, #3b5bdb, #4c6ef5);
            color: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(59, 91, 219, 0.2);
        }
        .points-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            font-weight: bold;
            backdrop-filter: blur(5px);
        }
        .reward-card {
            background: white;
            border: none;
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .reward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(59, 91, 219, 0.15);
        }
        .reward-header {
            background: #f0f4ff;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .discount-badge {
            background: #3b5bdb;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-weight: bold;
        }
        .btn-redeem {
            background: #3b5bdb;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-redeem:hover {
            background: #4c6ef5;
            transform: translateY(-2px);
        }
        .btn-redeem:disabled {
            background: #e9ecef;
            color: #6c757d;
        }
        .alert {
            border-radius: 1rem;
            border: none;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-dashboard {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #3b5bdb;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-dashboard:hover {
            background: #4c6ef5;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Back to Dashboard Button -->
        <a href="dashboard.php" class="btn btn-dashboard">
            <i class="bi bi-house-door"></i> Back to Dashboard
        </a>

        <h2 class="mb-4 text-center"><i class="bi bi-gift"></i> Points & Rewards</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success text-center"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Points Balance Section -->
        <div class="points-balance text-center">
            <h4 class="mb-3">Your Points Balance</h4>
            <div class="d-flex justify-content-center align-items-center">
                <div class="points-badge me-3">
                    <i class="bi bi-star-fill me-2"></i>
                    <?php echo number_format($pointInfo ? $pointInfo['pointBalance'] : 0); ?> Points
                </div>
            </div>
            <p class="mt-3 mb-0">Earn 1 point for every RM1 spent on trips or merchandise</p>
        </div>

        <!-- Available Rewards Section -->
        <h4 class="mb-4 text-center">Available Vouchers</h4>
        <div class="row">
            <?php foreach ($availablePromotions as $promo): ?>
                <div class="col-md-6">
                    <div class="reward-card">
                        <div class="reward-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Discount Voucher</h5>
                                <span class="discount-badge"><?php echo $promo['discountRate']; ?>% OFF</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="text-muted mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Valid until: <?php echo date('d M Y', strtotime($promo['expireDate'])); ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Available: <?php echo $promo['promotionQuantity']; ?> vouchers
                            </p>
                            <p class="text-muted mb-0">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Use on any trip booking or merchandise purchasing 
                            </p>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="item_id" value="<?php echo $promo['promotionID']; ?>">
                            <input type="hidden" name="item_type" value="Promotion">
                            <input type="hidden" name="points_cost" value="<?php echo $promo['discountRate'] * 10; ?>">
                            <button type="submit" name="redeem_points" class="btn btn-redeem w-100"
                                <?php echo ($pointInfo && $pointInfo['pointBalance'] >= ($promo['discountRate'] * 10)) ? '' : 'disabled'; ?>>
                                <?php if ($pointInfo && $pointInfo['pointBalance'] >= ($promo['discountRate'] * 10)): ?>
                                    Redeem for <?php echo $promo['discountRate'] * 10; ?> Points
                                <?php else: ?>
                                    Need <?php echo $promo['discountRate'] * 10; ?> Points
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Redeemed Items Section -->
        <div class="section-card">
            <h5 class="card-title mb-3"><i class="bi bi-ticket-perforated"></i> My Redeemed Vouchers</h5>
            <?php if (!empty($redeemedVouchers)): ?>
                <div class="row">
                    <?php foreach ($redeemedVouchers as $voucher): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">Discount Voucher</h6>
                                    <p class="card-text">
                                        <span class="badge bg-success"><?php echo $voucher['discountRate']; ?>% OFF</span>
                                        <br>
                                        <small class="text-muted">
                                            Redeemed on: <?php echo date('d M Y', strtotime($voucher['redemptionDate'])); ?>
                                            <br>
                                            Expires: <?php echo date('d M Y', strtotime($voucher['expireDate'])); ?>
                                        </small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">No redeemed vouchers yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
