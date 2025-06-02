<?php
session_start();
require_once '../classes/Point.php';
require_once '../classes/Account.php';
require_once '../classes/Promotion.php';
require_once '../classes/PointRedemption.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$point = new Point();
$account = new Account();
$promotion = new Promotion($db);
$pointRedemption = new PointRedemption();

$accountID = $_SESSION['accountID'];
$pointInfo = $point->getPointByAccountID($accountID);
$accountInfo = $account->getAccountByID($accountID);
$availableItems = $pointRedemption->getAvailableRedemptionItems();
$redeemedItems = $pointRedemption->getRedemptionsByAccountID($accountID);

// Fetch purchase history (receipts)
$purchaseHistory = [];
$stmt = $db->prepare("SELECT saleID, saleDate, saleTime, totalAmountPay FROM sale WHERE accountID = ? ORDER BY saleDate DESC, saleTime DESC");
$stmt->execute([$accountID]);
$purchaseHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
            position: relative;
        }
        .points-balance {
            background: linear-gradient(135deg, #3b5bdb 0%, #4c6ef5 100%);
            color: white;
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(59, 91, 219, 0.18);
            position: relative;
            overflow: hidden;
        }
        .points-balance .bi-star-fill {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: #ffe066;
            filter: drop-shadow(0 2px 6px #fff3);
        }
        .points-animated {
            font-size: 2.5rem;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        .points-badge {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            font-weight: bold;
            backdrop-filter: blur(5px);
            font-size: 1.2rem;
        }
        .reward-card {
            background: linear-gradient(135deg, #f0f4ff 0%, #e9ecef 100%);
            border: none;
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.3s, transform 0.2s;
            box-shadow: 0 2px 10px rgba(59,91,219,0.08);
            position: relative;
            overflow: hidden;
        }
        .reward-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 8px 24px rgba(59,91,219,0.18);
        }
        .reward-header {
            background: #e7f0ff;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .discount-badge {
            background: linear-gradient(90deg, #3b5bdb 0%, #4c6ef5 100%);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 1rem;
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px #3b5bdb22;
        }
        .btn-redeem {
            background: linear-gradient(90deg, #3b5bdb 0%, #4c6ef5 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 1rem;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 2px 8px #3b5bdb22;
        }
        .btn-redeem:hover {
            background: linear-gradient(90deg, #4c6ef5 0%, #3b5bdb 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .btn-redeem:disabled {
            background: #e9ecef;
            color: #6c757d;
        }
        .history-section-card {
            background: linear-gradient(135deg, #3b5bdb 0%, #4c6ef5 100%);
            border-radius: 1.5rem;
            color: #fff;
            box-shadow: 0 4px 15px rgba(59, 91, 219, 0.12);
            max-height: 340px;
            overflow-y: auto;
            scrollbar-width: none;
            padding: 1.5rem 1.2rem 1.2rem 1.2rem;
        }
        .history-section-card::-webkit-scrollbar { display: none; }
        .purchase-history-card {
            background: rgba(255,255,255,0.13);
            border-radius: 1rem;
            margin-bottom: 1.2rem;
            box-shadow: 0 2px 8px rgba(59,91,219,0.10);
            padding: 1.2rem 1.5rem;
            transition: box-shadow 0.2s, transform 0.2s;
            border: none;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            position: relative;
        }
        .purchase-history-card:hover {
            box-shadow: 0 8px 24px rgba(59,91,219,0.18);
            transform: translateY(-3px) scale(1.01);
            background: rgba(255,255,255,0.18);
        }
        .purchase-history-date {
            font-size: 1rem;
            opacity: 0.9;
        }
        .purchase-history-points {
            background: linear-gradient(90deg, #22d3ee 0%, #4ade80 100%);
            color: #fff;
            border-radius: 2rem;
            font-weight: bold;
            font-size: 1.1rem;
            padding: 0.4rem 1.2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-left: auto;
            box-shadow: 0 2px 8px #22d3ee22;
        }
        .purchase-history-paid {
            font-size: 1.1rem;
            font-weight: bold;
            color: #a7ffb0;
        }
        .section-title {
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            letter-spacing: 0.5px;
        }
        .card-title {
            font-weight: 600;
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
        @media (max-width: 991.98px) {
            .points-balance, .history-section-card {
                margin-bottom: 1.5rem;
            }
        }
        @media (max-width: 767.98px) {
            .points-balance, .history-section-card {
                max-height: none !important;
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5 position-relative">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-4 fw-bold"><i class="bi bi-gift"></i> Points & Rewards</h2> 
            <a href="dashboard.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success text-center"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row mb-4 g-3">
            <!-- Points Balance (Left) -->
            <div class="col-md-5">
                <div class="points-balance text-center h-100 d-flex flex-column justify-content-center align-items-center">
                    <i class="bi bi-star-fill mb-2"></i>
                    <div class="points-animated" id="pointsCounter"><?php echo number_format($pointInfo ? $pointInfo['pointBalance'] : 0); ?></div>
                    <div class="points-badge mb-2">
                        <i class="bi bi-coin me-2"></i>
                        Points Balance
                    </div>
                    <p class="mt-3 mb-0" style="opacity:0.85;">Earn 1 point for every RM1 spent on trips or merchandise</p>
                </div>
            </div>
            
            <!-- Earn Point History (Right) -->
            <div class="col-md-7">
                <div class="history-section-card h-100">
                    <div class="section-title mb-3"><i class="bi bi-receipt"></i> Purchase History & Points Earned</div>
                    <?php if (!empty($purchaseHistory)): ?>
                        <?php foreach ($purchaseHistory as $purchase): ?>
                            <div class="purchase-history-card">
                                <div>
                                    <div class="purchase-history-date mb-1">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo htmlspecialchars($purchase['saleDate']); ?>
                                        <i class="bi bi-clock ms-2"></i>
                                        <?php echo htmlspecialchars($purchase['saleTime']); ?>
                                    </div>
                                    <div>
                                        <span class="me-2">Total Paid:</span>
                                        <span class="purchase-history-paid">RM<?php echo number_format($purchase['totalAmountPay'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="purchase-history-points ms-md-auto mt-2 mt-md-0">
                                    <i class="bi bi-star-fill"></i>
                                    <?php echo floor($purchase['totalAmountPay']); ?> pts
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-light">No purchase history found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Available Rewards Section -->
        <h4 class="mb-4 text-center"><i class="bi bi-ticket-perforated"></i> Available Vouchers</h4>
        <div class="row">
            <?php foreach ($availablePromotions as $promo): ?>
                <div class="col-md-6">
                    <div class="reward-card">
                        <div class="reward-header">
                            <h5 class="mb-0">Discount Voucher</h5>
                            <span class="discount-badge"><?php echo $promo['discountRate']; ?>% OFF</span>
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
        <div class="reward-card mt-4">
            <h5 class="card-title mb-3"><i class="bi bi-ticket-perforated"></i> My Redeemed Vouchers</h5>
            <?php if (!empty($redeemedVouchers)): ?>
                <div class="row">
                    <?php foreach ($redeemedVouchers as $voucher): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm" style="border-radius:1rem;">
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
    <script>
        // Animated points counter
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('pointsCounter');
            var target = parseInt(el.textContent.replace(/,/g, '')) || 0;
            var current = 0;
            var step = Math.max(1, Math.floor(target / 40));
            if (target > 0) {
                var interval = setInterval(function() {
                    current += step;
                    if (current >= target) {
                        current = target;
                        clearInterval(interval);
                    }
                    el.textContent = current.toLocaleString();
                }, 18);
            }
        });
    </script>
</body>
</html>
