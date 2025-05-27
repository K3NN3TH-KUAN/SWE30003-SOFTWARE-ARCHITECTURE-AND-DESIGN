<?php
session_start();
require_once '../classes/Point.php';
require_once '../classes/Promotion.php';

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
    <h2>Dashboard</h2>
    <div class="card"><a href="book_trip.php">Book a Trip</a></div>
    <div class="card"><a href="topup.php">Top-up</a></div>
    <div class="card"><a href="history.php">View History</a></div>
    <div class="card"><a href="notifications.php">Notifications</a></div>
    <div class="card"><a href="points.php">View Points</a></div>
    <div class="card"><a href="merchandise.php">Merchandise</a></div>
    <div class="card"><a href="profile.php">Profile Page</a></div>
    <div class="card"><a href="logout.php">Logout</a></div>
</body>
</html>
