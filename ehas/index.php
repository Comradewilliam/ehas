<?php
require_once 'includes/header.php';

// Check if user is logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ' . BASE_URL . 'admin/index.php');
    } elseif ($_SESSION['role'] === 'doctor') {
        header('Location: ' . BASE_URL . 'doctor/index.php');
    } else {
        header('Location: ' . BASE_URL . 'patient/index.php');
    }
    exit();
}
?>
    <h2>Welcome to ELECTRONIC HEALTHCARE APPOINTMENT SYSTEM (EHAS)</h2>
    <p style="text-align: center;">Your one-stop solution for managing health appointments.</p>
    <div style="text-align: center; margin-top: 30px;">
        <a href="<?= BASE_URL ?>login.php" class="button" style="display: inline-block; background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">Login</a>
        <a href="<?= BASE_URL ?>patient/register.php" class="button" style="display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">Register as Patient</a>
    </div>
<?php require_once 'includes/footer.php'; ?>
