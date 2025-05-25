<?php
class Sale {
    public $saleID;
    public $accountID;
    public $promotionID;
    public $saleDate;
    public $saleTime;
    public $lineOfSaleQuantity;
    public $lineOfSaleAmount;
    public $totalAmountPay;
    public $saleStatus;

    public function initiateNewSale($accountID, $totalAmount, $lineOfSaleQuantity, $lineOfSaleAmount, $promotionID = null, $redemptionID = null) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        try {
            $db->beginTransaction(); // Start transaction

            $sql = "INSERT INTO sale (accountID, promotionID, redemptionID, saleDate, saleTime, lineOfSaleQuantity, lineOfSaleAmount, totalAmountPay, saleStatus) 
                    VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, 'Completed')";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $accountID,
                $promotionID,
                $redemptionID,
                $lineOfSaleQuantity,
                $lineOfSaleAmount,
                $totalAmount
            ]);
            
            $saleID = $db->lastInsertId();
            
            $db->commit(); // Commit transaction
            return $saleID;
        } catch (PDOException $e) {
            $db->rollBack(); // Rollback on error
            error_log("Error in initiateNewSale: " . $e->getMessage());
            return false;
        }
    }
    
    public function generateReceipt($saleID) {
        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/LineOfSale.php';
        require_once __DIR__ . '/Account.php';
        $database = new Database();
        $db = $database->getConnection();

        // Get sale details
        $sql = "SELECT s.*, a.accountName, a.email 
                FROM sale s 
                JOIN account a ON s.accountID = a.accountID 
                WHERE s.saleID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$saleID]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sale) {
            return "<div class='alert alert-danger'>Sale not found.</div>";
        }

        // Get line of sale items
        $lineOfSale = new LineOfSale();
        $items = $lineOfSale->getLineOfSaleBySaleID($saleID);

        // Build receipt HTML
        ob_start();
        ?>
        <div style="max-width:600px;margin:auto;border:1px solid #eee;padding:24px;border-radius:10px;font-family:sans-serif;">
            <h2 style="text-align:center;color:#4f46e5;">Kuching ART - Invoice Receipt</h2>
            <hr>
            <div>
                <strong>Receipt #: </strong> <?php echo htmlspecialchars($sale['saleID']); ?><br>
                <strong>Date: </strong> <?php echo htmlspecialchars($sale['saleDate']); ?> <?php echo htmlspecialchars($sale['saleTime']); ?><br>
                <strong>Account: </strong> <?php echo htmlspecialchars($sale['accountName']); ?> (<?php echo htmlspecialchars($sale['email']); ?>)
            </div>
            <hr>
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="padding:8px;border-bottom:1px solid #ddd;">Item</th>
                        <th style="padding:8px;border-bottom:1px solid #ddd;">Type</th>
                        <th style="padding:8px;border-bottom:1px solid #ddd;">Qty</th>
                        <th style="padding:8px;border-bottom:1px solid #ddd;">Unit Price</th>
                        <th style="padding:8px;border-bottom:1px solid #ddd;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="padding:8px;"><?php
                            // Try to get merchandise name if type is Merchandise
                            if ($item['type'] === 'Merchandise' && !empty($item['merchandiseID'])) {
                                $merchandiseStmt = $db->prepare("SELECT merchandiseName FROM merchandise WHERE merchandiseID = ?");
                                $merchandiseStmt->execute([$item['merchandiseID']]);
                                $merch = $merchandiseStmt->fetch(PDO::FETCH_ASSOC);
                                echo htmlspecialchars($merch ? $merch['merchandiseName'] : 'Merchandise');
                            } else {
                                echo htmlspecialchars($item['type']);
                            }
                        ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($item['type']); ?></td>
                        <td style="padding:8px;text-align:center;"><?php echo htmlspecialchars($item['itemQuantity']); ?></td>
                        <td style="padding:8px;">
                            RM<?php echo number_format($item['itemAmount'], 2); ?>
                        </td>
                        <td style="padding:8px;">
                            RM<?php
                                // Use the calculateTotalAmount method
                                $subtotal = $lineOfSale->calculateTotalAmount($item['itemQuantity'], $item['itemAmount']);
                                echo number_format($subtotal, 2);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <div style="text-align:right;">
                <strong>Total Paid: RM<?php echo number_format($sale['totalAmountPay'], 2); ?></strong>
            </div>
            <div style="text-align:right;color:#16a34a;">
                <strong>Status: <?php echo htmlspecialchars($sale['saleStatus']); ?></strong>
            </div>
            <hr>
            <div style="text-align:center;color:#64748b;font-size:0.95em;">
                Thank you for your purchase with Kuching ART!
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    public function showTotalAmount() {}
    public function activatePromotion() {}
    public function checkoutPromotion() {}
    public function earnRewardPoint() {}
    public function handleLineOfSale() {}

    // Add a method to update sale status if needed
    public function updateSaleStatus($saleID, $status) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE sale SET saleStatus = ? WHERE saleID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$status, $saleID]);
    }

    public function deleteSale($saleID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "DELETE FROM sale WHERE saleID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$saleID]);
    }

    public function getSaleByID($saleID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM sale WHERE saleID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$saleID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSalesByAccountID($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM sale WHERE accountID = ? ORDER BY saleDate DESC, saleTime DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function emailReceipt($saleID, $toEmail) {
        $receiptHtml = $this->generateReceipt($saleID);
        $subject = "Your Kuching ART Purchase Receipt #$saleID";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@kuchingart.com\r\n";
        return mail($toEmail, $subject, $receiptHtml, $headers);
    }
}
?>
