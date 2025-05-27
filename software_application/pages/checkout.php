<?php
session_start();
require_once '../classes/Promotion.php';
require_once '../classes/PointRedemption.php';
require_once '../classes/Account.php';

$promotion = new Promotion();
$pointRedemption = new PointRedemption();
$account = new Account();

$accountID = $_SESSION['accountID'] ?? null;

// Fetch available promotions (not vouchers)
$availablePromotions = $promotion->getAvailablePromotions('Promotion');

// Fetch user's redeemed vouchers
$redeemedVouchers = [];
if ($accountID) {
    $redeemedVouchers = $pointRedemption->getRedemptionsByAccountID($accountID);
}

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$info = $account->getAccountByID($accountID);
$currentBalance = isset($info['accountBalance']) ? $info['accountBalance'] : 0;

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['merchandisePrice'] * $item['quantity'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPromotionID = $_POST['promotionID'] ?? null;
    $selectedRedemptionID = $_POST['redemptionID'] ?? null;

    // Debug log
    error_log("Form submitted with: " . print_r($_POST, true));

    // Store in session for payment processing
    $_SESSION['checkout_discount'] = [
        'promotionID' => $selectedPromotionID,
        'redemptionID' => $selectedRedemptionID
    ];

    // Debug log
    error_log("Stored in session: " . print_r($_SESSION['checkout_discount'], true));

    header('Location: process_payment.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Kuching ART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .checkout-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-bottom: 1.5rem;
        }
        .cart-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.5rem;
            background: #f3f4f6;
        }
        .summary-row {
            background: #f3f4f6;
            font-size: 1.1rem;
        }
        .promo-card {
            cursor: pointer;
            transition: box-shadow 0.2s, border-color 0.2s;
            border-width: 2px;
        }
        .promo-card.selected {
            box-shadow: 0 0 0 0.25rem #3b5bdb33;
            border-color: #3b5bdb !important;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h2>
        <div class="row">
            <div class="col-lg-8">
                <div class="card checkout-card mb-3">
                    <div class="card-body">
                        <h5 class="mb-3 fw-bold">Order Details</h5>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="d-flex align-items-center border-bottom py-2">
                                <img src="../assets/images/merchandise/<?php echo htmlspecialchars($item['merchandiseImage']); ?>" class="cart-img me-3" alt="<?php echo htmlspecialchars($item['merchandiseName']); ?>">
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($item['merchandiseName']); ?></div>
                                    <div class="text-muted small mb-1"><?php echo htmlspecialchars($item['merchandiseCategory']); ?></div>
                                    <div class="text-muted small">RM<?php echo number_format($item['merchandisePrice'], 2); ?> x <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="fw-bold text-end">
                                    RM<?php echo number_format($item['merchandisePrice'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Promotion/Voucher Card Selection -->
                <div class="card checkout-card mb-3">
                    <div class="card-body">
                        <h4 class="mb-3">Apply Promotion or Voucher (Optional)</h4>
                        <div class="row g-3" id="promoCardGroup">
                            <div class="col-md-6">
                                <div class="promo-card card border-primary h-100 selected" data-type="none" data-discount="0" data-label="No Discount">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-2"><i class="bi bi-x-circle"></i> No Discount</h5>
                                        <p class="card-text text-muted mb-0">Proceed without any discount.</p>
                                    </div>
                                </div>
                            </div>
                            <?php foreach ($availablePromotions as $promo): ?>
                            <div class="col-md-6">
                                <div class="promo-card card border-success h-100" 
                                     data-type="promotion" 
                                     data-id="<?php echo $promo['promotionID']; ?>"
                                     data-discount="<?php echo $promo['discountRate']; ?>"
                                     data-label="Promotion: <?php echo htmlspecialchars($promo['discountRate']); ?>% OFF">
                                    <div class="card-body">
                                        <h5 class="card-title text-success mb-2"><i class="bi bi-percent"></i> <?php echo htmlspecialchars($promo['discountRate']); ?>% OFF</h5>
                                        <p class="card-text mb-1">Promotion (Expires: <?php echo htmlspecialchars($promo['expireDate']); ?>)</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php foreach ($redeemedVouchers as $voucher): ?>
                                <?php if (empty($voucher['isUsed']) || $voucher['isUsed'] == 0): // Only show unused vouchers ?>
                                <div class="col-md-6">
                                    <div class="promo-card card border-warning h-100" 
                                         data-type="voucher" 
                                         data-id="<?php echo $voucher['redemptionID']; ?>"
                                         data-discount="<?php echo $voucher['discountRate']; ?>"
                                         data-label="Voucher: <?php echo htmlspecialchars($voucher['discountRate']); ?>% OFF">
                                        <div class="card-body">
                                            <h5 class="card-title text-warning mb-2"><i class="bi bi-ticket-perforated"></i> <?php echo htmlspecialchars($voucher['discountRate']); ?>% OFF</h5>
                                            <p class="card-text mb-1">Voucher (Redeemed: <?php echo htmlspecialchars($voucher['redemptionDate']); ?>)</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <form method="POST" action="">
                <div class="card checkout-card">
                    <div class="card-body">
                        <h5 class="mb-3 fw-bold"><i class="bi bi-person-circle"></i> Account Summary</h5>
                        <div class="mb-2">
                            <span class="text-muted">Account Name:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($info['accountName']); ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Current Balance:</span>
                            <span class="fw-bold text-success">RM<?php echo number_format($currentBalance, 2); ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Total Items:</span>
                            <span class="fw-bold"><?php echo count($_SESSION['cart']); ?></span>
                        </div>
                        <!-- Discount summary will be shown here -->
                        <div id="discountSummary" class="mb-2"></div>
                        <div class="d-flex justify-content-between summary-row p-2 rounded mb-2">
                            <span class="fw-bold">Total to Pay:</span>
                            <span class="fw-bold text-success" id="orderTotal">RM<?php echo number_format($total, 2); ?></span>
                        </div>
                        <!-- Add these hidden fields here -->
                        <input type="hidden" name="promotionID" id="promotionID" value="">
                        <input type="hidden" name="redemptionID" id="redemptionID" value="">
                        <button type="submit" class="btn btn-success btn-lg fw-bold w-100 mb-2">
                            <i class="bi bi-check-circle"></i> Confirm Payment
                        </button>
                        <a href="cart.php" class="btn btn-outline-primary w-100"><i class="bi bi-arrow-left"></i> Back to Cart</a>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var baseTotal = <?php echo json_encode($total); ?>;

        // Card selection logic
        function selectPromoCard(card) {
            document.querySelectorAll('.promo-card').forEach(function(c) {
                c.classList.remove('selected', 'border-3');
            });
            card.classList.add('selected', 'border-3');
            updateHiddenFields(card);
            updateDiscountSummary(card);
        }

        function updateHiddenFields(card) {
            var type = card.getAttribute('data-type');
            var id = card.getAttribute('data-id');
            
            // Clear both fields first
            document.getElementById('promotionID').value = '';
            document.getElementById('redemptionID').value = '';
            
            // Set the appropriate field based on type
            if (type === 'promotion') {
                document.getElementById('promotionID').value = id;
            } else if (type === 'voucher') {
                document.getElementById('redemptionID').value = id;
            }
            
            // Debug log
            console.log('Selected type:', type);
            console.log('Selected ID:', id);
            console.log('promotionID value:', document.getElementById('promotionID').value);
            console.log('redemptionID value:', document.getElementById('redemptionID').value);
        }

        function updateDiscountSummary(card) {
            var discount = 0;
            var label = '';
            if (card && card.getAttribute('data-type') !== 'none') {
                discount = parseFloat(card.getAttribute('data-discount')) || 0;
                label = card.getAttribute('data-label') || '';
            }
            var discountAmount = baseTotal * (discount / 100);
            var discountedTotal = baseTotal - discountAmount;

            var summaryHtml = '';
            if (discount > 0) {
                summaryHtml = `
                    <div class="alert alert-info mb-0">
                        <strong>Applied:</strong> ${label}<br>
                        <strong>Discount:</strong> -RM${discountAmount.toFixed(2)}<br>
                        <strong>Total after discount:</strong> <span class="text-success">RM${discountedTotal.toFixed(2)}</span>
                    </div>
                `;
            } else {
                summaryHtml = '';
            }
            document.getElementById('discountSummary').innerHTML = summaryHtml;
            document.getElementById('orderTotal').textContent = 'RM' + discountedTotal.toFixed(2);
        }

        // Set up card click events
        document.querySelectorAll('.promo-card').forEach(function(card) {
            card.addEventListener('click', function() {
                selectPromoCard(card);
            });
        });

        // On page load, select the first card (No Discount)
        var firstCard = document.querySelector('.promo-card.selected') || document.querySelector('.promo-card');
        if (firstCard) {
            selectPromoCard(firstCard);
        }
    });
    </script>
</body>
</html> 