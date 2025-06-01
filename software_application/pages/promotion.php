<?php
session_start();
require_once '../classes/Promotion.php';
require_once '../classes/Account.php';
require_once '../classes/Point.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$promotion = new Promotion();
$account = new Account();
$point = new Point();

$accountID = $_SESSION['accountID'];
$accountInfo = $account->getAccountByID($accountID);

// Get available promotions (only Promotion type)
$availablePromotions = $promotion->getAvailablePromotions('Promotion');

// Get redeemed vouchers
$redeemedVouchers = $promotion->getRedeemedVouchers($accountID);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Promotions - Kuching ART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .promo-card {
            background: white;
            border: none;
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .promo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .promo-header {
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-4 fw-bold"><i class="bi bi-tag"></i> Promotions & Vouchers</h2>
            <a href="dashboard.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>

        <!-- Available Promotions Section -->
        <h4 class="mb-4 text-center">Available Promotions</h4>
        <div class="row">
            <?php foreach ($availablePromotions as $promo): ?>
                <div class="col-md-6">
                    <div class="promo-card">
                        <div class="promo-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Discount Promotion</h5>
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
                                Available: <?php echo $promo['promotionQuantity']; ?> promotions
                            </p>
                            <p class="text-muted mb-0">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Use on any trip booking or merchandise purchasing
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Redeemed Vouchers Section -->
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