<?php
require_once '../classes/Account.php';
require_once '../classes/Notification.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

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
                $notification = new Notification($db);
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
    if (isset($_POST['uploadIdentity']) && isset($_FILES['identityDocument'])) {
        $filename = uniqid('identity_') . '_' . basename($_FILES['identityDocument']['name']);
        $targetPath = '../uploads/' . $filename;
        if (move_uploaded_file($_FILES['identityDocument']['tmp_name'], $targetPath)) {
            // Store only the filename
            $stmt = $db->prepare("UPDATE account SET identityDocument = ? WHERE accountID = ?");
            $stmt->execute([$filename, $user['accountID']]);
            $user = $account->getAccountByID($user['accountID']);
            $_SESSION['user'] = $user;
            $identityUploaded = true;
            $message = "Identity document uploaded successfully. Awaiting admin review.";
            $notification = new Notification($db);
            $notification->createNotification($user['accountID'], 'Your identity document was uploaded and is pending review.', 'feedback');
        } else {
            $message = "Failed to upload identity document.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .profile-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-top: 3rem;
            padding: 2rem 2rem 1.5rem 2rem;
            background: #fff;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 2.5rem;
            margin: 0 auto 1rem auto;
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
        }
        .profile-label {
            font-weight: 500;
            color: #6366f1;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #6366f1;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            /* border-bottom: 2px solid #e0e7ff; */
        }
        .identity-preview {
            max-width: 100%;
            max-height: 180px;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
        }
        .section-container {
            border: 1px solid #e0e7ff;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            background: #fafafa;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .section-header i {
            color: #6366f1;
        }
        .form-control:read-only {
            background-color: #f8fafc;
        }
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-md-8">
            <div class="profile-card">
                <div class="text-center mb-4">
                    <div class="icon-circle">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h2 class="fw-bold mb-1">My Profile</h2>
                    <p class="text-muted mb-0">Manage your ART account details</p>
                </div>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $identityUploaded ? 'success' : 'info'; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <!-- Profile Info -->
                <div class="section-container">
                    <div class="section-header">
                        <h3 class="section-title mb-0"><i class="bi bi-person-lines-fill"></i> Account Information</h3>
                    </div>
                    <form method="post" id="profileForm" class="mb-4">
                        <div class="row mb-3">
                            <div class="col-sm-4 profile-label">Name:</div>
                            <div class="col-sm-8">
                                <input type="text" name="accountName" class="form-control" value="<?php echo htmlspecialchars($user['accountName']); ?>" required readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 profile-label">Email:</div>
                            <div class="col-sm-8">
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 profile-label">Phone Number:</div>
                            <div class="col-sm-8">
                                <input type="text" name="phoneNumber" class="form-control" value="<?php echo htmlspecialchars($user['phoneNumber']); ?>" required readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 profile-label">New Password:</div>
                            <div class="col-sm-8">
                                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current" readonly>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mb-3" id="profileButtons">
                            <button type="button" id="editProfileBtn" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit Profile</button>
                            <button type="submit" name="updateProfile" id="updateProfileBtn" class="btn btn-primary d-none ms-2"><i class="bi bi-save"></i> Update Profile</button>
                            <button type="button" id="discardChangesBtn" class="btn btn-secondary d-none ms-2">Discard Changes</button>
                        </div>
                    </form>
                </div>
                <script>
                    // Enable edit mode for profile fields
                    document.addEventListener('DOMContentLoaded', function() {
                        const editBtn = document.getElementById('editProfileBtn');
                        const updateBtn = document.getElementById('updateProfileBtn');
                        const discardBtn = document.getElementById('discardChangesBtn');
                        const form = document.getElementById('profileForm');
                        const inputs = form.querySelectorAll('input:not([type="hidden"])');

                        editBtn.addEventListener('click', function() {
                            inputs.forEach(input => {
                                if (input.name !== 'email') input.removeAttribute('readonly');
                            });
                            updateBtn.classList.remove('d-none');
                            discardBtn.classList.remove('d-none');
                            editBtn.classList.add('d-none');
                        });

                        discardBtn.addEventListener('click', function() {
                            // Reload the page to discard changes
                            window.location.reload();
                        });
                    });
                </script>

                <!-- Identity Verification Section -->
                <div class="section-container">
                    <div class="section-header">
                        <h3 class="section-title mb-0"><i class="bi bi-file-earmark-person"></i> Identity Verification</h3>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <?php if (!empty($user['identityDocument'])): ?>
                            <div class="mb-3">
                                <label class="form-label">Uploaded Document:</label><br>
                                <?php
                                $filename = $user['identityDocument'];
                                $fileUrl = "../uploads/" . rawurlencode($filename);
                                ?>
                                <img src="<?php echo $fileUrl; ?>" class="identity-preview" alt="Identity Document">
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="identityDocument" class="form-label">Upload Identity Document (PDF, JPG, PNG)</label>
                            <input type="file" name="identityDocument" id="identityDocument" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="uploadIdentity" class="btn btn-success"><i class="bi bi-upload"></i> Upload Document</button>
                        </div>
                    </form>
                </div>

                <!-- Account Status -->
                <div class="section-container">
                    <div class="section-header">
                        <h3 class="section-title mb-0"><i class="bi bi-shield-check"></i> Account Status</h3>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 profile-label">Account Status:</div>
                        <div class="col-sm-8">
                            <span class="badge <?php echo $user['accountStatus'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ucfirst($user['accountStatus']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 profile-label">Verify Status:</div>
                        <div class="col-sm-8">
                            <span class="badge <?php echo $user['accountVerifyStatus'] === 'verified' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                <?php echo ucfirst($user['accountVerifyStatus']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-sm-4 profile-label">Balance:</div>
                        <div class="col-sm-8 fw-bold text-success">RM<?php echo number_format($user['accountBalance'], 2); ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-house"></i> Dashboard</a>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
    <footer class="bg-light text-center text-lg-start mt-5 border-top shadow-sm">
        <div class="container py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <span class="mx-2">@Group_21 (A3)</span>
                    <span class="mx-2">|</span>
                    Kuching ART Website &copy; <?php echo date('Y'); ?>. All rights reserved.
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
