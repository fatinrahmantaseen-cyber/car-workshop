<?php
// Common reusable helper functions for Car Workshop Appointment Management System

// Maximum active appointments allowed per mechanic per day
define('MAX_DAILY_APPOINTMENTS', 4);

/**
 * Sanitize user input to prevent XSS and clean whitespace.
 */
function sanitize_input($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Fetch all active senior mechanics from the database.
 */
function get_active_mechanics($pdo) {
    $stmt = $pdo->prepare("SELECT id, name, phone FROM mechanics WHERE is_active = 1 ORDER BY id ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Count active appointments for a specific mechanic on a specific date.
 * Completed and cancelled appointments do not count toward the limit.
 */
function get_mechanic_active_count($pdo, $mechanic_id, $date) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS active_count FROM appointments WHERE mechanic_id = :mechanic_id AND appointment_date = :date AND status = 'active'");
    $stmt->execute([
        ':mechanic_id' => $mechanic_id,
        ':date'        => $date
    ]);
    $row = $stmt->fetch();
    return (int) ($row['active_count'] ?? 0);
}

/**
 * Calculate remaining available appointment slots for a mechanic on a specific date.
 */
function get_mechanic_available_slots($pdo, $mechanic_id, $date) {
    $active_count = get_mechanic_active_count($pdo, $mechanic_id, $date);
    $available = MAX_DAILY_APPOINTMENTS - $active_count;
    return max(0, $available);
}

/**
 * Retrieve all active mechanics along with their active count and available slots for a given date.
 */
function get_all_mechanics_with_slots($pdo, $date) {
    $mechanics = get_active_mechanics($pdo);
    $result = [];

    foreach ($mechanics as $mechanic) {
        $active_count = get_mechanic_active_count($pdo, $mechanic['id'], $date);
        $available_slots = max(0, MAX_DAILY_APPOINTMENTS - $active_count);

        $result[] = [
            'id'              => (int) $mechanic['id'],
            'name'            => $mechanic['name'],
            'phone'           => $mechanic['phone'],
            'active_count'    => $active_count,
            'available_slots' => $available_slots,
            'is_full'         => ($available_slots === 0)
        ];
    }

    return $result;
}

/**
 * Check if a client phone number already has an active appointment on a specific date.
 * A client cannot have two active appointments on the same date, even with different mechanics.
 */
function client_has_active_appointment($pdo, $phone, $date, $exclude_appointment_id = null) {
    $sql = "SELECT COUNT(*) AS total FROM appointments WHERE phone = :phone AND appointment_date = :date AND status = 'active'";
    $params = [
        ':phone' => $phone,
        ':date'  => $date
    ];

    // Optional exclusion for editing existing appointments in admin panel
    if ($exclude_appointment_id !== null) {
        $sql .= " AND id != :exclude_id";
        $params[':exclude_id'] = $exclude_appointment_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return ((int) ($row['total'] ?? 0)) > 0;
}

/**
 * Set a flash message in session for user feedback across redirects.
 */
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type'    => $type, // 'success', 'error', 'info', or 'warning'
        'message' => $message
    ];
}

/**
 * Retrieve and clear the flash message from session.
 */
function get_flash_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Store submitted form input in session to preserve values on validation error.
 */
function set_form_old_data($data) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['old_form_data'] = $data;
}

/**
 * Retrieve and clear previously submitted form input.
 */
function get_form_old_data() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['old_form_data'])) {
        $data = $_SESSION['old_form_data'];
        unset($_SESSION['old_form_data']);
        return $data;
    }
    return [];
}

/**
 * Check if an admin is currently logged in.
 */
function is_admin_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin authentication; redirect to login page if not logged in.
 */
function require_admin_login($redirect_to = 'login.php') {
    if (!is_admin_logged_in()) {
        set_flash_message('error', 'Please log in to access the admin area.');
        header("Location: $redirect_to");
        exit;
    }
}

/**
 * Generate or retrieve a CSRF token for form security.
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token.
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['csrf_token']) && !empty($token)) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    return false;
}
