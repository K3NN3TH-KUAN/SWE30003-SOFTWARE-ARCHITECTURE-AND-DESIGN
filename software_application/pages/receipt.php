<?php
session_start();
require_once '../classes/Sale.php';

if (!isset($_GET['saleID'])) {
    echo "<div class='alert alert-danger'>No sale ID provided.</div>";
    exit();
}

$saleID = $_GET['saleID'];
$sale = new Sale();
$receiptHtml = $sale->generateReceipt($saleID);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?php echo htmlspecialchars($saleID); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <?php echo $receiptHtml; ?>
        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-primary"><i class="bi bi-house"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html> 