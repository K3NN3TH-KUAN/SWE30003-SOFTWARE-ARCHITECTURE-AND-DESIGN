<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Promotion.php';

/**
 * Class for handling point redemption logic.
 */
class PointRedemption {
    private $conn;

    /**
     * Constructor. Initializes the database connection.
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Creates a new point redemption record.
     * @param int $accountID
     * @param int $itemID
     * @param string $itemType
     * @param int $pointsCost
     * @param int $quantity
     * @param string $redemptionDate
     * @param string $redemptionTime
     * @return bool
     */
    public function createRedemption($accountID, $itemID, $itemType, $pointsCost, $quantity, $redemptionDate, $redemptionTime) {
        try {
            $sql = "INSERT INTO point_redemption (accountID, itemID, itemType, pointsCost, quantity, redemptionDate, redemptionTime) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $accountID,
                $itemID,
                $itemType,
                $pointsCost,
                $quantity,
                $redemptionDate,
                $redemptionTime
            ]);

            if (!$result) {
                error_log("Error creating redemption record: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error in createRedemption: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets all redemptions for an account.
     * @param int $accountID
     * @return array
     */
    public function getRedemptionsByAccountID($accountID) {
        try {
            $sql = "SELECT pr.*, p.discountRate, p.expireDate 
                    FROM point_redemption pr
                    JOIN promotion p ON pr.itemID = p.promotionID
                    WHERE pr.accountID = ? 
                    AND pr.itemType = 'Voucher'
                    ORDER BY pr.redemptionDate DESC, pr.redemptionTime DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$accountID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRedemptionsByAccountID: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gets available items for redemption (promotions and merchandise).
     * @return array
     */
    public function getAvailableRedemptionItems() {
        try {
            $items = [];
            
            // Get available promotions
            $sql = "SELECT promotionID as itemID, promotionName as itemName, 
                    'Voucher' as itemType, 
                    (discountRate * 100) as pointsCost,
                    promotionQuantity as availableQuantity
                    FROM promotion 
                    WHERE promotionType = 'Voucher' 
                    AND promotionQuantity > 0 
                    AND expireDate >= CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));

            // Get available merchandise
            $sql = "SELECT merchandiseID as itemID, merchandiseName as itemName, 
                    'Merchandise' as itemType, 
                    merchandisePrice as pointsCost,
                    stockQuantity as availableQuantity
                    FROM merchandise 
                    WHERE stockQuantity > 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $items = array_merge($items, $stmt->fetchAll(PDO::FETCH_ASSOC));

            return $items;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Checks if the user has enough points for redemption.
     * @param int $accountID
     * @param int $pointsCost
     * @return bool
     */
    public function canRedeem($accountID, $pointsCost) {
        try {
            $sql = "SELECT pointBalance FROM point WHERE accountID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$accountID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['pointBalance'] >= $pointsCost;
        } catch (PDOException $e) {
            error_log("Error in canRedeem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marks a redemption as used.
     * @param int $redemptionID
     * @return bool
     */
    public function markAsUsed($redemptionID) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE point_redemption SET isUsed = 1 WHERE redemptionID = ?");
        return $stmt->execute([$redemptionID]);
    }

    /**
     * Gets a redemption record by its ID.
     * @param int $redemptionID
     * @return array|false
     */
    public function getRedemptionByID($redemptionID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM point_redemption WHERE redemptionID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$redemptionID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 