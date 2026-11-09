<?php
// Common Header Template for Car Workshop Appointment Management System

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

// Determine the base relative path depending on whether the current script is inside admin/
$base_path = isset($is_admin_area) && $is_admin_area ? '../' : './';
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = isset($page_title) ? $page_title . " - Car Workshop" : "Car Workshop Appointment Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="<?php echo $base_path; ?>index.php" class="logo-link">
                <span>🔧 Car Workshop</span>
            </a>
            <nav class="site-nav">
                <a href="<?php echo $base_path; ?>index.php" class="nav-link <?php echo ($current_page === 'index.php' && !isset($is_admin_area)) ? 'active' : ''; ?>">Book Appointment</a>
                <a href="<?php echo $base_path; ?>about.php" class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About Us</a>
                <a href="<?php echo $base_path; ?>help.php" class="nav-link <?php echo $current_page === 'help.php' ? 'active' : ''; ?>">Help & Guidelines</a>
                
                <?php if (is_admin_logged_in()): ?>
                    <a href="<?php echo $base_path; ?>admin/dashboard.php" class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
                    <a href="<?php echo $base_path; ?>admin/appointments.php" class="nav-link <?php echo ($current_page === 'appointments.php' || $current_page === 'edit-appointment.php') ? 'active' : ''; ?>">Appointments</a>
                    <a href="<?php echo $base_path; ?>admin/logout.php" class="nav-link admin-btn">Logout (<?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>)</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>admin/login.php" class="nav-link admin-btn <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">Admin Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <?php
        // Render flash notifications if present
        $flash = get_flash_message();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
