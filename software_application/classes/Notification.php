<?php
class Notification {
    public $notificationID;
    public $accountID;
    public $messageContent;
    public $notificationType;
    public $notificationDateTime;
    public $notificationStatus;

    public function sendPaymentConfirmation() {}
    public function notifyBookingUpdate() {}
    public function notifyBookingConfirmation() {}
    public function notifyTripUpdate() {}
    public function promotionExpiredAlert() {}
    public function sendFeedbackConfirmation() {}
    public function notifyFeedbackStatus() {}
    public function storeNotificationRecord() {}
    public function pointRedemptionAlert() {}
    public function notifyMerchandiseRelease() {}
    public function voucherRedemptionExpired() {}
    public function sendFailMessage() {}
    public function sendValidMessage() {}

    public function createNotification($accountID, $messageContent, $notificationType) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "INSERT INTO notification (accountID, messageContent, notificationType, notificationDateTime, notificationStatus) VALUES (?, ?, ?, NOW(), 'unread')";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$accountID, $messageContent, $notificationType]);
    }

    public function getNotificationsByAccount($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "SELECT * FROM notification WHERE accountID = ? ORDER BY notificationDateTime DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
