<?php
session_start();
require_once '../classes/Promotion.php';

$promotion = new Promotion();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $sql = "INSERT INTO promotion (discountRate, startDate, expireDate, 
                        promotionQuantity, promotionType) 
                        VALUES (?, ?, ?, ?, ?)";
                $params = [
                    $_POST['discountRate'],
                    $_POST['startDate'],
                    $_POST['expireDate'],
                    $_POST['promotionQuantity'],
                    $_POST['promotionType']
                ];
                
                require_once '../classes/Database.php';
                $database = new Database();
                $db = $database->getConnection();
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute($params)) {
                    $message = "Promotion added successfully!";
                } else {
                    $message = "Error adding promotion.";
                }
                break;

            case 'edit':
                $sql = "UPDATE promotion SET 
                        discountRate = ?, 
                        startDate = ?, 
                        expireDate = ?, 
                        promotionQuantity = ?, 
                        promotionType = ?
                        WHERE promotionID = ?";
                
                $params = [
                    $_POST['discountRate'],
                    $_POST['startDate'],
                    $_POST['expireDate'],
                    $_POST['promotionQuantity'],
                    $_POST['promotionType'],
                    $_POST['promotionID']
                ];

                require_once '../classes/Database.php';
                $database = new Database();
                $db = $database->getConnection();
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute($params)) {
                    $message = "Promotion updated successfully!";
                } else {
                    $message = "Error updating promotion.";
                }
                break;

            case 'delete':
                require_once '../classes/Database.php';
                $database = new Database();
                $db = $database->getConnection();
                
                $stmt = $db->prepare("DELETE FROM promotion WHERE promotionID = ?");
                if ($stmt->execute([$_POST['promotionID']])) {
                    $message = "Promotion deleted successfully!";
                } else {
                    $message = "Error deleting promotion.";
                }
                break;
        }
    }
}

$allPromotions = $promotion->getAllPromotions();
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
        .status-expired {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-upcoming {
            background-color: #e0e7ff;
            color: #3730a3;
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
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive p-4">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Discount Rate</th>
                        <th>Start Date</th>
                        <th>Expire Date</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allPromotions as $item): 
                        $now = new DateTime();
                        $start = new DateTime($item['startDate']);
                        $expire = new DateTime($item['expireDate']);
                        
                        if ($now < $start) {
                            $status = 'upcoming';
                            $statusText = 'Upcoming';
                        } elseif ($now > $expire) {
                            $status = 'expired';
                            $statusText = 'Expired';
                        } else {
                            $status = 'active';
                            $statusText = 'Active';
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['promotionID']); ?></td>
                            <td><?php echo htmlspecialchars($item['promotionType']); ?></td>
                            <td><?php echo htmlspecialchars($item['discountRate']); ?>%</td>
                            <td><?php echo date('Y-m-d', strtotime($item['startDate'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($item['expireDate'])); ?></td>
                            <td><?php echo htmlspecialchars($item['promotionQuantity']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button type="button" class="btn btn-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editPromotionModal"
                                        data-promotion='<?php echo json_encode($item); ?>'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deletePromotionModal"
                                        data-promotion-id="<?php echo $item['promotionID']; ?>"
                                        data-promotion-type="<?php echo htmlspecialchars($item['promotionType']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

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
                            <label class="form-label">Type</label>
                            <select class="form-select" name="promotionType" required>
                                <option value="Promotion">Promotion</option>
                                <option value="Voucher">Voucher</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Rate (%)</label>
                            <input type="number" class="form-control" name="discountRate" min="0" max="100" required>
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
                            <label class="form-label">Quantity</label>
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
                            <label class="form-label">Type</label>
                            <select class="form-select" name="promotionType" id="edit-promotion-type" required>
                                <option value="Promotion">Promotion</option>
                                <option value="Voucher">Voucher</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Rate (%)</label>
                            <input type="number" class="form-control" name="discountRate" id="edit-discount-rate" min="0" max="100" required>
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
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="promotionQuantity" id="edit-promotion-quantity" min="1" required>
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
                    <p>Are you sure you want to delete this <span id="delete-promotion-type"></span>?</p>
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
        // Set min date for date inputs to today
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.min = today;
        });

        // Handle edit modal data
        document.querySelectorAll('[data-bs-target="#editPromotionModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const promotion = JSON.parse(this.getAttribute('data-promotion'));
                document.getElementById('edit-promotion-id').value = promotion.promotionID;
                document.getElementById('edit-promotion-type').value = promotion.promotionType;
                document.getElementById('edit-discount-rate').value = promotion.discountRate;
                document.getElementById('edit-start-date').value = promotion.startDate.split(' ')[0];
                document.getElementById('edit-expire-date').value = promotion.expireDate.split(' ')[0];
                document.getElementById('edit-promotion-quantity').value = promotion.promotionQuantity;
            });
        });

        // Handle delete modal data
        document.querySelectorAll('[data-bs-target="#deletePromotionModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const promotionId = this.getAttribute('data-promotion-id');
                const promotionType = this.getAttribute('data-promotion-type');
                document.getElementById('delete-promotion-id').value = promotionId;
                document.getElementById('delete-promotion-type').textContent = promotionType.toLowerCase();
            });
        });

        // Validate date ranges
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const startDate = new Date(this.querySelector('input[name="startDate"]').value);
                const expireDate = new Date(this.querySelector('input[name="expireDate"]').value);
                
                if (expireDate <= startDate) {
                    e.preventDefault();
                    alert('Expire date must be after start date');
                }
            });
        });
    </script>
</body>
</html> 
