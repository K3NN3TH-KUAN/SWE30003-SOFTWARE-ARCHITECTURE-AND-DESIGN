<?php
session_start();
require_once '../classes/Database.php';

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = trim($_POST['comment'] ?? '');
    $accountID = $_SESSION['accountID'];
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5.';
    } elseif (empty($comment)) {
        $error = 'Please enter your feedback.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare('INSERT INTO feedback (accountID, rating, comment, feedbackStatus) VALUES (?, ?, ?, ?)');
        $feedbackStatus = 'pending';
        if ($stmt->execute([$accountID, $rating, $comment, $feedbackStatus])) {
            $success = true;
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Feedback - ART Ticketing</title>
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
        .feedback-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-top: 2rem;
            padding: 2rem 2rem 1.5rem 2rem;
            background: #fff;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 2.5rem;
            margin: 0 auto 1rem auto;
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
        }
        .star-rating .bi-star {
            font-size: 2rem;
            color: #222 !important;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating .bi-star-fill.selected {
            font-size: 2rem;
            color: #ffc107 !important;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating .bi-star:hover,
        .star-rating .bi-star:hover ~ .bi-star {
            color: #ffecb3 !important;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-feedback {
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
            font-weight: bold;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            transition: background 0.2s;
        }
        .btn-feedback:hover {
            background: linear-gradient(135deg, #4338ca 0%, #60a5fa 100%);
            color: #fff;
        }
        .alert-success {
            background: #e0f7fa;
            color: #00796b;
            border: none;
            border-radius: 0.5rem;
        }
        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: none;
            border-radius: 0.5rem;
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

    <div class="container">
        <div class="feedback-card">
            <div class="text-center mb-4">
                <div class="icon-circle">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h2 class="fw-bold mb-1">Submit Feedback</h2>
                <p class="text-muted mb-0">We value your feedback on ART online ticketing, route, and trip scheduling.</p>
            </div>
            <?php if ($success): ?>
                <div class="alert alert-success text-center mb-3">
                    <i class="bi bi-check-circle-fill"></i> Thank you for your feedback!
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger text-center mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="post" id="feedbackForm" autocomplete="off">
                <div class="mb-3 text-center">
                    <label class="form-label">Your Rating</label>
                    <div class="star-rating" id="starRating">
                        <input type="hidden" name="rating" id="ratingInput" value="<?php echo isset($_POST['rating']) ? intval($_POST['rating']) : 0; ?>">
                        <?php
                        $currentRating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
                        for ($i = 1; $i <= 5; $i++) {
                            $starClass = $i <= $currentRating ? 'bi-star-fill selected' : 'bi-star';
                            echo '<i class="bi ' . $starClass . '" data-value="' . $i . '"></i>';
                        }
                        ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="comment" class="form-label">Your Feedback</label>
                    <textarea name="comment" id="comment" class="form-control" rows="5" maxlength="1000" required><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-feedback">
                        <i class="bi bi-send"></i> Submit Feedback
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-primary me-2 mt-3 w-100">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Interactive star rating
        document.querySelectorAll('.star-rating .bi').forEach(function(star, idx, stars) {
            star.addEventListener('mouseenter', function() {
                for (let i = 0; i < stars.length; i++) {
                    if (i <= idx) {
                        stars[i].classList.remove('bi-star');
                        stars[i].classList.add('bi-star-fill', 'selected');
                    } else {
                        stars[i].classList.remove('bi-star-fill', 'selected');
                        stars[i].classList.add('bi-star');
                    }
                }
            });
            star.addEventListener('mouseleave', function() {
                let selected = parseInt(document.getElementById('ratingInput').value);
                for (let i = 0; i < stars.length; i++) {
                    if (i < selected) {
                        stars[i].classList.remove('bi-star');
                        stars[i].classList.add('bi-star-fill', 'selected');
                    } else {
                        stars[i].classList.remove('bi-star-fill', 'selected');
                        stars[i].classList.add('bi-star');
                    }
                }
            });
            star.addEventListener('click', function() {
                let value = parseInt(this.getAttribute('data-value'));
                document.getElementById('ratingInput').value = value;
                for (let i = 0; i < stars.length; i++) {
                    if (i < value) {
                        stars[i].classList.remove('bi-star');
                        stars[i].classList.add('bi-star-fill', 'selected');
                    } else {
                        stars[i].classList.remove('bi-star-fill', 'selected');
                        stars[i].classList.add('bi-star');
                    }
                }
            });
        });
        // On page load, set the correct stars
        window.addEventListener('DOMContentLoaded', function() {
            let selected = parseInt(document.getElementById('ratingInput').value);
            let stars = document.querySelectorAll('.star-rating .bi');
            for (let i = 0; i < stars.length; i++) {
                if (i < selected) {
                    stars[i].classList.remove('bi-star');
                    stars[i].classList.add('bi-star-fill', 'selected');
                } else {
                    stars[i].classList.remove('bi-star-fill', 'selected');
                    stars[i].classList.add('bi-star');
                }
            }
        });

        // Add this new code to handle form reset after successful submission
        <?php if ($success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Reset the form
            document.getElementById('feedbackForm').reset();
            
            // Reset the star rating
            document.getElementById('ratingInput').value = '0';
            let stars = document.querySelectorAll('.star-rating .bi');
            stars.forEach(star => {
                star.classList.remove('bi-star-fill', 'selected');
                star.classList.add('bi-star');
            });
            
            // Clear the comment textarea
            document.getElementById('comment').value = '';
        });
        <?php endif; ?>
    </script>
</body>
</html>
