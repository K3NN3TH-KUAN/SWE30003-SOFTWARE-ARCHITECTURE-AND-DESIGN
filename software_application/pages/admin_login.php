<?php
/**
 * Admin Login Page
 * 
 * Handles admin authentication. Displays a login form and processes login attempts.
 */

require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();
session_start();
$message = "";

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = $_POST['loginInput'];
    $password = $_POST['password'];

    $database = new Database();
    $db = $database->getConnection();

    // Allow login by email or name
    $stmt = $db->prepare("SELECT * FROM admin WHERE adminEmail = ? OR adminName = ?");
    $stmt->execute([$loginInput, $loginInput]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['adminPassword'])) {
        $_SESSION['adminID'] = $admin['adminID'];
        $_SESSION['adminName'] = $admin['adminName'];
        $_SESSION['adminRole'] = $admin['adminRole'];
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $message = "Invalid admin credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(99,102,241,0.08);
            margin-top: 5rem;
            padding: 2rem 2rem 1.5rem 2rem;
            background: #fff;
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
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-md-5">
            <div class="login-card">
                <div class="text-center mb-4">
                    <div class="icon-circle">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Admin Login</h2>
                    <p class="text-muted mb-0">Sign in to the ART admin panel</p>
                </div>
                <?php if ($message): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="post" class="mb-3">
                    <div class="mb-3">
                        <label for="loginInput" class="form-label">Email or Name</label>
                        <input type="text" name="loginInput" id="loginInput" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
                <div class="text-center mt-3">
                    <span>Not an admin? </span>
                    <a href="login.php" class="signup-link">Switch to User Login</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 