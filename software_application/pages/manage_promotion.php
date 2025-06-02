<?php
session_start();
require_once '../classes/Promotion.php';
require_once '../classes/Database.php';

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    header("Location: admin_login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$promotion = new Promotion($db);
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    // Validate form data
                    if (empty($_POST['discountRate']) || empty($_POST['startDate']) || 
                        empty($_POST['expireDate']) || empty($_POST['promotionQuantity']) || 
                        empty($_POST['promotionType'])) {
                        throw new Exception("All fields are required");
                    }

                    $adminID = $_SESSION['adminID']; // Get current admin's ID
                    
                    $sql = "INSERT INTO promotion (adminID, discountRate, startDate, expireDate, promotionQuantity, promotionType) 
                           VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $adminID,
                        $_POST['discountRate'],
                        $_POST['startDate'],
                        $_POST['expireDate'],
                        $_POST['promotionQuantity'],
                        $_POST['promotionType']
                    ]);

                    $message = "Promotion added successfully!";
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                break;

            case 'edit':
                try {
                    $sql = "UPDATE promotion SET 
                            discountRate = ?, 
                            startDate = ?, 
                            expireDate = ?, 
                            promotionQuantity = ?, 
                            promotionType = ?,
                            adminID = ? 
                            WHERE promotionID = ?";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $_POST['discountRate'],
                        $_POST['startDate'],
                        $_POST['expireDate'],
                        $_POST['promotionQuantity'],
                        $_POST['promotionType'],
                        $_SESSION['adminID'], // Update with current admin's ID
                        $_POST['promotionID']
                    ]);

                    $message = "Promotion updated successfully!";
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    $stmt = $db->prepare("DELETE FROM promotion WHERE promotionID = ?");
                    $stmt->execute([$_POST['promotionID']]);
                    $message = "Promotion deleted successfully!";
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                break;
        }
    }
}

// Get all promotions with admin information
$query = "SELECT p.*, a.adminName, a.adminRole 
          FROM promotion p 
          LEFT JOIN admin a ON p.adminID = a.adminID 
          ORDER BY p.startDate DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Promotions - Admin Dashboard</title>
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
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-expired {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .quantity-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .quantity-low {
            background-color: #fef08a;
            color: #92400e;
        }
        .quantity-good {
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Manage Promotions</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromotionModal">
                    <i class="bi bi-plus-lg"></i> Add New Promotion
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive p-4">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Discount Rate</th>
                        <th>Start Date</th>
                        <th>Expire Date</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promotions as $promo): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($promo['promotionType']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($promo['discountRate']); ?>%</td>
                            <td><?php echo htmlspecialchars($promo['startDate']); ?></td>
                            <td><?php echo htmlspecialchars($promo['expireDate']); ?></td>
                            <td>
                                <span class="quantity-badge <?php echo $promo['promotionQuantity'] <= 5 ? 'quantity-low' : 'quantity-good'; ?>">
                                    <?php echo htmlspecialchars($promo['promotionQuantity']); ?> left
                                </span>
                            </td>
                            <td>
                                <?php
                                $today = new DateTime();
                                $expireDate = new DateTime($promo['expireDate']);
                                $status = $today > $expireDate ? 'Expired' : 'Active';
                                $statusClass = $status === 'Active' ? 'status-active' : 'status-expired';
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($promo['adminName'] ?? 'Unknown'); ?>
                                    <br>
                                    <span class="text-muted"><?php echo htmlspecialchars($promo['adminRole'] ?? ''); ?></span>
                                </small>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editPromotionModal"
                                        data-promotion='<?php echo json_encode($promo); ?>'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deletePromotionModal"
                                        data-promotion-id="<?php echo $promo['promotionID']; ?>"
                                        data-promotion-type="<?php echo htmlspecialchars($promo['promotionType']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

    <!-- Add Promotion Modal -->
    <div class="modal fade" id="addPromotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Promotion Type</label>
                            <select class="form-select" name="promotionType" required>
                                <option value="Promotion">Promotion</option>
                                <option value="Voucher">Voucher</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Rate (%)</label>
                            <input type="number" class="form-control" name="discountRate" min="1" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="startDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expire Date</label>
                            <input type="date" class="form-control" name="expireDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity Available</label>
                            <input type="number" class="form-control" name="promotionQuantity" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Promotion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Promotion Modal -->
    <div class="modal fade" id="editPromotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="promotionID" id="edit-promotion-id">
                        <div class="mb-3">
                            <label class="form-label">Promotion Type</label>
                            <select class="form-select" name="promotionType" id="edit-promotion-type" required>
                                <option value="Promotion">Promotion</option>
                                <option value="Voucher">Voucher</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Rate (%)</label>
                            <input type="number" class="form-control" name="discountRate" id="edit-discount-rate" min="1" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="startDate" id="edit-start-date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expire Date</label>
                            <input type="date" class="form-control" name="expireDate" id="edit-expire-date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity Available</label>
                            <input type="number" class="form-control" name="promotionQuantity" id="edit-promotion-quantity" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Promotion Modal -->
    <div class="modal fade" id="deletePromotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this promotion?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <form action="" method="post">
                    <div class="modal-footer">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="promotionID" id="delete-promotion-id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit modal data
        document.querySelectorAll('[data-bs-target="#editPromotionModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const promotion = JSON.parse(this.getAttribute('data-promotion'));
                document.getElementById('edit-promotion-id').value = promotion.promotionID;
                document.getElementById('edit-promotion-type').value = promotion.promotionType;
                document.getElementById('edit-discount-rate').value = promotion.discountRate;
                document.getElementById('edit-start-date').value = promotion.startDate;
                document.getElementById('edit-expire-date').value = promotion.expireDate;
                document.getElementById('edit-promotion-quantity').value = promotion.promotionQuantity;
            });
        });

        // Handle delete modal data
        document.querySelectorAll('[data-bs-target="#deletePromotionModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const promotionId = this.getAttribute('data-promotion-id');
                document.getElementById('delete-promotion-id').value = promotionId;
            });
        });
    </script>
</body>
</html> 
