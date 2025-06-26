<?php
session_start();
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
$lang_file = __DIR__ . '/../lang/' . $lang_code . '.php';
if (!file_exists($lang_file)) $lang_file = __DIR__ . '/../lang/en.php';
$lang = include $lang_file;
?> 