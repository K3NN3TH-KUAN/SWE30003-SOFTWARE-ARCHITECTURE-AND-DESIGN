<?php
session_start();
require_once '../classes/Account.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle item removal FIRST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $index = $_POST['index'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
    }
    header('Location: cart.php');
    exit();
}

// Handle quantity updates with plus/minus (only if not removing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['remove_item'])) {
    $index = $_POST['index'];
    if (isset($_POST['increase_quantity']) && isset($_SESSION['cart'][$index])) {
        $_SESSION['cart'][$index]['quantity']++;
    } elseif (isset($_POST['decrease_quantity']) && isset($_SESSION['cart'][$index])) {
        if ($_SESSION['cart'][$index]['quantity'] > 1) {
            $_SESSION['cart'][$index]['quantity']--;
        }
    }
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
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .cart-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-bottom: 1.5rem;
        }
        .cart-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
            background: #f3f4f6;
        }
        .cart-action-btn {
            min-width: 90px;
        }
        .cart-total-row {
            background: #f3f4f6;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-cart3"></i> Shopping Cart</h2>
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="merchandise.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                        <div class="card cart-card mb-3">
                            <div class="card-body d-flex align-items-center">
                                <div class="me-3">
                                <img src="../assets/images/merchandise/<?php echo !empty($item['merchandiseImage']) ? htmlspecialchars($item['merchandiseImage']) : 'placeholder.png'; ?>" class="cart-img" alt="<?php echo htmlspecialchars($item['merchandiseName']); ?>">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($item['merchandiseName']); ?></h5>
                                    <div class="text-muted mb-2">RM<?php echo number_format($item['merchandisePrice'], 2); ?></div>
                                    <form action="cart.php" method="POST" class="d-flex align-items-center quantity-form" style="max-width: 180px;">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <button type="submit" name="decrease_quantity" class="btn btn-outline-secondary btn-sm" <?php if ($item['quantity'] <= 1) echo 'disabled'; ?>><i class="bi bi-dash"></i></button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="form-control form-control-sm mx-2 text-center" style="width: 60px;" readonly>
                                        <button type="submit" name="increase_quantity" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus"></i></button>
                                    </form>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold mb-2">Subtotal: RM<?php echo number_format($item['merchandisePrice'] * $item['quantity'], 2); ?></div>
                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="remove_item" value="1">
                                        <button type="submit" class="btn btn-outline-danger btn-sm cart-action-btn"><i class="bi bi-trash"></i> Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-4">
                    <div class="card cart-card">
                        <div class="card-body">
                            <h5 class="mb-3"><i class="bi bi-receipt"></i> Order Summary</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Items:</span>
                                <span><?php echo count($_SESSION['cart']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between cart-total-row p-2 rounded">
                                <span class="fw-bold">Total:</span>
                                <span class="fw-bold text-success">RM<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <a href="checkout.php" class="btn btn-success btn-lg"><i class="bi bi-credit-card"></i> Proceed to Checkout</a>
                                <a href="merchandise.php" class="btn btn-outline-primary"><i class="bi bi-bag"></i> Continue Shopping</a>
                                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Back to Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 