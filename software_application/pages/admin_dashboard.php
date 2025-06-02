<?php
session_start();
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();
if (!isset($_SESSION['adminID'])) {
    header('Location: login.php');
    exit();
}
$adminName = $_SESSION['adminName'] ?? 'Admin';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - ART Ticketing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .dashboard-header {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .dashboard-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            transition: transform 0.15s, box-shadow 0.15s;
            cursor: pointer;
            background: #fff;
        }
        .dashboard-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 6px 24px rgba(99,102,241,0.18);
        }
        .dashboard-icon {
            font-size: 2.5rem;
            color: #6366f1;
            margin-bottom: 0.5rem;
        }
        .dashboard-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #22223b;
        }
        .dashboard-link {
            text-decoration: none;
        }
        .welcome-box {
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-box text-center mt-4">
            <h1 class="mb-2">Welcome, <?= htmlspecialchars($adminName) ?>!</h1>
            <p class="lead mb-0">Manage ART Ticketing System efficiently from your dashboard.</p>
        </div>
        <div class="row g-4 dashboard-header">
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manage_accounts.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-people"></i></div>
                        <div class="dashboard-title">Manage Accounts</div>
                        <div class="text-muted small">View, add, edit, or delete user accounts</div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="admin_review_accounts.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-person-check"></i></div>
                        <div class="dashboard-title">Verify Accounts</div>
                        <div class="text-muted small">Review and verify new user registrations</div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manage_merchandise.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-bag"></i></div>
                        <div class="dashboard-title">Manage Merchandise</div>
                        <div class="text-muted small">Add, edit, or remove merchandise</div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manage_promotion.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-ticket-perforated"></i></div>
                        <div class="dashboard-title">Manage Promotions</div>
                        <div class="text-muted small">Create and manage promotions & vouchers</div>
                    </div>
                </a>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manage_trip.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-bus-front"></i></div>
                        <div class="dashboard-title">Manage Trips</div>
                        <div class="text-muted small">Schedule, edit, or cancel trips</div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="manage_booking.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-calendar-check"></i></div>
                        <div class="dashboard-title">Manage Bookings</div>
                        <div class="text-muted small">View and manage trip bookings</div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="views_feedback.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-chat-dots"></i></div>
                        <div class="dashboard-title">View Feedback</div>
                        <div class="text-muted small">Read and respond to user feedback</div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="statistic.php" class="dashboard-link">
                    <div class="dashboard-card p-4 text-center h-100">
                        <div class="dashboard-icon"><i class="bi bi-bar-chart"></i></div>
                        <div class="dashboard-title">Statistics</div>
                        <div class="text-muted small">View system statistics and reports</div>
                    </div>
                </a>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="logout.php" class="btn btn-outline-danger btn-lg">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
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
