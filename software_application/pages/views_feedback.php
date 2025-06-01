<?php
session_start();
require_once '../classes/Database.php';

// Admin authentication can be added here

$database = new Database();
$db = $database->getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedbackID'])) {
    $feedbackID = $_POST['feedbackID'];
    if (isset($_POST['resolve'])) {
        $stmt = $db->prepare('UPDATE feedback SET feedbackStatus = ? WHERE feedbackID = ?');
        $stmt->execute(['resolved', $feedbackID]);
    }
    header('Location: views_feedback.php');
    exit();
}

// Fetch all feedback with user info
$stmt = $db->query('SELECT f.feedbackID, f.accountID, a.accountName, f.rating, f.comment, f.feedbackStatus FROM feedback f JOIN account a ON f.accountID = a.accountID ORDER BY f.feedbackID DESC');
$feedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - View Feedback</title>
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
        .nav-link {
            color: #fff !important;
            font-size: 1.3rem;
        }
        .feedback-table-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-top: 2rem;
            padding: 2rem 2rem 1.5rem 2rem;
            background: #fff;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }
        .table thead th {
            background: #6366f1;
            color: #fff;
            font-weight: 600;
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.4em 1em;
            border-radius: 0.5em;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        .btn-resolve {
            background: linear-gradient(135deg, #22d3ee 0%, #4ade80 100%);
            color: #fff;
            font-weight: bold;
            border: none;
            border-radius: 0.5rem;
            padding: 0.4rem 1.2rem;
            transition: background 0.2s;
        }
        .btn-resolve:hover {
            background: linear-gradient(135deg, #0ea5e9 0%, #22d3ee 100%);
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <img src="../assets/images/logo.png" alt="ART Logo" style="height:32px;vertical-align:middle;margin-right:8px;">
                ART Admin
            </a>
        </div>
    </nav> -->
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
            <h2 class="fw-bold mb-0"> User Feedback</h2>
            <a href="admin_dashboard.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
        </div>
        <div class="feedback-table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">User</th>
                            <th scope="col">Rating</th>
                            <th scope="col">Comment</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($feedbackList) === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted">No feedback found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($feedbackList as $idx => $fb): ?>
                                <tr>
                                    <th scope="row"><?php echo $idx + 1; ?></th>
                                    <td><?php echo htmlspecialchars($fb['accountName']); ?> (ID: <?php echo $fb['accountID']; ?>)</td>
                                    <td>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $fb['rating']): ?>
                                                <i class="bi bi-star-fill" style="color:#ffc107;"></i>
                                            <?php else: ?>
                                                <i class="bi bi-star" style="color:#222;"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($fb['comment'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $fb['feedbackStatus'] === 'resolved' ? 'status-resolved' : 'status-pending'; ?>">
                                            <?php echo ucfirst($fb['feedbackStatus']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($fb['feedbackStatus'] === 'pending'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="feedbackID" value="<?php echo $fb['feedbackID']; ?>">
                                                <button type="submit" name="resolve" class="btn btn-resolve btn-sm"><i class="bi bi-check-circle"></i> Mark as Resolved</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-success"><i class="bi bi-check2-circle"></i> Resolved</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
