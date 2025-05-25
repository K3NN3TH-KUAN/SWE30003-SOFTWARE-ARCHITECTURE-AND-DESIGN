<?php
class Point {
    public $pointID;
    public $accountID;
    public $pointBalance;
    public $totalPointEarned;
    public $pointRedeemed;
    public $pointQuantity;
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createPoint() {}
    public function retrievePointBalance() {}
    public function updateAccountPointBalance() {}
    public function displayPointBalance() {}
    public function validatePointBalance() {}
    public function validatePointRedemption($accountID, $requiredPoints) {
        $pointInfo = $this->getPointByAccountID($accountID);
        if ($pointInfo && $pointInfo['pointBalance'] >= $requiredPoints) {
            return true;
        }
        return false;
    }
    public function verifyRewardType() {}
    public function redeemPromotionVoucher() {}
    public function redeemMerchandise() {}
    public function getPointByAccountID($accountID) {
        try {
            $sql = "SELECT * FROM point WHERE accountID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$accountID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPointByAccountID: " . $e->getMessage());
            return false;
        }
    }
    public function updatePointBalance($accountID, $newBalance, $newTotalEarned) {
        try {
            $this->conn->beginTransaction();

            // First verify the current point balance
            $currentPoint = $this->getPointByAccountID($accountID);
            if (!$currentPoint) {
                // If no point record exists, create one
                $sql = "INSERT INTO point (accountID, pointBalance, totalPointEarned, pointRedeemed) 
                        VALUES (?, ?, ?, 0)";
                $stmt = $this->conn->prepare($sql);
                $result = $stmt->execute([$accountID, $newBalance, $newTotalEarned]);
            } else {
                // Update existing point record
                $sql = "UPDATE point 
                        SET pointBalance = ?, 
                            totalPointEarned = ? 
                        WHERE accountID = ?";
                $stmt = $this->conn->prepare($sql);
                $result = $stmt->execute([$newBalance, $newTotalEarned, $accountID]);
            }

            if ($result) {
                $this->conn->commit();
                error_log("Successfully updated points for account {$accountID}: New balance = {$newBalance}, Total earned = {$newTotalEarned}");
                return true;
            } else {
                $this->conn->rollBack();
                error_log("Failed to update points for account {$accountID}");
                return false;
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error in updatePointBalance: " . $e->getMessage());
            return false;
        }
    }
    public function createPointRecord($accountID, $initialBalance, $totalEarned, $redeemed) {
        try {
            $this->conn->beginTransaction();

            // Check if record already exists
            $existingPoint = $this->getPointByAccountID($accountID);
            if ($existingPoint) {
                $this->conn->rollBack();
                return false;
            }

            $sql = "INSERT INTO point (accountID, pointBalance, totalPointEarned, pointRedeemed) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$accountID, $initialBalance, $totalEarned, $redeemed]);

            if ($result) {
                $this->conn->commit();
                error_log("Successfully created point record for account {$accountID}");
                return true;
            } else {
                $this->conn->rollBack();
                error_log("Failed to create point record for account {$accountID}");
                return false;
            }
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error in createPointRecord: " . $e->getMessage());
            return false;
        }
    }
    public function getPointHistory($accountID) {
        try {
            $sql = "SELECT * FROM point WHERE accountID = ? ORDER BY pointID DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$accountID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPointHistory: " . $e->getMessage());
            return [];
        }
    }
}
?>
