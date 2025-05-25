<?php
session_start();
require_once '../classes/Notification.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$notification = new Notification();

// Handle actions: mark as read/unread, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notification->updateNotificationStatus($_POST['notificationID'], 'read');
    }
    if (isset($_POST['mark_unread'])) {
        $notification->updateNotificationStatus($_POST['notificationID'], 'unread');
    }
    if (isset($_POST['delete'])) {
        $notification->deleteNotification($_POST['notificationID']);
    }
    // Refresh to avoid resubmission
    header("Location: notifications.php");
    exit();
}

$notifications = $notification->getNotificationsByAccountID($_SESSION['accountID']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .notification-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-bottom: 1.5rem;
            background: #fff;
        }
        .icon-circle {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1.5rem;
            margin-right: 1rem;
            color: #fff;
        }
        .bg-booking { background: #6366f1; }
        .bg-promotion { background: #22d3ee; }
        .bg-payment { background: #16a34a; }
        .bg-feedback { background: #fbbf24; color: #333 !important; }
        .notification-actions form {
            display: inline;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="bi bi-bell"></i> Notifications</h2>
            <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-house"></i> Dashboard</a>
        </div>
        <?php if ($notifications && count($notifications) > 0): ?>
            <?php foreach ($notifications as $note): ?>
                <div class="card notification-card mb-3">
                    <div class="card-body d-flex align-items-center">
                        <?php
                        $icon = 'bi-info-circle';
                        $bg = 'bg-secondary';
                        if ($note['notificationType'] === 'booking') { $icon = 'bi-bus-front'; $bg = 'bg-booking'; }
                        if ($note['notificationType'] === 'promotion') { $icon = 'bi-megaphone'; $bg = 'bg-promotion'; }
                        if ($note['notificationType'] === 'payment') { $icon = 'bi-cash-coin'; $bg = 'bg-payment'; }
                        if ($note['notificationType'] === 'feedback') { $icon = 'bi-chat-dots'; $bg = 'bg-feedback'; }
                        ?>
                        <div class="icon-circle <?php echo $bg; ?>">
                            <i class="bi <?php echo $icon; ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?php echo ucfirst($note['notificationType']); ?></div>
                            <div><?php echo htmlspecialchars($note['messageContent']); ?></div>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-clock"></i>
                                <?php echo date('d M Y, h:i A', strtotime($note['notificationDateTime'])); ?>
                                <?php if ($note['notificationStatus'] === 'unread'): ?>
                                    <span class="badge bg-warning text-dark ms-2">Unread</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2">Read</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="notification-actions ms-3">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="notificationID" value="<?php echo $note['notificationID']; ?>">
                                <?php if ($note['notificationStatus'] === 'unread'): ?>
                                    <button type="submit" name="mark_read" class="btn btn-outline-success btn-sm" title="Mark as Read"><i class="bi bi-check2-circle"></i></button>
                                <?php else: ?>
                                    <button type="submit" name="mark_unread" class="btn btn-outline-warning btn-sm" title="Mark as Unread"><i class="bi bi-envelope"></i></button>
                                <?php endif; ?>
                                <button type="submit" name="delete" class="btn btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">You have no notifications.</div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
