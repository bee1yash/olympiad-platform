<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

switch ($_SESSION['role']) {
    case 'admin':
        header("Location: admin/index.php");
        exit;
    case 'specialist':
        header("Location: specialist/index.php");
        exit;
    case 'student':
    default:
        header("Location: student/index.php");
        exit;
}
?>