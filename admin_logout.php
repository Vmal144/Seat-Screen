<?php
session_start();

// Unset only admin session variables to keep user logged in if they were
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

// Redirect to admin login page
header("Location: admin_login.php");
exit();
?>
