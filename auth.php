<?php
require_once 'config.php'; 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_actuelle = basename($_SERVER['PHP_SELF']);
if ($page_actuelle !== 'login.php') {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>