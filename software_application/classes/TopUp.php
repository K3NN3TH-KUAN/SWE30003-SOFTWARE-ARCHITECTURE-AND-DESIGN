<?php
class TopUp {
    public $topUpID;
    public $accountID;
    public $topUpDate;
    public $topUpTime;
    public $topUpAmount;
    public $topUpType;
    public $topUpStatus;

    /**
     * Performs a top-up operation (implementation needed).
     */
    public function performTopUp() {}

    /**
     * Requests details for a top-up (implementation needed).
     */
    public function requestTopUpDetails() {}

    /**
     * Validates the payment details for a top-up.
     */
    public function validatePaymentDetails($type, $details = []) {
        $validTypes = ['Credit Card', 'Debit Card', 'FPX', 'Touch n Go'];
        // You can add more detailed validation for each type if needed
        return in_array($type, $validTypes);
    }

    /**
     * Updates the account balance after a top-up.
     */
    public function updateAccountBalance($accountID, $newBalance) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE account SET accountBalance = ? WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$newBalance, $accountID]);
    }

    /**
     * Generates a notification for a top-up (implementation needed).
     */
    public function generateNotification() {}

    /**
     * Gets the remaining account balance after a top-up (implementation needed).
     */
    public function remainAccountBalance() {}

    /**
     * Notifies the user of a successful top-up (implementation needed).
     */
    public function notifySuccessNotification() {}

    /**
     * Notifies the user of a failed top-up (implementation needed).
     */
    public function notifyFailedNotification() {}

    /**
     * Records a top-up transaction in the database.
     */
    public function recordTopUp($accountID, $amount, $type, $status = 'completed') {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "INSERT INTO topup (accountID, topUpDate, topUpTime, topUpAmount, topUpType, topUpStatus) VALUES (?, CURDATE(), CURTIME(), ?, ?, ?)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$accountID, $amount, $type, $status]);
    }
}
?>
