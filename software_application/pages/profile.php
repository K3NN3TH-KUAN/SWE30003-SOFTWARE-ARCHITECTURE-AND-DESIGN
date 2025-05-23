<?php
require_once '../classes/Account.php';
require_once '../classes/Notification.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$account = new Account();
$user = $account->getAccountByID($_SESSION['user']['accountID']);
$message = "";
$identityUploaded = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['updateProfile'])) {
        $accountName = $_POST['accountName'];
        $phoneNumber = $_POST['phoneNumber'];
        $email = $_POST['email'];
        $password = $_POST['password'] ? $_POST['password'] : null;
    
        // Detect changed fields
        $changedFields = [];
        if ($accountName !== $user['accountName']) $changedFields[] = 'Name';
        if ($phoneNumber !== $user['phoneNumber']) $changedFields[] = 'Phone Number';
        if ($email !== $user['email']) $changedFields[] = 'Email';
        if ($password) $changedFields[] = 'Password';
    
        $success = $account->updateAccountInfo($user['accountID'], $accountName, $phoneNumber, $email, $password);
        if ($success) {
            $user = $account->getAccountByID($user['accountID']);
            $_SESSION['user'] = $user;
            $message = "Profile updated successfully.";
            if (!empty($changedFields)) {
                $notification = new Notification();
                $fieldsList = implode(', ', $changedFields);
                $notification->createNotification(
                    $user['accountID'],
                    "Your profile was updated. Fields changed: $fieldsList.",
                    'feedback'
                );
            }
        } else {
            $message = "Failed to update profile.";
        }
    }
    if (isset($_POST['uploadIdentity']) && isset($_FILES['identityDocument']) && $_FILES['identityDocument']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['identityDocument']['name']);
        $targetFile = $uploadDir . uniqid('identity_') . '_' . $fileName;
        if (move_uploaded_file($_FILES['identityDocument']['tmp_name'], $targetFile)) {
            $account->uploadIdentity($user['accountID'], $targetFile);
            $user = $account->getAccountByID($user['accountID']);
            $_SESSION['user'] = $user;
            $identityUploaded = true;
            $message = "Identity document uploaded successfully. Awaiting admin review.";
            $notification = new Notification();
            $notification->createNotification($user['accountID'], 'Your identity document was uploaded and is pending review.', 'feedback');
        } else {
            $message = "Failed to upload identity document.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Profile</title></head>
<body>
    <h2>Profile Page</h2>
    <?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>
    <p><strong>Account Verify Status:</strong> <?php echo htmlspecialchars($user['accountVerifyStatus']); ?></p>
    <form method="post">
        <label>Name:</label><br>
        <input type="text" name="accountName" value="<?php echo htmlspecialchars($user['accountName']); ?>" required><br>
        <label>Phone Number:</label><br>
        <input type="text" name="phoneNumber" value="<?php echo htmlspecialchars($user['phoneNumber']); ?>" required><br>
        <label>Email:</label><br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>
        <label>New Password (leave blank to keep current):</label><br>
        <input type="password" name="password"><br>
        <button type="submit" name="updateProfile">Update Profile</button>
    </form>
    <hr>
    <form method="post" enctype="multipart/form-data">
        <label>Upload Identity Document:</label><br>
        <input type="file" name="identityDocument" accept="image/*,application/pdf" required><br>
        <button type="submit" name="uploadIdentity">Upload Identity</button>
    </form>
    <?php if (!empty($user['identityDocument'])): ?>
        <p>Identity Document Uploaded: <a href="<?php echo htmlspecialchars($user['identityDocument']); ?>" target="_blank">View</a></p>
    <?php endif; ?>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
