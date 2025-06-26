<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}
?> 