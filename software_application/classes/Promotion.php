<?php
class Promotion {
    public $promotionID;
    public $adminID;
    public $discountRate;
    public $startDate;
    public $expireDate;
    public $promotionQuantity;
    public $promotionType;
    public $redeemedBy;
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createPromotion() {}
    public function deletePromotion() {}
    public function updatePromotion() {}
    public function applyPromotion() {}
    public function validatePromotion() {}
    public function trackUsageCount() {}
    public function displayPromotionList() {}
    public function generatePromotionVoucher() {}

    public function getAllPromotions() {
        try {
            $sql = "SELECT * FROM promotion WHERE promotionType = 'Promotion'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllPromotions: " . $e->getMessage());
            return [];
        }
    }

    public function getAvailablePromotions($type = null) {
        try {
            $sql = "SELECT * FROM promotion 
                    WHERE expireDate >= CURDATE() 
                    AND promotionQuantity > 0";
            
            if ($type) {
                $sql .= " AND promotionType = ?";
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if ($type) {
                $stmt->execute([$type]);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAvailablePromotions: " . $e->getMessage());
            return [];
        }
    }

    public function getRedeemedPromotionsByAccountID($accountID, $type = null) {
        try {
            $sql = "SELECT p.*, s.saleDate as redeemDate 
                    FROM promotion p 
                    JOIN sale s ON p.promotionID = s.promotionID 
                    WHERE s.accountID = ?";
            
            if ($type) {
                $sql .= " AND p.promotionType = ?";
            }
            
            $sql .= " ORDER BY s.saleDate DESC";
            
            $stmt = $this->conn->prepare($sql);
            
            if ($type) {
                $stmt->execute([$accountID, $type]);
            } else {
                $stmt->execute([$accountID]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRedeemedPromotionsByAccountID: " . $e->getMessage());
            return [];
        }
    }

    public function redeemPromotion($promotionID, $accountID) {
        try {
            $this->conn->beginTransaction();
            
            // Check if promotion is available
            $sql = "SELECT * FROM promotion 
                    WHERE promotionID = ? 
                    AND promotionType = 'Voucher'
                    AND promotionQuantity > 0 
                    FOR UPDATE";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$promotionID]);
            $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$promotion) {
                throw new Exception("Promotion not available");
            }
            
            // Update promotion quantity
            $sql = "UPDATE promotion 
                    SET promotionQuantity = promotionQuantity - 1 
                    WHERE promotionID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$promotionID]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in redeemPromotion: " . $e->getMessage());
            return false;
        }
    }

    public function getPromotionByID($promotionID) {
        try {
            $sql = "SELECT * FROM promotion WHERE promotionID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$promotionID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPromotionByID: " . $e->getMessage());
            return false;
        }
    }

    public function decrementPromotionQuantity($promotionID) {
        $stmt = $this->conn->prepare("UPDATE promotion SET promotionQuantity = promotionQuantity - 1 WHERE promotionID = ? AND promotionQuantity > 0");
        $stmt->execute([$promotionID]);
    }

    public function updatePromotionQuantity($promotionID) {
        try {
            $sql = "UPDATE promotion 
                    SET promotionQuantity = promotionQuantity - 1 
                    WHERE promotionID = ? AND promotionQuantity > 0";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$promotionID]);
        } catch (PDOException $e) {
            error_log("Error in updatePromotionQuantity: " . $e->getMessage());
            return false;
        }
    }

    public function getRedeemedVouchers($accountID) {
        try {
            $sql = "SELECT pr.*, p.discountRate, p.expireDate
                    FROM point_redemption pr
                    JOIN promotion p ON pr.itemID = p.promotionID
                    WHERE pr.accountID = ? 
                    AND pr.itemType = 'Voucher'
                    AND p.expireDate >= CURDATE()
                    AND pr.isUsed = 0
                    ORDER BY pr.redemptionDate DESC, pr.redemptionTime DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$accountID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRedeemedVouchers: " . $e->getMessage());
            return [];
        }
    }

    public function processRedemption($accountID, $promotionID, $pointsCost, $point, $pointRedemption) {
        try {
            $this->conn->beginTransaction();
            
            // 1. Get promotion details
            $promotionDetails = $this->getPromotionByID($promotionID);
            if (!$promotionDetails || $promotionDetails['promotionType'] !== 'Voucher') {
                throw new Exception("Invalid promotion or promotion type");
            }

            // 2. Update point balance
            $pointInfo = $point->getPointByAccountID($accountID);
            if ($pointInfo) {
                $newBalance = $pointInfo['pointBalance'] - $pointsCost;
                if (!$point->updatePointBalance($accountID, $newBalance, $pointInfo['totalPointEarned'])) {
                    throw new Exception("Failed to update point balance");
                }
            }

            // 3. Create redemption record
            if (!$pointRedemption->createRedemption(
                $accountID,
                $promotionID,
                'Voucher',
                $pointsCost,
                1,
                date('Y-m-d'),
                date('H:i:s')
            )) {
                throw new Exception("Failed to create redemption record");
            }

            // 4. Update promotion quantity
            if (!$this->updatePromotionQuantity($promotionID)) {
                throw new Exception("Failed to update promotion quantity");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in processRedemption: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
