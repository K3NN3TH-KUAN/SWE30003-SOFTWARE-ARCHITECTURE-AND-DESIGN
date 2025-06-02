<?php
session_start();
require_once '../classes/Trip.php';
require_once '../classes/Promotion.php';
require_once '../classes/Account.php';
require_once '../classes/Point.php';
require_once '../classes/Merchandise.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

// Check if a trip is selected
if (!isset($_SESSION['selected_trip'])) {
    header('Location: book_trip.php');
    exit();
}

$trip = new Trip();
$promotion = new Promotion($db);
$account = new Account();
$point = new Point();
$merchandise = new Merchandise($db);

$accountID = $_SESSION['accountID'];
$tripID = $_SESSION['selected_trip'];

// Get trip details
$tripDetails = $trip->getTripByID($tripID);
if (!$tripDetails) {
    unset($_SESSION['selected_trip']);
    header('Location: book_trip.php');
    exit();
}

// Get account info
$accountInfo = $account->getAccountByID($accountID);
$pointInfo = $point->getPointByAccountID($accountID);

// Get seat quantity from POST or set default to 1
$seatQuantity = isset($_POST['seatQuantity']) ? (int)$_POST['seatQuantity'] : 1;

// Get 2 random merchandise
$suggestedMerchandise = $merchandise->getRandomMerchandise(2);

// Get available promotions and redeemed vouchers
$availablePromotions = $promotion->getAvailablePromotions('Promotion');
$redeemedVouchers = $promotion->getRedeemedVouchers($accountID);

// Calculate total amount including merchandise
$totalAmount = $tripDetails['totalAmount'] * $seatQuantity;
$merchandiseTotal = 0;

if (isset($_POST['selected_merchandise']) && !empty($_POST['selected_merchandise'])) {
    foreach ($_POST['selected_merchandise'] as $merchandiseID => $quantity) {
        if ($quantity > 0) {
            $merchandiseDetails = $merchandise->getMerchandiseByID($merchandiseID);
            if ($merchandiseDetails) {
                $merchandiseTotal += $merchandiseDetails['merchandisePrice'] * $quantity;
            }
        }
    }
}

$totalAmount += $merchandiseTotal;

