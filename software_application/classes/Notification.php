<?php
class Notification {
    public $notificationID;
    public $accountID;
    public $messageContent;
    public $notificationType;
    public $notificationDateTime;
    public $notificationStatus;
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

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
        $stmt = $this->db->prepare("INSERT INTO notification (accountID, messageContent, notificationType, notificationDateTime, notificationStatus) VALUES (?, ?, ?, NOW(), 'unread')");
        $stmt->execute([$accountID, $messageContent, $notificationType]);
    }

    public function getNotificationsByAccount($accountID) {
        $sql = "SELECT * FROM notification WHERE accountID = ? ORDER BY notificationDateTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotificationsByAccountID($accountID) {
        $sql = "SELECT * FROM notification WHERE accountID = ? ORDER BY notificationDateTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateNotificationStatus($notificationID, $status) {
        $sql = "UPDATE notification SET notificationStatus = ? WHERE notificationID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $notificationID]);
    }

    public function deleteNotification($notificationID) {
        $sql = "DELETE FROM notification WHERE notificationID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationID]);
    }
}
?>
