<?php
require_once '../classes/Notification.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$notification = new Notification();
$notifications = $notification->getNotificationsByAccount($_SESSION['user']['accountID']);
?>
<!DOCTYPE html>
<html>
<head><title>Notifications</title></head>
<body>
    <h2>Notifications</h2>
    <?php if (empty($notifications)): ?>
        <p>No notifications found.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($notifications as $note): ?>
            <li>
                <strong><?php echo htmlspecialchars($note['notificationType']); ?>:</strong>
                <?php echo htmlspecialchars($note['messageContent']); ?>
                <em>(<?php echo htmlspecialchars($note['notificationDateTime']); ?>)</em>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
