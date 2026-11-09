<?php
// Car Workshop Appointment Management System
// Admin Logout Script

require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all admin authentication keys
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

set_flash_message('info', 'You have been logged out successfully.');
header("Location: login.php");
exit;
