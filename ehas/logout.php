<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/header.php'; // Include the header

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to login page
header('Location: ' . BASE_URL . 'login.php');
exit;
?>
    <h2>You have been logged out.</h2>
    <p style="text-align: center;">Thank you for using ELECTRONIC HEALTHCARE APPOINTMENT SYSTEM (EHAS).</p>
    <p style="text-align: center;"><a href="<?= BASE_URL ?>login.php">Click here to login again.</a></p>
<?php require_once 'includes/footer.php'; ?> 