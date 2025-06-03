<?php
/**
 * Class for handling promotions and vouchers.
 */
class Promotion {
    public $promotionID;
    public $adminID;
    public $discountRate;
    public $startDate;
    public $expireDate;
    public $promotionQuantity;
    public $promotionType;
    public $redeemedBy;
    private $db;

    /**
     * Constructor. Initializes the database connection.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Creates a new promotion (implementation needed).
     */
    public function createPromotion() {}

    /**
     * Deletes a promotion (implementation needed).
     */
    public function deletePromotion() {}

    /**
     * Updates a promotion (implementation needed).
     */
    public function updatePromotion() {}

    /**
     * Applies a promotion to a sale or booking (implementation needed).
     */
    public function applyPromotion() {}

    /**
     * Validates a promotion code or details (implementation needed).
     */
    public function validatePromotion() {}

    /**
     * Tracks the usage count of a promotion (implementation needed).
     */
    public function trackUsageCount() {}

    /**
     * Displays the list of promotions (implementation needed).
     */
    public function displayPromotionList() {}

    /**
     * Generates a promotion voucher (implementation needed).
     */
    public function generatePromotionVoucher() {}

    /**
     * Gets all promotions of type 'Promotion'.
     * @return array
     */
    public function getAllPromotions() {
        try {
            $sql = "SELECT * FROM promotion WHERE promotionType = 'Promotion'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllPromotions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gets available promotions, optionally filtered by type.
     * @param string|null $type
     * @return array
     */
    public function getAvailablePromotions($type = null) {
        try {
            $sql = "SELECT * FROM promotion 
                    WHERE expireDate >= CURDATE() 
                    AND promotionQuantity > 0";
            
            if ($type) {
                $sql .= " AND promotionType = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            
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

    /**
     * Gets redeemed promotions for an account.
     * @param int $accountID
     * @param string|null $type
     * @return array
     */
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
            
            $stmt = $this->db->prepare($sql);
            
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

    /**
     * Redeems a promotion for a user.
     * @param int $promotionID
     * @param int $accountID
     * @return bool
     */
    public function redeemPromotion($promotionID, $accountID) {
        try {
            $this->db->beginTransaction();
            
            // Check if promotion is available
            $sql = "SELECT * FROM promotion 
                    WHERE promotionID = ? 
                    AND promotionType = 'Voucher'
                    AND promotionQuantity > 0 
                    FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$promotionID]);
            $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$promotion) {
                throw new Exception("Promotion not available");
            }
            
            // Update promotion quantity
            $sql = "UPDATE promotion 
                    SET promotionQuantity = promotionQuantity - 1 
                    WHERE promotionID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$promotionID]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in redeemPromotion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets promotion details by ID.
     * @param int $promotionID
     * @return array|false
     */
    public function getPromotionByID($promotionID) {
        try {
            $sql = "SELECT * FROM promotion WHERE promotionID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$promotionID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPromotionByID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrements the quantity of a promotion.
     * @param int $promotionID
     */
    public function decrementPromotionQuantity($promotionID) {
        $stmt = $this->db->prepare("UPDATE promotion SET promotionQuantity = promotionQuantity - 1 WHERE promotionID = ? AND promotionQuantity > 0");
        $stmt->execute([$promotionID]);
    }

    /**
     * Updates the quantity of a promotion.
     * @param int $promotionID
     * @return bool
     */
    public function updatePromotionQuantity($promotionID) {
        try {
            $sql = "UPDATE promotion 
                    SET promotionQuantity = promotionQuantity - 1 
                    WHERE promotionID = ? AND promotionQuantity > 0";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$promotionID]);
        } catch (PDOException $e) {
            error_log("Error in updatePromotionQuantity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets redeemed vouchers for an account.
     * @param int $accountID
     * @return array
     */
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
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$accountID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRedeemedVouchers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Processes the redemption of a promotion using points.
     * @param int $accountID
     * @param int $promotionID
     * @param int $pointsCost
     * @param object $point
     * @param object $pointRedemption
     * @return bool
     */
    public function processRedemption($accountID, $promotionID, $pointsCost, $point, $pointRedemption) {
        try {
            $this->db->beginTransaction();
            
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

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in processRedemption: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Decreases the quantity of a promotion.
     * @param int $promotionID
     * @return bool
     */
    public function decreaseQuantity($promotionID) {
        $sql = "UPDATE promotion SET promotionQuantity = promotionQuantity - 1 WHERE promotionID = ? AND promotionQuantity > 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$promotionID]);
    }

    /**
     * Gets the quantity of a promotion.
     * @param int $promotionID
     * @return int
     */
    public function getPromotionQuantity($promotionID) {
        $sql = "SELECT promotionQuantity FROM promotion WHERE promotionID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$promotionID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['promotionQuantity'] : 0;
    }
}
?>
