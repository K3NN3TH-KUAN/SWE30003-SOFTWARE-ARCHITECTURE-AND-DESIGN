<?php
session_start();
require_once '../classes/Database.php';
require_once '../classes/Feedback.php';
require_once '../classes/Notification.php';

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    header("Location: admin_login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$feedback = new Feedback($db);
$notification = new Notification($db);

// Handle mark as resolved action
if (isset($_POST['mark_resolved']) && isset($_POST['feedback_id'])) {
    $feedbackID = $_POST['feedback_id'];
    $adminID = $_SESSION['adminID'];
    
    // Update feedback status
    $updateQuery = "UPDATE feedback SET feedbackStatus = 'resolved', adminID = ? WHERE feedbackID = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$adminID, $feedbackID]);
    
    // Get feedback details for notification
    $feedbackQuery = "SELECT f.*, a.accountID, a.accountName 
                     FROM feedback f 
                     JOIN account a ON f.accountID = a.accountID 
                     WHERE f.feedbackID = ?";
    $feedbackStmt = $db->prepare($feedbackQuery);
    $feedbackStmt->execute([$feedbackID]);
    $feedbackDetails = $feedbackStmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notification for user
    $notificationMessage = "Your feedback has been reviewed and resolved. Thank you for your input!";
    $notification->createNotification(
        $feedbackDetails['accountID'],
        $notificationMessage,
        'feedback'
    );
    
    // Redirect to refresh the page
    header("Location: views_feedback.php");
    exit();
}

// Get all feedback with account names
$query = "SELECT f.*, a.accountName, a.email 
          FROM feedback f 
          JOIN account a ON f.accountID = a.accountID 
          ORDER BY f.feedbackID DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Feedback - ART Kuching</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .feedback-card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }
        .feedback-card:hover {
            transform: translateY(-5px);
        }
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.875rem;
        }
        .status-resolved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background-color: #fef08a;
            color: #92400e;
        }
        .user-info {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }
        .comment-box {
            background-color: #f1f5f9;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem 0;
        }
        .action-buttons {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .btn-resolve {
            background-color: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }
        .btn-resolve:hover {
            background-color: #059669;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0 fw-bold">Customer Feedback</h1>
            <div>
                <a href="admin_dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="row">
            <?php foreach ($feedbacks as $feedback): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card feedback-card">
                        <div class="card-body">
                            <!-- User Information -->
                            <div class="user-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-person-circle"></i> 
                                        <?= htmlspecialchars($feedback['accountName']) ?>
                                    </h5>
                                    <span class="status-badge status-<?= $feedback['feedbackStatus'] ?>">
                                        <?= ucfirst($feedback['feedbackStatus']) ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($feedback['email']) ?>
                                </small>
                            </div>

                            <!-- Rating -->
                            <div class="rating-stars mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?= $i <= $feedback['rating'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                                <span class="ms-2 text-muted">(<?= $feedback['rating'] ?>/5)</span>
                            </div>

                            <!-- Comment -->
                            <div class="comment-box">
                                <p class="card-text mb-0">
                                    <i class="bi bi-chat-quote"></i>
                                    <?= htmlspecialchars($feedback['comment']) ?>
                                </p>
                            </div>

                            <!-- Feedback ID and Date -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-hash"></i> Feedback #<?= $feedback['feedbackID'] ?>
                                </small>
                                <?php if ($feedback['adminID']): ?>
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i> Reviewed by Admin
                                    </small>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <?php if ($feedback['feedbackStatus'] === 'pending'): ?>
                                <div class="action-buttons">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="feedback_id" value="<?= $feedback['feedbackID'] ?>">
                                        <button type="submit" name="mark_resolved" class="btn btn-resolve">
                                            <i class="bi bi-check-circle"></i> Mark as Resolved
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
