<?php
require_once '../classes/Account.php';
$message = "";
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = new Account();
    $user = $account->login($_POST['loginInput'], $_POST['password']);
    if ($user) {
        $_SESSION['user'] = $user;
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "Invalid credentials or inactive account.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
    <h2>Login</h2>
    <?php if ($message) echo "<p style='color:red;'>$message</p>"; ?>
    <form method="post">
        <input type="text" name="loginInput" placeholder="Email, Name, or Phone Number" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    <a href="register.php">Don't have an account? Register</a>
</body>
</html>
