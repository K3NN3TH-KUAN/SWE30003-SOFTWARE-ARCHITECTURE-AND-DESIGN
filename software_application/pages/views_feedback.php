<?php
require_once '../classes/Feedback.php';
require_once '../classes/Notification.php';
require_once '../classes/Database.php';
session_start();

// Assume only admin can access this page (add your admin auth check here)

$database = new Database();
$db = $database->getConnection();

// Fetch feedback records
$stmt = $db->query("SELECT f.feedbackID, f.accountID, a.accountName, f.message, f.rating, f.created_at
                    FROM feedback f
                    JOIN account a ON f.accountID = a.accountID
                    WHERE f.status = 'active'
                    ORDER BY f.created_at DESC");

$feedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedbackID'])) {
    $feedbackID = $_POST['feedbackID'];

    if (isset($_POST['archive'])) {
        $db->prepare("UPDATE feedback SET status = 'archived' WHERE feedbackID = ?")->execute([$feedbackID]);
    } elseif (isset($_POST['delete'])) {
        $db->prepare("DELETE FROM feedback WHERE feedbackID = ?")->execute([$feedbackID]);
    }
    
    header("Location: admin_review_feedback.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback Form</title>
</head>
<body>
    <h2>Feedback Submitted by Users</h2>

    <?php if (count($feedbackList) === 0): ?>
        <p>No feedback submitted.</p>
    <?php else: ?>
        <?php foreach ($feedbackList as $fb): ?>
            <div style="border:1px solid #ccc; margin:10px; padding:10px;">
                <p><strong>User:</strong> <?=htmlspecialchars($fb['accountName'])?> (ID: <?=$fb['accountID']?>)</p>
                <p><strong>Rating:</strong> <?=htmlspecialchars($fb['rating'])?> / 5</p>
                <p><strong>Message:</strong> <?=nl2br(htmlspecialchars($fb['message']))?></p>
                <p><strong>Submitted At:</strong> <?=$fb['created_at']?></p>
                <form method="post">
                    <input type="hidden" name="feedbackID" value="<?=$fb['feedbackID']?>">
                    <button type="submit" name="archive">Archive</button>
                    <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this feedback?');">Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
