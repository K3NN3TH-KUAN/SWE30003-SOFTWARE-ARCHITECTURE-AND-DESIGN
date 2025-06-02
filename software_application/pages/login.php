<?php
session_start();
require_once '../classes/Database.php';

// First, connect to MySQL without selecting a database
$database = new Database();
$conn = $database->getConnectionWithoutDB();

// Check if database exists
$checkDB = $conn->query("SHOW DATABASES LIKE 'software_app_db'");
if ($checkDB->rowCount() == 0) {
    // If database doesn't exist, run setup
    require_once '../setup_database.php';
} else {
    // If database exists, check if tables exist
    $conn->query("USE software_app_db");
    $checkTable = $conn->query("SHOW TABLES LIKE 'account'");
    if ($checkTable->rowCount() == 0) {
        // If account table doesn't exist, run setup
        require_once '../setup_database.php';
    }
}

require_once '../classes/Account.php';
$message = "";

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = new Account();
    $loginInput = $_POST['loginInput'];
    $password = $_POST['password'];
    $user = $account->login($loginInput, $password);
    if ($user) {
        $_SESSION['user'] = $user;
        $_SESSION['accountID'] = $user['accountID'];
        $_SESSION['accountName'] = $user['accountName'];
        header('Location: dashboard.php');
        exit();
    } else {
        $message = "Invalid login credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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
        .signup-link {
            color: #6366f1;
            font-weight: 500;
            text-decoration: none;
        }
        .signup-link:hover {
            text-decoration: underline;
            color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-md-5">
            <div class="login-card">
                <div class="text-center mb-4">
                    <div class="icon-circle">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Login</h2>
                    <p class="text-muted mb-0">Sign in to your ART account</p>
                </div>
                <?php if ($message): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="post" class="mb-3">
                    <div class="mb-3">
                        <label for="loginInput" class="form-label">Email, Name, or Phone Number</label>
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
                    <span>Doesn't have an account? </span>
                    <a href="register.php" class="signup-link">Sign up</a>
                </div>
                <div class="text-center mt-2">
                    <span>Are you an admin? </span>
                    <a href="admin_login.php" class="signup-link">Switch to Admin Login</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
