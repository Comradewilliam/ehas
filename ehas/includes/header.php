<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$app_name = "ELECTRONIC HEALTHCARE APPOINTMENT SYSTEM (EHAS)";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header style="background: #2c3e50; padding: 15px 0; color: #fff; text-align: center;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
            <h1 style="margin: 0; font-size: 1.8em; color: #fff;"><?= htmlspecialchars($app_name) ?></h1>
            <nav>
                <ul style="list-style: none; margin: 0; padding: 0; display: flex;">
                    <li style="margin-left: 25px;"><a href="<?= BASE_URL ?>index.php" style="color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Home</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li style="margin-left: 25px;"><a href="<?= BASE_URL ?>admin/index.php" style="color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Admin Dashboard</a></li>
                        <?php elseif ($_SESSION['role'] === 'doctor'): ?>
                            <li style="margin-left: 25px;"><a href="<?= BASE_URL ?>doctor/index.php" style="color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Doctor Dashboard</a></li>
                        <?php else: // patient ?>
                            <li style="margin-left: 25px;"><a href="<?= BASE_URL ?>patient/index.php" style="color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Patient Dashboard</a></li>
                        <?php endif; ?>
                        <li style="margin-left: 25px;"><a href="<?= BASE_URL ?>logout.php" style="color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Logout</a></li>
                    <?php else: ?>
                        <li style="margin-left: 25px;"><a href="<?= BASE_URL ?>login.php" style="color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Login</a></li>
                        <li style="margin-left: 25px;"><a href="<?= BASE_URL ?>patient/register.php" style="color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main style="padding: 20px;"> 