<?php
/**
 * Class for handling sales transactions.
 */
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

    /**
     * Initiates a new sale with the provided details.
     * @param int $accountID
     * @param float $lineOfSaleAmount
     * @param int $lineOfSaleQuantity
     * @param float $totalAmountPay
     * @param int|null $promotionID
     * @param int|null $redemptionID
     * @return int|false Sale ID on success, false on failure.
     */
    public function initiateNewSale($accountID, $lineOfSaleAmount, $lineOfSaleQuantity, $totalAmountPay, $promotionID = null, $redemptionID = null) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Debug log
            error_log("Creating sale with params: " . print_r([
                'accountID' => $accountID,
                'lineOfSaleAmount' => $lineOfSaleAmount,
                'lineOfSaleQuantity' => $lineOfSaleQuantity,
                'totalAmountPay' => $totalAmountPay,
                'promotionID' => $promotionID,
                'redemptionID' => $redemptionID
            ], true));

            $sql = "INSERT INTO sale (accountID, promotionID, redemptionID, saleDate, saleTime, lineOfSaleQuantity, lineOfSaleAmount, totalAmountPay, saleStatus) 
                    VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, 'Completed')";
            
            $stmt = $db->prepare($sql);
            
            // Convert null values to actual NULL for the database
            $promotionID = $promotionID === '' ? null : $promotionID;
            $redemptionID = $redemptionID === '' ? null : $redemptionID;
            
            $result = $stmt->execute([
                $accountID,
                $promotionID,
                $redemptionID,
                $lineOfSaleQuantity,
                $lineOfSaleAmount,
                $totalAmountPay
            ]);
            
            if ($result) {
                $saleID = $db->lastInsertId();
                error_log("Sale created successfully with ID: " . $saleID);
                return $saleID;
            }
            
            error_log("Failed to create sale: " . print_r($stmt->errorInfo(), true));
            return false;
            
        } catch (PDOException $e) {
            error_log("Error in initiateNewSale: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generates a receipt for a sale.
     * @param int $saleID
     * @return string HTML receipt
     */
    public function generateReceipt($saleID) {
        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/LineOfSale.php';
        require_once __DIR__ . '/Account.php';
        require_once __DIR__ . '/Promotion.php';
        $database = new Database();
        $db = $database->getConnection();

        // Get sale details with promotion info
        $sql = "SELECT s.*, a.accountName, a.email, 
                p.discountRate as promotion_discount_rate,
                pr.redemptionID
                FROM sale s 
                JOIN account a ON s.accountID = a.accountID 
                LEFT JOIN promotion p ON s.promotionID = p.promotionID
                LEFT JOIN point_redemption pr ON s.redemptionID = pr.redemptionID
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

        // Calculate discount information
        $originalTotal = $sale['lineOfSaleAmount'];
        $discountRate = $sale['promotion_discount_rate'] ?? 0;
        $discountAmount = $originalTotal - $sale['totalAmountPay'];

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
                        <td style="padding:8px;">RM<?php echo number_format($item['itemAmount'], 2); ?></td>
                        <td style="padding:8px;">RM<?php echo number_format($item['totalAmountPerLineOfSale'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <div style="text-align:right;">
                <?php if ($discountRate > 0): ?>
                    <div style="margin-bottom:8px;">
                        <strong>Subtotal:</strong> RM<?php echo number_format($originalTotal, 2); ?><br>
                        <strong style="color:#16a34a;">Discount (<?php echo $discountRate; ?>%):</strong> -RM<?php echo number_format($discountAmount, 2); ?>
                    </div>
                <?php endif; ?>
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
    /**
     * Shows the total amount for the sale (implementation needed).
     */
    public function showTotalAmount() {}
    /**
     * Activates a promotion for the sale (implementation needed).
     */
    public function activatePromotion() {}
    /**
     * Checks out a promotion for the sale (implementation needed).
     */
    public function checkoutPromotion() {}
    /**
     * Earns reward points for the sale (implementation needed).
     */
    public function earnRewardPoint() {}
    /**
     * Handles line of sale items for the sale (implementation needed).
     */
    public function handleLineOfSale() {}

    /**
     * Updates the status of a sale.
     * @param int $saleID
     * @param string $status
     * @return bool
     */
    public function updateSaleStatus($saleID, $status) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE sale SET saleStatus = ? WHERE saleID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$status, $saleID]);
    }

    /**
     * Deletes a sale by its ID.
     * @param int $saleID
     * @return bool
     */
    public function deleteSale($saleID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "DELETE FROM sale WHERE saleID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$saleID]);
    }

    /**
     * Gets sale details by sale ID.
     * @param int $saleID
     * @return array|false
     */
    public function getSaleByID($saleID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM sale WHERE saleID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$saleID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all sales for a specific account.
     * @param int $accountID
     * @return array
     */
    public function getSalesByAccountID($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM sale WHERE accountID = ? ORDER BY saleDate DESC, saleTime DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Emails a receipt for a sale to the specified email address.
     * @param int $saleID
     * @param string $toEmail
     * @return bool
     */
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
