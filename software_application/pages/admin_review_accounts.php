<?php
require_once '../classes/Account.php';
require_once '../classes/Notification.php';
session_start();
// Add your admin authentication here

$account = new Account();
$pendingAccounts = [];
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->query("SELECT * FROM account WHERE accountVerifyStatus = 'pending' AND identityDocument IS NOT NULL");
$pendingAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accountID'])) {
    $accountID = $_POST['accountID'];
    $notification = new Notification();
    if (isset($_POST['approve'])) {
        $account->verifyAccountStatus($accountID);
        $notification->createNotification($accountID, 'Your account has been verified by admin.', 'feedback');
    } elseif (isset($_POST['reject'])) {
        $db->prepare("UPDATE account SET accountVerifyStatus = 'unverified' WHERE accountID = ?")->execute([$accountID]);
        $notification->createNotification($accountID, 'Your account verification was rejected by admin.', 'feedback');
    }
    header("Location: admin_review_accounts.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Admin Review Accounts</title></head>
<body>
    <h2>Accounts Pending Verification</h2>
    <?php foreach ($pendingAccounts as $user): ?>
        <div style="border:1px solid #ccc; margin:10px; padding:10px;">
            <p><strong>Name:</strong> <?=htmlspecialchars($user['accountName'])?></p>
            <p><strong>Email:</strong> <?=htmlspecialchars($user['email'])?></p>
            <p><strong>Phone:</strong> <?=htmlspecialchars($user['phoneNumber'])?></p>
            <p><strong>Identity Document:</strong>
                <a href="<?=htmlspecialchars($user['identityDocument'])?>" target="_blank">View</a>
            </p>
            <form method="post">
                <input type="hidden" name="accountID" value="<?=$user['accountID']?>">
                <button type="submit" name="approve">Approve</button>
                <button type="submit" name="reject">Reject</button>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>
