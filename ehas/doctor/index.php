<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$doctor_name = htmlspecialchars($_SESSION['name']);
?>
    <h2>Welcome, Doctor <?= $doctor_name ?>!</h2>
    <p style="text-align: center;">This is your doctor dashboard.</p>

    <div style="text-align: center; margin-top: 30px;">
        <ul style="list-style: none; padding: 0; display: inline-block; text-align: left;">
            <li style="margin-bottom: 10px;"><a href="<?= BASE_URL ?>doctor/appointments.php" style="text-decoration: none; color: #007bff; font-weight: 600;">Manage Appointments</a></li>
            <li style="margin-bottom: 10px;"><a href="<?= BASE_URL ?>doctor/profile.php" style="text-decoration: none; color: #007bff; font-weight: 600;">Update Profile</a></li>
            <li style="margin-bottom: 10px;"><a href="<?= BASE_URL ?>logout.php" style="text-decoration: none; color: #dc3545; font-weight: 600;">Logout</a></li>
        </ul>
    </div>
<?php require_once '../includes/footer.php'; ?>