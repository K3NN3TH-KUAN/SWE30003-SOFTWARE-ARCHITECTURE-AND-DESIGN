<?php
class TopUp {
    public $topUpID;
    public $accountID;
    public $topUpDate;
    public $topUpTime;
    public $topUpAmount;
    public $topUpType;
    public $topUpStatus;

    public function performTopUp() {}
    public function requestTopUpDetails() {}
    public function validatePaymentDetails($type, $details = []) {
        $validTypes = ['Credit Card', 'Debit Card', 'FPX', 'Touch n Go'];
        // You can add more detailed validation for each type if needed
        return in_array($type, $validTypes);
    }
    public function updateAccountBalance($accountID, $newBalance) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE account SET accountBalance = ? WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$newBalance, $accountID]);
    }
    public function generateNotification() {}
    public function remainAccountBalance() {}
    public function notifySuccessNotification() {}
    public function notifyFailedNotification() {}
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
