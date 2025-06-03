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

    /**
     * Sends a payment confirmation notification (implementation needed).
     */
    public function sendPaymentConfirmation() {}

    /**
     * Notifies about a booking update (implementation needed).
     */
    public function notifyBookingUpdate() {}

    /**
     * Notifies about booking confirmation (implementation needed).
     */
    public function notifyBookingConfirmation() {}

    /**
     * Notifies about a trip update (implementation needed).
     */
    public function notifyTripUpdate() {}

    /**
     * Alerts when a promotion has expired (implementation needed).
     */
    public function promotionExpiredAlert() {}

    /**
     * Sends feedback confirmation (implementation needed).
     */
    public function sendFeedbackConfirmation() {}

    /**
     * Notifies about feedback status (implementation needed).
     */
    public function notifyFeedbackStatus() {}

    /**
     * Stores a notification record (implementation needed).
     */
    public function storeNotificationRecord() {}

    /**
     * Alerts about point redemption (implementation needed).
     */
    public function pointRedemptionAlert() {}

    /**
     * Notifies about merchandise release (implementation needed).
     */
    public function notifyMerchandiseRelease() {}

    /**
     * Alerts when a voucher redemption has expired (implementation needed).
     */
    public function voucherRedemptionExpired() {}

    /**
     * Sends a failure message notification (implementation needed).
     */
    public function sendFailMessage() {}

    /**
     * Sends a valid message notification (implementation needed).
     */
    public function sendValidMessage() {}

    /**
     * Creates a notification record in the database.
     */
    public function createNotification($accountID, $messageContent, $notificationType) {
        $stmt = $this->db->prepare("INSERT INTO notification (accountID, messageContent, notificationType, notificationDateTime, notificationStatus) VALUES (?, ?, ?, NOW(), 'unread')");
        $stmt->execute([$accountID, $messageContent, $notificationType]);
    }

    /**
     * Gets all notifications for an account.
     */
    public function getNotificationsByAccount($accountID) {
        $sql = "SELECT * FROM notification WHERE accountID = ? ORDER BY notificationDateTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all notifications for an account by account ID.
     */
    public function getNotificationsByAccountID($accountID) {
        $sql = "SELECT * FROM notification WHERE accountID = ? ORDER BY notificationDateTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Updates the status of a notification.
     */
    public function updateNotificationStatus($notificationID, $status) {
        $sql = "UPDATE notification SET notificationStatus = ? WHERE notificationID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $notificationID]);
    }

    /**
     * Deletes a notification by ID.
     */
    public function deleteNotification($notificationID) {
        $sql = "DELETE FROM notification WHERE notificationID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$notificationID]);
    }
}
?>
