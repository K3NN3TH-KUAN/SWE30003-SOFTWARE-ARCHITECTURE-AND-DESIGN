<?php
require_once '../classes/Account.php';
session_start();
$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = new Account();
    $accountName = $_POST['accountName'];
    $phoneNumber = $_POST['phoneNumber'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $success = $account->registerAccount($accountName, $phoneNumber, $password, $email);
    if ($success) {
        $message = "Registration successful! Redirecting to login...";
        // Set a flag for JS redirect
    } else {
        $message = "Registration failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        .register-card {
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
            background: linear-gradient(135deg, #22d3ee 0%, #4ade80 100%);
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
    <?php if ($success): ?>
    <meta http-equiv="refresh" content="2;url=login.php">
    <?php endif; ?>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="col-md-5">
            <div class="register-card">
                <div class="text-center mb-4">
                    <div class="icon-circle">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Sign Up</h2>
                    <p class="text-muted mb-0">Create your ART account</p>
                </div>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                        <?php echo $message; ?>
                        <?php if ($success): ?>
                            <div class="small mt-2">You will be redirected to login page shortly.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!$success): ?>
                <form method="post" class="mb-3">
                    <div class="mb-3">
                        <label for="accountName" class="form-label">Name</label>
                        <input type="text" name="accountName" id="accountName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="phoneNumber" class="form-label">Phone Number</label>
                        <input type="text" name="phoneNumber" id="phoneNumber" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">
                        <i class="bi bi-person-plus"></i> Register
                    </button>
                </form>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <span>Already have an account? </span>
                    <a href="login.php" class="signup-link">Login</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
