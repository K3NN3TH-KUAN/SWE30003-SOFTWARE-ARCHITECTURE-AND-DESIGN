<?php
// Session check logic goes here
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .admin-dashboard-container {
            max-width: 900px;
            margin: 40px auto;
        }
        .admin-title {
            text-align: center;
            color: #333;
            margin-bottom: 2rem;
            font-weight: bold;
            font-size: 2.2rem;
            letter-spacing: 1px;
        }
        .admin-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            text-align: center;
            padding: 2rem 1rem 1.5rem 1rem;
            background: #fff;
            margin-bottom: 2rem;
        }
        .admin-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 8px 32px rgba(99,102,241,0.18);
        }
        .admin-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #6366f1;
        }
        .admin-link {
            display: block;
            font-size: 1.2rem;
            font-weight: 500;
            color: #6366f1;
            text-decoration: none;
            margin-top: 0.5rem;
            transition: color 0.2s;
        }
        .admin-link:hover {
            color: #4338ca;
            text-decoration: underline;
        }
        @media (min-width: 768px) {
            .admin-dashboard-row {
                display: flex;
                flex-wrap: wrap;
                gap: 2rem;
                justify-content: center;
            }
            .admin-card {
                flex: 1 1 250px;
                min-width: 220px;
                max-width: 260px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard-container">
        <div class="admin-title">Admin Dashboard</div>
        <div class="admin-dashboard-row">
            <div class="admin-card">
                <div class="admin-icon"><i class="bi bi-box-seam"></i></div>
                <div>Merchandise</div>
                <a href="manage_merchandise.php" class="admin-link">Manage Merchandise</a>
            </div>
            <div class="admin-card">
                <div class="admin-icon"><i class="bi bi-megaphone"></i></div>
                <div>Promotion</div>
                <a href="manage_promotion.php" class="admin-link">Manage Promotion</a>
            </div>
            <div class="admin-card">
                <div class="admin-icon"><i class="bi bi-chat-dots"></i></div>
                <div>Feedback</div>
                <a href="views_feedback.php" class="admin-link">View Feedback</a>
            </div>
            <div class="admin-card">
                <div class="admin-icon"><i class="bi bi-graph-up"></i></div>
                <div>Statistic</div>
                <a href="statistic.php" class="admin-link">View Statistic</a>
            </div>
            <div class="admin-card">
                <div class="admin-icon"><i class="bi bi-bus-front"></i></div>
                <div>Trip Management</div>
                <a href="manage_trip.php" class="admin-link">Manage Trips</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
