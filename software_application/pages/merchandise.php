<?php
session_start();
require_once '../classes/Merchandise.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$merchandise = new Merchandise();
$allMerchandise = $merchandise->getAllMerchandise(); // This will fetch all merchandise
?>

<!DOCTYPE html>
<html>
<head>
    <title>ART Merchandise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .merchandise-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            transition: transform 0.3s ease;
        }
        .merchandise-card:hover {
            transform: translateY(-5px);
        }
        .merchandise-image {
            height: 200px;
            object-fit: cover;
            border-radius: 1rem 1rem 0 0;
        }
        .category-badge {
            background: rgba(99,102,241,0.9);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .stock-badge {
            background: rgba(34,197,94,0.9);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            display: inline-block;
        }
        .price-tag {
            font-size: 1.25rem;
            font-weight: bold;
            color: #4f46e5;
        }
        .badges-container {
            margin: 0.5rem 0 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold"><i class="bi bi-bag"></i> ART Merchandise</h1>
            <div>
                <a href="dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <a href="cart.php" class="btn btn-primary">
                    <i class="bi bi-cart3"></i> View Cart
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($allMerchandise)): ?>
                <?php foreach ($allMerchandise as $item): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card merchandise-card h-100">
                            <div class="position-relative">
                                <img src="../assets/images/merchandise/<?php echo htmlspecialchars($item['merchandiseImage']); ?>" 
                                     class="merchandise-image w-100" 
                                     alt="<?php echo htmlspecialchars($item['merchandiseName']); ?>">
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-2">
                                    <?php echo htmlspecialchars($item['merchandiseName']); ?>
                                </h5>
                                <div class="badges-container">
                                    <span class="category-badge">
                                        <?php echo htmlspecialchars($item['merchandiseCategory']); ?>
                                    </span>
                                    <span class="stock-badge">
                                        Stock: <?php echo htmlspecialchars($item['stockQuantity']); ?>
                                    </span>
                                </div>
                                <p class="card-text text-muted mb-3">
                                    <?php echo htmlspecialchars($item['merchandiseDescription']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag">
                                        RM<?php echo number_format($item['merchandisePrice'], 2); ?>
                                    </span>
                                    <?php if ($item['stockQuantity'] > 0): ?>
                                        <form action="add_to_cart.php" method="post">
                                            <input type="hidden" name="merchandiseID" value="<?php echo $item['merchandiseID']; ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="bi bi-x-circle"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No merchandise available at the moment.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
