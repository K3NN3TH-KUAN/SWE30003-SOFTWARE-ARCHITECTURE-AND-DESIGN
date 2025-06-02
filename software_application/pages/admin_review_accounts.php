<?php
require_once '../classes/Account.php';
require_once '../classes/Notification.php';
require_once '../classes/Database.php';
session_start();
// Add your admin authentication here

$account = new Account();
$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$pendingAccounts = [];

// Handle filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$allowedStatuses = ['all', 'pending', 'verified', 'unverified'];
if (!in_array($statusFilter, $allowedStatuses)) $statusFilter = 'all';

$query = "SELECT * FROM account";
$params = [];
if ($statusFilter !== 'all') {
    $query .= " WHERE accountVerifyStatus = ?";
    $params[] = $statusFilter;
}
$stmt = $db->prepare($query);
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!isset($_SESSION['adminID'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle verify/unverify actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accountID'])) {
    $id = $_POST['accountID'];
    $successMsg = '';
    if (isset($_POST['verify'])) {
        $newStatus = 'verified';
        $successMsg = 'Account has been verified and user notified.';
    } elseif (isset($_POST['unverify'])) {
        $newStatus = 'unverified';
        $successMsg = 'Account has been unverified and user notified.';
    }
    if (isset($newStatus)) {
        $stmt = $db->prepare("UPDATE account SET accountVerifyStatus=? WHERE accountID=?");
        $stmt->execute([$newStatus, $id]);
        // Send notification to user
        $user = $account->getAccountByID($id);
        if ($user) {
            $notifMsg = $newStatus === 'verified'
                ? "Your account has been verified. You now have full access to all features."
                : "Your account verification has been revoked. Please contact support if you have questions.";
            $notification->createNotification($id, $notifMsg, 'feedback');
        }
        // Redirect with success message
        header("Location: admin_review_accounts.php?success=" . urlencode($successMsg) . "&status=" . urlencode($statusFilter));
        exit();
    }
}

// Get success message from URL if present
$successMessage = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Accounts - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .account-card {
            border-radius: 1rem;
            box-shadow: 0 4px 16px rgba(99,102,241,0.10);
            transition: transform 0.18s, box-shadow 0.18s;
            border: none;
            background: #fff;
        }
        .account-card:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 8px 24px rgba(99,102,241,0.18);
        }
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .status-verified {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background-color: #fef08a;
            color: #92400e;
        }
        .status-unverified {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .identity-img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: box-shadow 0.2s;
        }
        .identity-img:hover {
            box-shadow: 0 2px 12px rgba(99,102,241,0.18);
        }
        .btn-verify {
            background: linear-gradient(90deg, #22d3ee 0%, #4ade80 100%);
            color: #fff;
            border: none;
        }
        .btn-verify:hover {
            background: linear-gradient(90deg, #06b6d4 0%, #16a34a 100%);
            color: #fff;
        }
        .btn-unverify {
            background: #e0e7ff;
            color: #3730a3;
            border: none;
        }
        .btn-unverify:hover {
            background: #c7d2fe;
            color: #3730a3;
        }
        .card-title i {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold mb-0">Account Verification Review</h1>
            <a href="admin_dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <!-- Filter Section -->
        <div class="mb-4">
            <form method="get" class="d-flex align-items-center gap-2">
                <label class="fw-bold me-2">Filter by Status:</label>
                <?php
                $statuses = [
                    'all' => 'All',
                    'pending' => 'Pending',
                    'verified' => 'Verified',
                    'unverified' => 'Unverified'
                ];
                foreach ($statuses as $key => $label): ?>
                    <a href="?status=<?= $key ?>"
                       class="btn <?= $statusFilter === $key ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </form>
        </div>
        <div class="row">
            <?php foreach ($accounts as $account): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card account-card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-circle fs-2 me-2"></i>
                                <h5 class="card-title mb-0"><?= htmlspecialchars($account['accountName']) ?></h5>
                            </div>
                            <p class="mb-1"><i class="bi bi-envelope"></i> <b>Email:</b> <?= htmlspecialchars($account['email']) ?></p>
                            <p class="mb-1"><i class="bi bi-telephone"></i> <b>Phone:</b> <?= htmlspecialchars($account['phoneNumber']) ?></p>
                            <p class="mb-1 mt-2 mb-2">
                                <i class="bi bi-shield-check"></i> <b>Verify Status:</b>
                                <span class="status-badge status-<?= $account['accountVerifyStatus'] ?>">
                                    <?= ucfirst($account['accountVerifyStatus']) ?>
                                </span>
                            </p>
                            <?php if (!empty($account['identityDocument'])): ?>
                                <?php
                                    $filename = $account['identityDocument'];
                                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                    $fileUrl = "../uploads/" . rawurlencode($filename);
                                ?>
                                <div class="mb-2">
                                    <b>Identity Document:</b><br>
                                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <a href="<?= $fileUrl ?>" target="_blank">
                                            <img src="<?= $fileUrl ?>" alt="Identity Document" class="identity-img">
                                        </a>
                                    <?php elseif ($ext === 'pdf'): ?>
                                        <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-file-earmark-pdf"></i> View PDF Document
                                        </a>
                                    <?php else: ?>
                                        <span class="text-danger">Unsupported file type</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="mb-2 text-muted"><i>No document uploaded</i></div>
                            <?php endif; ?>
                            <form method="post" class="mt-auto d-flex gap-2">
                                <input type="hidden" name="accountID" value="<?= $account['accountID'] ?>">
                                <?php if ($account['accountVerifyStatus'] !== 'verified'): ?>
                                    <button type="submit" name="verify" class="btn btn-verify btn-sm flex-fill">
                                        <i class="bi bi-check-circle"></i> Verify
                                    </button>
                                <?php endif; ?>
                                <?php if ($account['accountVerifyStatus'] === 'verified'): ?>
                                    <button type="submit" name="unverify" class="btn btn-unverify btn-sm flex-fill">
                                        <i class="bi bi-x-circle"></i> Unverify
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($accounts)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> No accounts found for this filter.
                    </div>
                </div>
            <?php endif; ?>
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
