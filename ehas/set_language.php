<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'sw'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$ref = $_SERVER['HTTP_REFERER'] ?? '/';
header('Location: ' . $ref);
exit();
?> 