<?php
session_start();
require_once '../classes/Database.php';
require_once '../classes/Merchandise.php';
$database = new Database();
$db = $database->getConnection();

$merchandise = new Merchandise($db);
$message = '';
$error = '';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    // Validate form data
                    if (empty($_POST['merchandiseName']) || empty($_POST['merchandisePrice']) || 
                        empty($_POST['merchandiseDescription']) || empty($_POST['stockQuantity']) || 
                        empty($_POST['merchandiseCategory'])) {
                        throw new Exception("All fields are required");
                    }

                    // Debug information for file upload
                    if (!isset($_FILES['merchandiseImage'])) {
                        throw new Exception("No file was uploaded");
                    }

                    // Check for file upload errors
                    if ($_FILES['merchandiseImage']['error'] !== UPLOAD_ERR_OK) {
                        $uploadErrors = array(
                            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
                            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
                            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
                            UPLOAD_ERR_NO_FILE => "No file was uploaded",
                            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
                            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
                            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
                        );
                        $errorCode = $_FILES['merchandiseImage']['error'];
                        throw new Exception("File upload error: " . ($uploadErrors[$errorCode] ?? "Unknown error"));
                    }

                    // Validate image file
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($fileInfo, $_FILES['merchandiseImage']['tmp_name']);
                    finfo_close($fileInfo);

                    if (!in_array($mimeType, $allowedTypes)) {
                        throw new Exception("Invalid file type ($mimeType). Only JPG, PNG and GIF are allowed");
                    }

                    // Create merchandise directory if it doesn't exist
                    $targetDir = "../assets/images/merchandise/";
                    if (!file_exists($targetDir)) {
                        if (!mkdir($targetDir, 0777, true)) {
                            throw new Exception("Failed to create directory: " . error_get_last()['message']);
                        }
                    }

                    // Check directory permissions
                    if (!is_writable($targetDir)) {
                        throw new Exception("Directory is not writable: $targetDir");
                    }

                    // Generate unique filename
                    $imageFileName = time() . '_' . basename($_FILES["merchandiseImage"]["name"]);
                    $targetFile = $targetDir . $imageFileName;
                    
                    // Move uploaded file
                    if (!move_uploaded_file($_FILES["merchandiseImage"]["tmp_name"], $targetFile)) {
                        $lastError = error_get_last();
                        throw new Exception("Failed to move uploaded file. Error: " . ($lastError ? $lastError['message'] : 'Unknown error'));
                    }

                    // Verify file was actually uploaded
                    if (!file_exists($targetFile)) {
                        throw new Exception("File was not created at target location");
                    }

                    // Insert into database
                    require_once '../classes/Database.php';
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    $adminID = $_SESSION['adminID']; // Get the current admin's ID
                    $sql = "INSERT INTO merchandise (adminID, merchandiseName, merchandisePrice, merchandiseDescription, stockQuantity, quantity, merchandiseCategory, merchandiseImage) 
                            VALUES (?, ?, ?, ?, ?, 0, ?, ?)";
                    
                    $params = [
                        $adminID,
                        $_POST['merchandiseName'],
                        $_POST['merchandisePrice'],
                        $_POST['merchandiseDescription'],
                        $_POST['stockQuantity'],
                        $_POST['merchandiseCategory'],
                        $imageFileName
                    ];
                    
                    $stmt = $db->prepare($sql);
                    
                    if (!$stmt->execute($params)) {
                        // If database insert fails, delete the uploaded image
                        unlink($targetFile);
                        throw new Exception("Failed to add merchandise to database: " . implode(", ", $stmt->errorInfo()));
                    }

                    $message = "Merchandise added successfully!";
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    // Log the error for debugging
                    error_log("Error in admin_manage_merchandise.php: " . $e->getMessage());
                }
                break;

            case 'edit':
                try {
                    $sql = "UPDATE merchandise SET 
                            merchandiseName = ?, 
                            merchandisePrice = ?, 
                            merchandiseDescription = ?, 
                            stockQuantity = ?, 
                            merchandiseCategory = ?";
                    
                    $params = [
                        $_POST['merchandiseName'],
                        $_POST['merchandisePrice'],
                        $_POST['merchandiseDescription'],
                        $_POST['stockQuantity'],
                        $_POST['merchandiseCategory']
                    ];

                    // Handle new image if uploaded
                    if (!empty($_FILES['merchandiseImage']['name'])) {
                        $targetDir = "../assets/images/merchandise/";
                        $imageFileName = time() . '_' . basename($_FILES["merchandiseImage"]["name"]);
                        $targetFile = $targetDir . $imageFileName;
                        
                        if (move_uploaded_file($_FILES["merchandiseImage"]["tmp_name"], $targetFile)) {
                            $sql .= ", merchandiseImage = ?";
                            $params[] = $imageFileName;
                        }
                    }

                    $sql .= " WHERE merchandiseID = ?";
                    $params[] = $_POST['merchandiseID'];

                    require_once '../classes/Database.php';
                    $database = new Database();
                    $db = $database->getConnection();
                    $stmt = $db->prepare($sql);
                    
                    if ($stmt->execute($params)) {
                        $message = "Merchandise updated successfully!";
                    } else {
                        throw new Exception("Error updating merchandise");
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                break;

            case 'delete':
                try {
                    require_once '../classes/Database.php';
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    // Start transaction
                    $db->beginTransaction();
                    
                    // First get the image filename
                    $stmt = $db->prepare("SELECT merchandiseImage FROM merchandise WHERE merchandiseID = ?");
                    $stmt->execute([$_POST['merchandiseID']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Delete related records from line_of_sale first
                    $stmt = $db->prepare("DELETE FROM line_of_sale WHERE merchandiseID = ?");
                    $stmt->execute([$_POST['merchandiseID']]);
                    
                    // Then delete the merchandise record
                    $stmt = $db->prepare("DELETE FROM merchandise WHERE merchandiseID = ?");
                    $stmt->execute([$_POST['merchandiseID']]);
                    
                    // If database operations successful, delete the image file
                    if ($result) {
                        $imagePath = "../assets/images/merchandise/" . $result['merchandiseImage'];
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    
                    // Commit the transaction
                    $db->commit();
                    $message = "Merchandise and related records deleted successfully!";
                } catch (Exception $e) {
                    // Rollback the transaction if any error occurs
                    $db->rollBack();
                    $error = "Error deleting merchandise: " . $e->getMessage();
                }
                break;
        }
    }
}

$allMerchandise = $merchandise->getAllMerchandise();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Merchandise - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .merchandise-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
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
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold">Manage Merchandise</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMerchandiseModal">
                    <i class="bi bi-plus-lg"></i> Add New Merchandise
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
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price (RM)</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allMerchandise as $item): ?>
                        <tr>
                            <td>
                                <img src="../assets/images/merchandise/<?php echo htmlspecialchars($item['merchandiseImage']); ?>" 
                                     class="merchandise-image" 
                                     alt="<?php echo htmlspecialchars($item['merchandiseName']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($item['merchandiseName']); ?></td>
                            <td><?php echo htmlspecialchars($item['merchandiseCategory']); ?></td>
                            <td><?php echo number_format($item['merchandisePrice'], 2); ?></td>
                            <td>
                                <span class="badge <?php echo $item['stockQuantity'] > 10 ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($item['stockQuantity']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button type="button" class="btn btn-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editMerchandiseModal"
                                        data-merchandise='<?php echo json_encode($item); ?>'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteMerchandiseModal"
                                        data-merchandise-id="<?php echo $item['merchandiseID']; ?>"
                                        data-merchandise-name="<?php echo htmlspecialchars($item['merchandiseName']); ?>">
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

    <!-- Add Merchandise Modal -->
    <div class="modal fade" id="addMerchandiseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Merchandise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="merchandiseName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="merchandiseCategory" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (RM)</label>
                            <input type="number" class="form-control" name="merchandisePrice" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" name="stockQuantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="merchandiseDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="merchandiseImage" accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Merchandise</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Merchandise Modal -->
    <div class="modal fade" id="editMerchandiseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Merchandise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="merchandiseID" id="edit-merchandise-id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="merchandiseName" id="edit-merchandise-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="merchandiseCategory" id="edit-merchandise-category" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (RM)</label>
                            <input type="number" class="form-control" name="merchandisePrice" id="edit-merchandise-price" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" name="stockQuantity" id="edit-stock-quantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="merchandiseDescription" id="edit-merchandise-description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Image (leave empty to keep current image)</label>
                            <input type="file" class="form-control" name="merchandiseImage" accept="image/*">
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

    <!-- Delete Merchandise Modal -->
    <div class="modal fade" id="deleteMerchandiseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Merchandise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span id="delete-merchandise-name"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <form action="" method="post">
                    <div class="modal-footer">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="merchandiseID" id="delete-merchandise-id">
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
        document.querySelectorAll('[data-bs-target="#editMerchandiseModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const merchandise = JSON.parse(this.getAttribute('data-merchandise'));
                document.getElementById('edit-merchandise-id').value = merchandise.merchandiseID;
                document.getElementById('edit-merchandise-name').value = merchandise.merchandiseName;
                document.getElementById('edit-merchandise-category').value = merchandise.merchandiseCategory;
                document.getElementById('edit-merchandise-price').value = merchandise.merchandisePrice;
                document.getElementById('edit-stock-quantity').value = merchandise.stockQuantity;
                document.getElementById('edit-merchandise-description').value = merchandise.merchandiseDescription;
            });
        });

        // Handle delete modal data
        document.querySelectorAll('[data-bs-target="#deleteMerchandiseModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const merchandiseId = this.getAttribute('data-merchandise-id');
                const merchandiseName = this.getAttribute('data-merchandise-name');
                document.getElementById('delete-merchandise-id').value = merchandiseId;
                document.getElementById('delete-merchandise-name').textContent = merchandiseName;
            });
        });
    </script>
</body>
</html> 
