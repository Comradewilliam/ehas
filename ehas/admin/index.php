<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Get total counts for dashboard
$total_doctors = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetch_row()[0];
$total_patients = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetch_row()[0];
$pending_appointments = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetch_row()[0];
$approved_appointments = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'approved'")->fetch_row()[0];
$total_appointments = $pending_appointments + $approved_appointments;

?>
    <h2>Admin Dashboard</h2>
    <p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Total Doctors</h3>
            <p><?= $total_doctors ?></p>
            <a href="<?= BASE_URL ?>admin/manage_doctors.php">Manage Doctors</a>
        </div>
        <div class="stat-card">
            <h3>Total Patients</h3>
            <p><?= $total_patients ?></p>
            <a href="<?= BASE_URL ?>admin/manage_patients.php">Manage Patients</a>
        </div>
        <div class="stat-card">
            <h3>Total Appointments</h3>
            <p><?= $total_appointments ?></p>
            <a href="<?= BASE_URL ?>admin/monitoring.php">View Appointments</a>
        </div>
        <div class="stat-card">
            <h3>Pending Appointments</h3>
            <p><?= $pending_appointments ?></p>
            <a href="<?= BASE_URL ?>admin/monitoring.php?status=pending">Review Pending</a>
        </div>
        <div class="stat-card">
            <h3>Approved Appointments</h3>
            <p><?= $approved_appointments ?></p>
            <a href="<?= BASE_URL ?>admin/monitoring.php?status=approved">View Approved</a>
        </div>
    </div>

    <h3>Quick Links:</h3>
    <ul>
        <li><a href="<?= BASE_URL ?>admin/adminreg.php">Register New Admin</a></li>
        <li><a href="<?= BASE_URL ?>admin/manage_regions.php">Manage Regions</a></li>
        <li><a href="<?= BASE_URL ?>admin/manage_districts.php">Manage Districts</a></li>
        <li><a href="<?= BASE_URL ?>admin/manage_hospitals.php">Manage Hospitals</a></li>
        <li><a href="<?= BASE_URL ?>admin/manage_specialties.php">Manage Specialties</a></li>
        <li><a href="<?= BASE_URL ?>admin/monitoring.php">Monitor Appointments</a></li>
    </ul>
<?php require_once '../includes/footer.php'; ?>