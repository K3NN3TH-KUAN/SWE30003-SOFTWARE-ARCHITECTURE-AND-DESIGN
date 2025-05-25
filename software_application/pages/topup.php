<?php
session_start();
if (isset($_GET['from_checkout'])) {
    if (isset($_GET['trip_id'])) {
        $_SESSION['checkout_trip_id'] = $_GET['trip_id'];
    }
    if (isset($_GET['promotion_id'])) {
        $_SESSION['checkout_promotion_id'] = $_GET['promotion_id'];
    }
}
require_once '../classes/Account.php';
require_once '../classes/TopUp.php';
require_once '../classes/Notification.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$account = new Account();
$topup = new TopUp();
$notification = new Notification();
$accountID = $_SESSION['accountID'];
$info = $account->getAccountByID($accountID);
$currentBalance = isset($info['accountBalance']) ? $info['accountBalance'] : 0;
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $topUpType = $_POST['topUpType'];

    // Validate payment details
    if ($amount <= 0) {
        $error = "Please enter a valid top-up amount.";
    } elseif (!$topup->validatePaymentDetails($topUpType)) {
        $error = "Please select a valid payment method.";
    } else {
        // Simulate payment gateway success/failure (always success here, but you can randomize for demo)
        $paymentSuccess = true; // Set to false to simulate failure

        if ($paymentSuccess) {
            $newBalance = $currentBalance + $amount;
            $recorded = $topup->recordTopUp($accountID, $amount, $topUpType, 'completed');
            if ($recorded && $topup->updateAccountBalance($accountID, $newBalance)) {
                $success = "Top-up successful! Your new balance is RM" . number_format($newBalance, 2);
                $currentBalance = $newBalance;
                $notification->createNotification($accountID, "Top-up of RM" . number_format($amount, 2) . " via $topUpType was successful.", 'payment');
            } else {
                $error = "Failed to top up. Please try again.";
                $topup->recordTopUp($accountID, $amount, $topUpType, 'failed');
            }
        } else {
            // Payment failed, do not update balance
            $error = "Payment failed. Please check your payment details and try again.";
            $topup->recordTopUp($accountID, $amount, $topUpType, 'failed');
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Top Up Balance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .topup-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-top: 3rem;
            padding: 2rem 2rem 1.5rem 2rem;
            background: #fff;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 2.5rem;
            margin: 0 auto 1rem auto;
            background: linear-gradient(135deg, #22d3ee 0%, #4ade80 100%);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-md-6">
            <div class="topup-card">
                <div class="text-center mb-4">
                    <div class="icon-circle">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Top Up Balance</h2>
                    <p class="text-muted mb-0">Add funds to your ART account for seamless travel and shopping.</p>
                </div>
                <form method="post" class="mb-3">
                    <div class="mb-3">
                        <label for="currentBalance" class="form-label">Current Balance</label>
                        <input type="text" class="form-control" id="currentBalance" value="RM<?php echo number_format($currentBalance, 2); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Top Up Amount (RM)</label>
                        <input type="number" min="1" step="0.01" class="form-control" id="amount" name="amount" placeholder="Enter amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="topUpType" class="form-label">Payment Method</label>
                        <select class="form-select" id="topUpType" name="topUpType" required>
                            <option value="" disabled selected>Select payment method</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="FPX">Online Banking (FPX)</option>
                            <option value="Touch n Go">E-Wallet (Touch n Go)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Top Up Now
                    </button>
                </form>
                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
                    <a href="cart.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cart3"></i> View Cart</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Pop-up message -->
    <?php if ($success || $error): ?>
    <div class="modal fade show" id="topupModal" tabindex="-1" style="display:block; background:rgba(0,0,0,0.3);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header <?php echo $success ? 'bg-success' : 'bg-danger'; ?>">
                    <h5 class="modal-title text-white"><?php echo $success ? 'Success' : 'Error'; ?></h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeModal()" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo $success ? $success : $error; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn <?php echo $success ? 'btn-success' : 'btn-danger'; ?>" onclick="closeModal()">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function closeModal() {
            document.getElementById('topupModal').style.display = 'none';
            window.location.href = 'topup.php';
        }
    </script>
    <?php endif; ?>
    <?php if (isset($_SESSION['checkout_trip_id'])): ?>
        <a href="checkout_trip.php"
           onclick="event.preventDefault(); document.getElementById('backToCheckoutForm').submit();"
           class="btn btn-outline-primary mt-3">
            <i class="bi bi-arrow-left"></i> Back to Checkout
        </a>
        <form id="backToCheckoutForm" method="post" action="checkout_trip.php" style="display:none;">
            <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($_SESSION['checkout_trip_id']); ?>">
            <?php if (isset($_SESSION['checkout_promotion_id'])): ?>
                <input type="hidden" name="promotion_id" value="<?php echo htmlspecialchars($_SESSION['checkout_promotion_id']); ?>">
            <?php endif; ?>
        </form>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
