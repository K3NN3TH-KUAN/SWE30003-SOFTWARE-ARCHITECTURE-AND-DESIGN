<?php
session_start();
require_once '../classes/Account.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$account = new Account();
$accountID = $_SESSION['accountID'];
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
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
            </div>
            <div class="col-lg-4">
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
                        <div class="d-flex justify-content-between summary-row p-2 rounded mb-2">
                            <span class="fw-bold">Total to Pay:</span>
                            <span class="fw-bold text-success">RM<?php echo number_format($total, 2); ?></span>
                        </div>
                        <form action="process_payment.php" method="post" class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-success btn-lg fw-bold">
                                <i class="bi bi-check-circle"></i> Confirm Payment
                            </button>
                        </form>
                        <a href="cart.php" class="btn btn-outline-primary mt-2"><i class="bi bi-arrow-left"></i> Back to Cart</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 