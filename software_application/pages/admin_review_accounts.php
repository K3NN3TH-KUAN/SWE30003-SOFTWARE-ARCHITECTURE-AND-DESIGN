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
$stmt = $db->query("SELECT * FROM account WHERE accountVerifyStatus = 'pending'");
$pendingAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!isset($_SESSION['adminID'])) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['accountID'];
    $action = $_POST['action'];
    $newStatus = $action === 'approve' ? 'verified' : 'unverified';
    $stmt = $db->prepare("UPDATE account SET accountVerifyStatus=? WHERE accountID=?");
    $stmt->execute([$newStatus, $id]);
    header("Location: admin_review_accounts.php");
    exit();
}
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
        .table-responsive {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-pending {
            background-color: #fef08a;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Verify Accounts</h1>
            <a href="admin_dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>

        <div class="table-responsive p-4">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Account ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Verify Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingAccounts as $acc): ?>
                    <tr>
                        <td><?= $acc['accountID'] ?></td>
                        <td><?= htmlspecialchars($acc['accountName']) ?></td>
                        <td><?= htmlspecialchars($acc['email']) ?></td>
                        <td><?= htmlspecialchars($acc['phoneNumber']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $acc['accountStatus'] ?>">
                                <?= ucfirst($acc['accountStatus']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-pending"><?= ucfirst($acc['accountVerifyStatus']) ?></span>
                        </td>
                        <td class="action-buttons">
                            <button type="button" class="btn btn-success btn-sm approve-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#approveModal"
                                data-id="<?= $acc['accountID'] ?>"
                                data-name="<?= htmlspecialchars($acc['accountName'], ENT_QUOTES) ?>">
                                <i class="bi bi-check-circle"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm reject-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal"
                                data-id="<?= $acc['accountID'] ?>"
                                data-name="<?= htmlspecialchars($acc['accountName'], ENT_QUOTES) ?>">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pendingAccounts)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No accounts pending verification.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accountID" id="approve-account-id">
                    <input type="hidden" name="action" value="approve">
                    <p>Are you sure you want to <span class="text-success fw-bold">approve</span> this account?</p>
                    <p class="mb-0"><span id="approve-account-name"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accountID" id="reject-account-id">
                    <input type="hidden" name="action" value="reject">
                    <p>Are you sure you want to <span class="text-danger fw-bold">reject</span> this account?</p>
                    <p class="mb-0"><span id="reject-account-name"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Approve modal
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('approve-account-id').value = this.getAttribute('data-id');
                document.getElementById('approve-account-name').textContent = this.getAttribute('data-name');
            });
        });
        // Reject modal
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('reject-account-id').value = this.getAttribute('data-id');
                document.getElementById('reject-account-name').textContent = this.getAttribute('data-name');
            });
        });
    </script>
</body>
</html>
