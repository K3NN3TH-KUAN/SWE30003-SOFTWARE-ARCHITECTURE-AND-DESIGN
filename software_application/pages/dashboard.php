<?php
session_start();
require_once '../classes/Point.php';
require_once '../classes/Promotion.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$accountID = $_SESSION['accountID'];
$point = new Point();
$pointInfo = $point->getPointByAccountID($accountID);
$pointBalance = $pointInfo ? $pointInfo['pointBalance'] : 0;

$accountName = isset($_SESSION['accountName']) ? $_SESSION['accountName'] : 'User';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .navbar-custom {
            background: #6366f1;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: #fff !important;
            letter-spacing: 1px;
        }
        .nav-link, .bi {
            color: #fff !important;
            font-size: 1.3rem;
        }
        .dashboard-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 8px 32px rgba(99,102,241,0.18);
        }
        .icon-circle {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .bg-gradient-blue {
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
        }
        .bg-gradient-green {
            background: linear-gradient(135deg, #22d3ee 0%, #4ade80 100%);
            color: #fff;
        }
        .bg-gradient-orange {
            background: linear-gradient(135deg, #fbbf24 0%, #f87171 100%);
            color: #fff;
        }
        .bg-gradient-purple {
            background: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);
            color: #fff;
        }
        .bg-gradient-pink {
            background: linear-gradient(135deg, #f472b6 0%, #fbbf24 100%);
            color: #fff;
        }
        .mt-section {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/images/logo.png" alt="ART Logo" style="height:32px;vertical-align:middle;margin-right:8px;">
                ART Ticketing
            </a>
            <div class="ms-auto d-flex align-items-center">
                <a class="nav-link me-3" href="cart.php" title="View Cart">
                    <i class="bi bi-cart3"></i>
                </a>
                <a class="nav-link me-3" href="notifications.php" title="Notifications">
                    <i class="bi bi-bell"></i>
                </a>
                <a class="nav-link me-3" href="feedback.php" title="Feedback">
                    <i class="bi bi-chat-dots"></i>
                </a>
                <a class="nav-link" href="profile.php" title="Profile">
                    <i class="bi bi-person-circle"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Row 1: Account Balance, Points -->
        <div class="row g-4 mb-4">
            <!-- Account Balance -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-circle bg-gradient-blue me-3">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Account Balance</h5>
                            <p class="display-6 fw-bold text-success mb-1">
                                RM<?php
                                require_once '../classes/Account.php';
                                $account = new Account();
                                $info = $account->getAccountByID($_SESSION['accountID']);
                                echo isset($info['accountBalance']) ? number_format($info['accountBalance'], 2) : '0.00';
                                ?>
                            </p>
                            <a href="topup.php" class="btn btn-outline-primary btn-sm">Top Up</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Points -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-circle bg-gradient-pink me-3">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Points</h5>
                            <p class="display-6 fw-bold text-warning mb-1">
                                <?php echo $pointBalance; ?> pts
                            </p>
                            <a href="points.php" class="btn btn-outline-warning btn-sm">View Points</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Promotions, Purchase History -->
        <div class="row g-4 mb-4">
            <!-- Promotions -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-circle bg-gradient-orange me-3">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Promotions</h5>
                            <p class="mb-1">Check out the latest promotions and your vouchers.</p>
                            <a href="promotion.php" class="btn btn-outline-warning btn-sm">View Promotions</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Purchase History -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="icon-circle bg-gradient-purple me-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Purchase History</h5>
                            <p class="mb-1">See your past ticket and merchandise purchases.</p>
                            <a href="history.php" class="btn btn-outline-dark btn-sm">View History</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3: Book a Trip & Merchandise -->
        <div class="row g-4 mt-section">
            <div class="col-md-6">
                <div class="card dashboard-card text-center">
                    <div class="card-body">
                        <div class="icon-circle bg-gradient-purple mb-2 mx-auto">
                            <i class="bi bi-bus-front"></i>
                        </div>
                        <h5 class="card-title">Book a Trip</h5>
                        <p class="card-text">Plan and book your next ART journey with ease.</p>
                        <a href="book_trip.php" class="btn btn-outline-primary btn-sm">Book Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card dashboard-card text-center">
                    <div class="card-body">
                        <div class="icon-circle bg-gradient-green mb-2 mx-auto">
                            <i class="bi bi-bag"></i>
                        </div>
                        <h5 class="card-title">Merchandise</h5>
                        <p class="card-text">Browse and purchase ART merchandise.</p>
                        <a href="merchandise.php" class="btn btn-outline-success btn-sm">Shop Now</a>
                    </div>
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