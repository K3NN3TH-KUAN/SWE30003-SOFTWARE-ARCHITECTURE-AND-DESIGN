<?php
// Start the session to clear user data
session_start();
// Destroy all session data to log out the user
session_destroy();
// Redirect to login page after logout
header('Location: login.php');
exit();
?>