// Check if account balance is sufficient
if ($accountInfo['accountBalance'] < $totalAmount) {
    $_SESSION['error'] = "Insufficient account balance. You need RM" . number_format($totalAmount, 2) . 
                        " but your current balance is RM" . number_format($accountInfo['accountBalance'], 2);
    header("Location: book_trip.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_payment'])) {
    // Debug output
    error_log("Form submitted with POST data: " . print_r($_POST, true));
    
    // Make promotion optional - only set if explicitly selected
    $promotionID = null;
    if (isset($_POST['promotionID']) && !empty($_POST['promotionID'])) {
        $promotionID = $_POST['promotionID'];
    }
    
    // Make merchandise optional
    $selectedMerchandise = [];
    if (isset($_POST['merchandise']) && is_array($_POST['merchandise'])) {
        foreach ($_POST['merchandise'] as $merchID => $qty) {
            if ((int)$qty > 0) {
                $selectedMerchandise[$merchID] = (int)$qty;
            }
        }
    }

    // Validate seat quantity
    if ($seatQuantity > $tripDetails['availableSeats']) {
        $error = "Selected seats exceed available seats.";
    } else {
        // Store booking details in session
        $_SESSION['booking_details'] = [
            'trip_id' => $tripID,
            'seat_quantity' => $seatQuantity,
            'promotion_id' => $promotionID,
            'merchandise' => $selectedMerchandise
        ];
        
        // Debug output
        error_log("Booking details stored in session: " . print_r($_SESSION['booking_details'], true));
        
        // Redirect to payment
        header('Location: process_trip_payment.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Kuching ART</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .section-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .promo-card {
            border: 2px solid #e0e7ef;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .promo-card.selected, .promo-card:hover {
            border-color: #e0e7ff;
            box-shadow: 0 2px 12px rgba(224, 231, 255, 0.5);
            background: #f8fafc;
        }
        .merch-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .merch-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .merch-image {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
        }
        .merch-image img {
            transition: transform 0.3s ease;
        }
        .merch-card:hover .merch-image img {
            transform: scale(1.05);
        }
        .price-highlight {
            background: #e0e7ff;
            color: #3b5bdb;
            padding: 0.3rem 0.8rem;
            border-radius: 0.5rem;
            font-weight: bold;
        }
        .summary-card {
            background: linear-gradient(135deg,rgb(161, 178, 232) 0%, #f8fafc 100%);
            color: #1e293b;
            border-radius: 1.5rem;
            padding: 2rem;
            position: sticky;
            top: 2rem;
            box-shadow: 0 10px 20px rgba(224, 231, 255, 0.3);
            transition: all 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(224, 231, 255, 0.4);
        }
        .summary-card .list-group-item {
            background: rgba(255, 255, 255, 0.7);
            border-color: rgba(224, 231, 255, 0.5);
            color: #1e293b;
            backdrop-filter: blur(5px);
        }
        .summary-card .btn {
            background: #3b5bdb;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .summary-card .btn:hover {
            background: #4c6ef5;
            transform: translateY(-2px);
        }
        .btn-primary {
            background: #3b5bdb;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 1rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #4c6ef5;
            transform: translateY(-2px);
        }
        .discount-badge {
            background: #e0e7ff;
            color: #3b5bdb;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-weight: bold;
        }
        .voucher-badge {
            background: #dcfce7;
            color: #16a34a;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-weight: bold;
        }
        .summary-card .btn i {
            margin-right: 0.5rem;
        }
        .input-group .btn-outline-secondary {
            border-color: #e0e7ff;
            color: #3b5bdb;
        }
        
        .input-group .btn-outline-secondary:hover {
            background-color: #e0e7ff;
            color: #3b5bdb;
        }
        
        .input-group .form-control {
            border-color: #e0e7ff;
        }
        
        .input-group .form-control:focus {
            box-shadow: none;
            border-color: #3b5bdb;
        }
        
        .no-image {
            border: 2px dashed #e0e7ff;
            background: #f8fafc;
        }
        
        .card-title {
            color: #1e293b;
        }
        
        .text-muted {
            color: #64748b !important;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h2 class="mb-4 text-center"><i class="bi bi-cart-check"></i> Checkout</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']); // Clear the error message after displaying
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="checkoutForm">
            <div class="row">
                <!-- Main content -->
                <div class="col-md-8">
                    <!-- Trip Details -->
                    <div class="section-card">
                        <h5 class="card-title mb-3"><i class="bi bi-train-front"></i> Trip Details</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Route:</strong> <?php echo htmlspecialchars($tripDetails['origin']); ?> to 
                                <?php echo htmlspecialchars($tripDetails['destination']); ?></p>
                                <p class="mb-2"><strong>Date:</strong> <?php echo date('d M Y', strtotime($tripDetails['tripDate'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Time:</strong> <?php echo date('h:i A', strtotime($tripDetails['tripTime'])); ?></p>
                                <p class="mb-2"><strong>Price per seat:</strong> RM<?php echo number_format($tripDetails['totalAmount'], 2); ?></p>
                                <p class="mb-0"><strong>Available seats:</strong> <?php echo $tripDetails['availableSeats']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Seat Selection -->
                    <div class="section-card">
                        <h5 class="card-title mb-3"><i class="bi bi-person-plus"></i> Select Seats</h5>
                        <div class="mb-3">
                            <label class="form-label">Number of Seats</label>
                            <div class="input-group">
                                <input type="number" name="seatQuantity" id="seatQuantity" class="form-control text-center" 
                                       min="1" max="<?php echo $tripDetails['availableSeats']; ?>" value="1" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="decreaseSeats()">-</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="increaseSeats()">+</button>
                            </div>
                            <small class="text-muted">Maximum <?php echo $tripDetails['availableSeats']; ?> seats available</small>
                        </div>
                    </div>

                    <!-- Promotion Selection Section -->
                    <div class="section-card mb-4">
                        <h5 class="card-title mb-3"><i class="bi bi-stars"></i> Select Promotion or Voucher</h5>
                        <div class="row g-3">
                            <!-- Regular Promotions -->
                            <?php if (!empty($availablePromotions)): ?>
                                <?php foreach ($availablePromotions as $promo): ?>
                                    <div class="col-md-6">
                                        <label class="w-100">
                                            <input class="form-check-input d-none" type="radio" name="promotionID"
                                                id="promo_<?php echo isset($promo['promotionID']) ? $promo['promotionID'] : ''; ?>"
                                                value="<?php echo isset($promo['promotionID']) ? $promo['promotionID'] : ''; ?>"
                                                data-discount="<?php echo isset($promo['discountRate']) ? $promo['discountRate'] : 0; ?>">
                                            <div class="promo-card shadow-sm mb-2 p-3 border border-primary border-2">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-percent fs-3 text-primary me-2"></i>
                                                    <span class="fs-5 fw-bold text-primary"><?php echo $promo['discountRate']; ?>% OFF</span>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="badge bg-primary">Promotion</span>
                                                    <span class="ms-2 text-muted">Valid until: <?php echo date('d M Y', strtotime($promo['expireDate'])); ?></span>
                                                </div>
                                                <div class="text-muted small">Available: <?php echo $promo['promotionQuantity']; ?></div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Redeemed Vouchers -->
                            <?php if (!empty($redeemedVouchers)): ?>
                                <?php foreach ($redeemedVouchers as $voucher): ?>
                                    <div class="col-md-6">
                                        <label class="w-100">
                                            <input class="form-check-input d-none" type="radio" name="promotionID"
                                                id="voucher_<?php echo isset($voucher['promotionID']) ? $voucher['promotionID'] : ''; ?>"
                                                value="<?php echo isset($voucher['promotionID']) ? $voucher['promotionID'] : ''; ?>"
                                                data-discount="<?php echo isset($voucher['discountRate']) ? $voucher['discountRate'] : 0; ?>">
                                            <div class="promo-card shadow-sm mb-2 p-3 border border-success border-2">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-ticket-perforated fs-3 text-success me-2"></i>
                                                    <span class="fs-5 fw-bold text-success"><?php echo isset($voucher['discountRate']) ? $voucher['discountRate'] : 0; ?>% OFF</span>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="badge bg-success">Voucher</span>
                                                    <span class="ms-2 text-muted">Expires: <?php echo isset($voucher['expireDate']) ? date('d M Y', strtotime($voucher['expireDate'])) : '-'; ?></span>
                                                </div>
                                                <div class="text-muted small">Redeemed: <?php echo isset($voucher['redemptionDate']) ? date('d M Y', strtotime($voucher['redemptionDate'])) : '-'; ?></div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- No Promotion Option -->
                            <div class="col-md-6">
                                <label class="w-100">
                                    <input class="form-check-input d-none" type="radio" name="promotionID"
                                        id="no_promotion" value="" checked>
                                    <div class="promo-card shadow-sm mb-2 p-3 border border-secondary border-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-x-circle fs-3 text-secondary me-2"></i>
                                            <span class="fs-5 fw-bold text-secondary">No Promotion</span>
                                        </div>
                                        <div class="text-muted small">Proceed without any discount</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Suggested Merchandise -->
                    <?php if (!empty($suggestedMerchandise)): ?>
                        <div class="section-card">
                            <h5 class="card-title mb-3"><i class="bi bi-bag"></i> Suggested Merchandise</h5>
                            <div class="row">
                                <?php foreach ($suggestedMerchandise as $item): ?>
                                    <div class="col-md-6">
                                        <div class="merch-card">
                                            <div class="merch-image mb-3">
                                                <?php if (!empty($item['merchandiseImage'])): ?>
                                                    <img src="../assets/images/merchandise/<?php echo htmlspecialchars($item['merchandiseImage']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['merchandiseName']); ?>"
                                                         class="img-fluid rounded" 
                                                         style="width: 100%; height: 200px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="no-image rounded" style="width: 100%; height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                        <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['merchandiseName']); ?></h6>
                                                <span class="price-highlight">RM<?php echo number_format($item['merchandisePrice'], 2); ?></span>
                                            </div>
                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($item['merchandiseDescription']); ?></p>
                                            <div class="d-flex align-items-center">
                                                <label class="me-2">Qty:</label>
                                                <div class="input-group" style="width: 120px;">
                                                    <input type="number" min="0" max="<?php echo $item['stockQuantity']; ?>" value="0"
                                                        name="merchandise[<?php echo $item['merchandiseID']; ?>]"
                                                        id="merchandise_<?php echo $item['merchandiseID']; ?>"
                                                        class="form-control text-center">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                            onclick="decreaseMerchandise(<?php echo $item['merchandiseID']; ?>)">-</button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                            onclick="increaseMerchandise(<?php echo $item['merchandiseID']; ?>, <?php echo $item['stockQuantity']; ?>)">+</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Summary and Buttons -->
                <div class="col-md-4">
                    <div class="summary-card">
                        <h5 class="mb-3"><i class="bi bi-receipt"></i> Order Summary</h5>
                        <div id="orderSummary">
                            <!-- JS will fill this -->
                        </div>
                        
                        <div class="d-flex flex-column gap-2 mt-4">
                            <a href="book_trip.php" class="btn btn-light w-100">
                                <i class="bi bi-arrow-left"></i> Back to Trips
                            </a>
                            <button type="submit" name="proceed_payment" class="btn btn-primary w-100">
                                <i class="bi bi-credit-card"></i> Proceed to Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function updateTotal() {
            var seatQty = parseInt(document.querySelector('input[name="seatQuantity"]').value) || 1;
            var tripPrice = parseFloat(<?php echo json_encode($tripDetails['totalAmount']); ?>);

            // Get selected promotion's discount directly from data-discount
            var promoRadio = document.querySelector('input[name="promotionID"]:checked');
            var promoDiscount = 0;
            if (promoRadio && promoRadio.dataset.discount) {
                promoDiscount = parseFloat(promoRadio.dataset.discount) || 0;
            }

            var discountedTripPrice = tripPrice * (1 - promoDiscount / 100);
            var tripTotal = discountedTripPrice * seatQty;

            // Merchandise (make it optional)
            var merchTotal = 0;
            var merchInputs = document.querySelectorAll('input[name^="merchandise["]');
            merchInputs.forEach(function(input) {
                var qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    var price = parseFloat(input.closest('.merch-card').querySelector('.price-highlight').textContent.replace('RM',''));
                    var discountedPrice = price * (1 - promoDiscount / 100);
                    merchTotal += qty * discountedPrice;
                }
            });

            var orderTotal = tripTotal + merchTotal;

            // Update order summary
            document.getElementById('orderSummary').innerHTML = `
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-white">
                        <span>Trip (per seat)</span>
                        <span>RM${discountedTripPrice.toFixed(2)}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-white">
                        <span>Seats</span>
                        <span>${seatQty}</span>
                    </li>
                    ${merchTotal > 0 ? `
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-white">
                        <span>Merchandise</span>
                        <span>RM${merchTotal.toFixed(2)}</span>
                    </li>
                    ` : ''}
                    ${promoDiscount > 0 ? `
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-white">
                        <span>Promotion</span>
                        <span>${promoDiscount}%</span>
                    </li>
                    ` : ''}
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-white fw-bold">
                        <span>Total</span>
                        <span>RM${orderTotal.toFixed(2)}</span>
                    </li>
                </ul>
            `;
        }

        function decreaseSeats() {
            const input = document.getElementById('seatQuantity');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                updateTotal();
            }
        }

        function increaseSeats() {
            const input = document.getElementById('seatQuantity');
            const currentValue = parseInt(input.value);
            const maxSeats = parseInt(input.getAttribute('max'));
            if (currentValue < maxSeats) {
                input.value = currentValue + 1;
                updateTotal();
            }
        }

        function decreaseMerchandise(merchandiseId) {
            const input = document.getElementById('merchandise_' + merchandiseId);
            const currentValue = parseInt(input.value);
            if (currentValue > 0) {
                input.value = currentValue - 1;
                updateTotal();
            }
        }

        function increaseMerchandise(merchandiseId, maxQuantity) {
            const input = document.getElementById('merchandise_' + merchandiseId);
            const currentValue = parseInt(input.value);
            if (currentValue < maxQuantity) {
                input.value = currentValue + 1;
                updateTotal();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateTotal();
            const seatInput = document.getElementById('seatQuantity');
            seatInput.addEventListener('input', function() {
                const maxSeats = parseInt(this.getAttribute('max'));
                if (parseInt(this.value) > maxSeats) {
                    this.value = maxSeats;
                }
                updateTotal();
            });
            
            document.querySelectorAll('input[name^="merchandise["]').forEach(function(input) {
                input.addEventListener('input', function() {
                    const maxQuantity = parseInt(this.getAttribute('max'));
                    if (parseInt(this.value) > maxQuantity) {
                        this.value = maxQuantity;
                    }
                    updateTotal();
                });
            });

            // Promotion radio selection and card highlight
            document.querySelectorAll('input[name="promotionID"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.promo-card').forEach(function(card) {
                        card.classList.remove('selected');
                    });
                    if (radio.checked) {
                        radio.nextElementSibling.classList.add('selected');
                    }
                    updateTotal();
                });
                // On page load, set .selected for the checked one
                if (radio.checked) {
                    radio.nextElementSibling.classList.add('selected');
                }
            });
        });
    </script>
</body>
</html> 