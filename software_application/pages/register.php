<?php
require_once '../classes/Account.php';
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = new Account();
    $success = $account->registerAccount(
        $_POST['accountName'],
        $_POST['phoneNumber'],
        $_POST['password'],
        $_POST['email']
    );
    if ($success) {
        header("Location: login.php");
        exit;
    } else {
        $message = "Registration failed!";
    }
}
// Registration logic goes here
?>
<!DOCTYPE html>
<html>
<head><title>Register</title></head>
<body>
    <h2>Register</h2>
    <?php if ($message) echo "<p style='color:red;'>$message</p>"; ?>
    <form method="post">
        <input type="text" name="accountName" placeholder="Name" required><br>
        <input type="text" name="phoneNumber" placeholder="Phone Number" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Register</button>
    </form>
    <a href="login.php">Already have an account? Login</a>
</body>
</html>
