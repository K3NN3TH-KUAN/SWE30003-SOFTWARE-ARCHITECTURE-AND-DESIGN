<?php
session_start();
$message = isset($_SESSION['payment_message']) ? $_SESSION['payment_message'] : '';
$type = isset($_SESSION['payment_type']) ? $_SESSION['payment_type'] : 'info';
$saleID = isset($_SESSION['last_sale_id']) ? $_SESSION['last_sale_id'] : null;
$redirect = isset($_SESSION['payment_redirect']) ? $_SESSION['payment_redirect'] : 'dashboard.php';

// Clear the session messages after displaying, but keep last_sale_id for the receipt link
unset($_SESSION['payment_message'], $_SESSION['payment_type'], $_SESSION['payment_redirect']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($type !== 'success'): ?>
    <script>
        setTimeout(function() {
            window.location.href = "<?php echo $redirect; ?>";
        }, 2000);
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="alert alert-<?php echo $type === 'success' ? 'success' : 'danger'; ?> text-center fs-4" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php if ($type === 'success' && $saleID): ?>
                    <div class="text-center mt-3">
                        <a href="receipt.php?saleID=<?php echo urlencode($saleID); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-receipt"></i> View Receipt
                        </a>
                    </div>
                <?php endif; ?>
                <div class="text-center text-muted mt-3">
                    <?php if ($type !== 'success'): ?>
                        Redirecting in 2 seconds...
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 