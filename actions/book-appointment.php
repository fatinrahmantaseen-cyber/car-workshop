<?php
// Car Workshop Appointment Management System
// actions/book-appointment.php
// Handles appointment booking submission with server-side validation and PDO transactions

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// --------------------------------------------------------------------------
// 1. Retrieve and Sanitize Form Inputs
// --------------------------------------------------------------------------
$client_name      = sanitize_input($_POST['client_name'] ?? '');
$address          = sanitize_input($_POST['address'] ?? '');
$phone            = sanitize_input($_POST['phone'] ?? '');
$car_license      = sanitize_input($_POST['car_license'] ?? '');
$car_engine       = sanitize_input($_POST['car_engine'] ?? '');
$appointment_date = sanitize_input($_POST['appointment_date'] ?? '');
$mechanic_id      = isset($_POST['mechanic_id']) ? (int) $_POST['mechanic_id'] : 0;

// Save submitted inputs so user does not have to re-type on validation error
set_form_old_data([
    'client_name'      => $client_name,
    'address'          => $address,
    'phone'            => $phone,
    'car_license'      => $car_license,
    'car_engine'       => $car_engine,
    'appointment_date' => $appointment_date,
    'mechanic_id'      => $mechanic_id
]);

// --------------------------------------------------------------------------
// 2. Server-Side Input Validation
// --------------------------------------------------------------------------

// Check required fields are not empty
if (empty($client_name) || empty($address) || empty($phone) || empty($car_license) || empty($car_engine) || empty($appointment_date) || empty($mechanic_id)) {
    set_flash_message('error', 'All fields are mandatory. Please fill in all the required details.');
    header("Location: ../index.php#booking-section");
    exit;
}

// Validate client name (letters, spaces, periods, hyphens)
if (!preg_match("/^[a-zA-Z\s\.\-']{2,100}$/", $client_name)) {
    set_flash_message('error', 'Please enter a valid client name containing letters and standard punctuation.');
    header("Location: ../index.php#booking-section");
    exit;
}

// Validate phone number (digits only, length 7 to 15 per assignment rule)
if (!preg_match('/^[0-9]{7,15}$/', $phone)) {
    set_flash_message('error', 'Phone number must contain numbers only (between 7 and 15 digits).');
    header("Location: ../index.php#booking-section");
    exit;
}

// Validate car engine number (numbers only per course instructions)
if (!preg_match('/^[0-9]+$/', $car_engine)) {
    set_flash_message('error', 'Car engine number must contain numbers only.');
    header("Location: ../index.php#booking-section");
    exit;
}

// Validate appointment date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
    set_flash_message('error', 'Please provide a valid appointment date.');
    header("Location: ../index.php#booking-section");
    exit;
}

// Prevent selecting past dates
$today = date('Y-m-d');
if ($appointment_date < $today) {
    set_flash_message('error', 'Appointment date cannot be in the past. Please select today or a future date.');
    header("Location: ../index.php#booking-section");
    exit;
}

// --------------------------------------------------------------------------
// 3. Database Transaction and Concurrency Control
// --------------------------------------------------------------------------
try {
    // Begin PDO transaction
    $pdo->beginTransaction();

    // Lock ALL active mechanic rows in ascending ID order using SELECT ... FOR UPDATE.
    // This establishes a strict global serialization order across all booking transactions,
    // protecting BOTH the 4-car-per-mechanic limit AND the 1-appointment-per-phone rule
    // (even when two concurrent requests choose completely different mechanics).
    // Because all transactions lock the mechanics in the exact same ascending order, deadlocks are impossible.
    $stmt_lock = $pdo->query("SELECT id, name, is_active FROM mechanics WHERE is_active = 1 ORDER BY id ASC FOR UPDATE");
    $all_active_mechanics = $stmt_lock->fetchAll();

    // Verify that the chosen mechanic exists and is in the active list
    $selected_mechanic = null;
    foreach ($all_active_mechanics as $m) {
        if ((int) $m['id'] === $mechanic_id) {
            $selected_mechanic = $m;
            break;
        }
    }

    if (!$selected_mechanic) {
        $pdo->rollBack();
        set_flash_message('error', 'The selected senior mechanic does not exist or is currently inactive.');
        header("Location: ../index.php#booking-section");
        exit;
    }

    $mechanic_name = $selected_mechanic['name'];

    // TASK 3: Check Duplicate Booking for Client Phone
    // Rule: A client must not have more than one ACTIVE appointment on the same date,
    // regardless of which mechanic was selected.
    $stmt_client = $pdo->prepare("
        SELECT COUNT(*) AS active_client_count 
        FROM appointments 
        WHERE phone = :phone 
          AND appointment_date = :date 
          AND status = 'active'
    ");
    $stmt_client->execute([
        ':phone' => $phone,
        ':date'  => $appointment_date
    ]);
    $client_count = (int) $stmt_client->fetch()['active_client_count'];

    if ($client_count > 0) {
        $pdo->rollBack();
        set_flash_message('error', "Booking Rejected: Client with phone {$phone} already has an active appointment on {$appointment_date}. A client cannot hold more than one active appointment on the same date.");
        header("Location: ../index.php#booking-section");
        exit;
    }

    // TASK 4: Check Mechanic Daily Capacity (Max 4 active appointments per date)
    // Rule: Completed and cancelled appointments do not count toward the limit.
    $stmt_capacity = $pdo->prepare("
        SELECT COUNT(*) AS active_mech_count 
        FROM appointments 
        WHERE mechanic_id = :mechanic_id 
          AND appointment_date = :date 
          AND status = 'active'
    ");
    $stmt_capacity->execute([
        ':mechanic_id' => $mechanic_id,
        ':date'        => $appointment_date
    ]);
    $active_mech_count = (int) $stmt_capacity->fetch()['active_mech_count'];

    if ($active_mech_count >= MAX_DAILY_APPOINTMENTS) {
        $pdo->rollBack();
        set_flash_message('error', "Booking Rejected: {$mechanic_name} is already fully booked with 4 active appointments on {$appointment_date}. Please choose another available mechanic or pick a different date.");
        header("Location: ../index.php#booking-section");
        exit;
    }

    // TASK 5: Insert the Appointment with status 'active'
    $stmt_insert = $pdo->prepare("
        INSERT INTO appointments (client_name, address, phone, car_license, car_engine, appointment_date, mechanic_id, status)
        VALUES (:client_name, :address, :phone, :car_license, :car_engine, :appointment_date, :mechanic_id, 'active')
    ");

    $stmt_insert->execute([
        ':client_name'      => $client_name,
        ':address'          => $address,
        ':phone'            => $phone,
        ':car_license'      => $car_license,
        ':car_engine'       => $car_engine,
        ':appointment_date' => $appointment_date,
        ':mechanic_id'      => $mechanic_id
    ]);

    // Commit transaction
    $pdo->commit();

    // Clear old form data since booking succeeded
    get_form_old_data();

    // Set success feedback
    $success_msg = "Appointment successfully confirmed! Your appointment with {$mechanic_name} is scheduled for {$appointment_date}.";
    set_flash_message('success', $success_msg);

    // Redirect back to homepage
    header("Location: ../index.php#booking-section");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Appointment booking transaction failed: " . $e->getMessage());
    set_flash_message('error', 'A system error occurred while processing your appointment. Please try again.');
    header("Location: ../index.php#booking-section");
    exit;
}
