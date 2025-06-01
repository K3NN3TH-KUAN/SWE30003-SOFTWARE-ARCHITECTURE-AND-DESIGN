<?php
session_start();
require_once '../classes/Database.php';

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = "";

// Handle Add Account
if (isset($_POST['add_account'])) {
    $name = $_POST['accountName'];
    $email = $_POST['email'];
    $phone = $_POST['phoneNumber'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = $_POST['accountStatus'];
    $verify = $_POST['accountVerifyStatus'];
    $stmt = $db->prepare("INSERT INTO account (accountName, phoneNumber, password, email, accountStatus, accountVerifyStatus) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $phone, $password, $email, $status, $verify])) {
        $message = "Account added successfully!";
    } else {
        $message = "Failed to add account.";
    }
}

// Handle Edit Account
if (isset($_POST['edit_account'])) {
    $id = $_POST['accountID'];
    $name = $_POST['accountName'];
    $email = $_POST['email'];
    $phone = $_POST['phoneNumber'];
    $status = $_POST['accountStatus'];
    $verify = $_POST['accountVerifyStatus'];
    // Only update password if provided
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE account SET accountName=?, phoneNumber=?, password=?, email=?, accountStatus=?, accountVerifyStatus=? WHERE accountID=?");
        $stmt->execute([$name, $phone, $password, $email, $status, $verify, $id]);
    } else {
        $stmt = $db->prepare("UPDATE account SET accountName=?, phoneNumber=?, email=?, accountStatus=?, accountVerifyStatus=? WHERE accountID=?");
        $stmt->execute([$name, $phone, $email, $status, $verify, $id]);
    }
    $message = "Account updated successfully!";
}

// Handle Delete Account
if (isset($_POST['delete_account'])) {
    $id = $_POST['accountID'];
    // Delete related notifications
    $stmt = $db->prepare("DELETE FROM notification WHERE accountID=?");
    $stmt->execute([$id]);
    // Delete related feedback (if you have a feedback table)
    $stmt = $db->prepare("DELETE FROM feedback WHERE accountID=?");
    $stmt->execute([$id]);
    // Add similar lines for other related tables if needed

    // Now delete the account
    $stmt = $db->prepare("DELETE FROM account WHERE accountID=?");
    $stmt->execute([$id]);
    $message = "Account deleted successfully!";
}

// Fetch all accounts
$stmt = $db->query("SELECT * FROM account");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Accounts - Admin Dashboard</title>
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
        .status-verified {
            background-color: #a7f3d0;
            color: #065f46;
        }
        .status-unverified {
            background-color: #fde68a;
            color: #92400e;
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
            <h1 class="fw-bold">Manage Accounts</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="bi bi-person-plus"></i> Add New Account
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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
                <?php foreach ($accounts as $acc): ?>
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
                            <span class="status-badge status-<?= $acc['accountVerifyStatus'] ?>">
                                <?= ucfirst($acc['accountVerifyStatus']) ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button type="button" class="btn btn-primary btn-sm edit-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editAccountModal"
                                    data-id="<?= $acc['accountID'] ?>"
                                    data-name="<?= htmlspecialchars($acc['accountName'], ENT_QUOTES) ?>"
                                    data-email="<?= htmlspecialchars($acc['email'], ENT_QUOTES) ?>"
                                    data-phone="<?= htmlspecialchars($acc['phoneNumber'], ENT_QUOTES) ?>"
                                    data-status="<?= $acc['accountStatus'] ?>"
                                    data-verify="<?= $acc['accountVerifyStatus'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm delete-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteAccountModal"
                                    data-id="<?= $acc['accountID'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_account" value="1">
                    <div class="mb-2">
                        <label>Name</label>
                        <input type="text" name="accountName" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Phone</label>
                        <input type="text" name="phoneNumber" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="accountStatus" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Verify Status</label>
                        <select name="accountVerifyStatus" class="form-control">
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accountID" id="edit-account-id">
                    <div class="mb-2">
                        <label>Name</label>
                        <input type="text" name="accountName" id="edit-account-name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Email</label>
                        <input type="email" name="email" id="edit-account-email" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Phone</label>
                        <input type="text" name="phoneNumber" id="edit-account-phone" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="accountStatus" id="edit-account-status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Verify Status</label>
                        <select name="accountVerifyStatus" id="edit-account-verify" class="form-control">
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_account" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accountID" id="delete-account-id">
                    <p>Are you sure you want to delete this account?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_account" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit-account-id').value = this.getAttribute('data-id');
                document.getElementById('edit-account-name').value = this.getAttribute('data-name');
                document.getElementById('edit-account-email').value = this.getAttribute('data-email');
                document.getElementById('edit-account-phone').value = this.getAttribute('data-phone');
                document.getElementById('edit-account-status').value = this.getAttribute('data-status');
                document.getElementById('edit-account-verify').value = this.getAttribute('data-verify');
            });
        });
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('delete-account-id').value = this.getAttribute('data-id');
            });
        });
    </script>
</body>
</html> 