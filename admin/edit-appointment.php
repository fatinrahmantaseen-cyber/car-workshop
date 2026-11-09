<?php
// Car Workshop Appointment Management System
// Admin Edit & Reschedule Appointment

$is_admin_area = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login('login.php');

$appointment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($appointment_id <= 0) {
    set_flash_message('error', 'Invalid appointment ID.');
    header("Location: appointments.php");
    exit;
}

// --------------------------------------------------------------------------
// 1. Fetch Existing Appointment Details
// --------------------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT a.*, m.name AS mechanic_name 
        FROM appointments a
        JOIN mechanics m ON a.mechanic_id = m.id
        WHERE a.id = :id
    ");
    $stmt->execute([':id' => $appointment_id]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        set_flash_message('error', 'Appointment not found.');
        header("Location: appointments.php");
        exit;
    }

    // Fetch all mechanics for the selection dropdown
    $all_mechanics_stmt = $pdo->query("SELECT id, name, phone, is_active FROM mechanics ORDER BY id ASC");
    $all_mechanics = $all_mechanics_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Failed to fetch appointment: " . $e->getMessage());
    set_flash_message('error', 'Database error loading appointment.');
    header("Location: appointments.php");
    exit;
}

// --------------------------------------------------------------------------
// 2. Handle Form Submission (Update & Reschedule)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        set_flash_message('error', 'Security token expired. Please try again.');
        header("Location: edit-appointment.php?id=" . $appointment_id);
        exit;
    }

    // Sanitize and retrieve form inputs
    $client_name      = sanitize_input($_POST['client_name'] ?? '');
    $address          = sanitize_input($_POST['address'] ?? '');
    $phone            = sanitize_input($_POST['phone'] ?? '');
    $car_license      = sanitize_input($_POST['car_license'] ?? '');
    $car_engine       = sanitize_input($_POST['car_engine'] ?? '');
    $appointment_date = sanitize_input($_POST['appointment_date'] ?? '');
    $mechanic_id      = (int) ($_POST['mechanic_id'] ?? 0);
    $status           = sanitize_input($_POST['status'] ?? 'active');

    // Server-side validation
    if (empty($client_name) || empty($address) || empty($phone) || empty($car_license) || empty($car_engine) || empty($appointment_date) || $mechanic_id <= 0) {
        set_flash_message('error', 'All fields are required.');
        header("Location: edit-appointment.php?id=" . $appointment_id);
        exit;
    }

    if (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        set_flash_message('error', 'Phone number must contain between 7 and 15 digits.');
        header("Location: edit-appointment.php?id=" . $appointment_id);
        exit;
    }

    if (!preg_match('/^[0-9]+$/', $car_engine)) {
        set_flash_message('error', 'Car engine number must contain numbers only.');
        header("Location: edit-appointment.php?id=" . $appointment_id);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
        set_flash_message('error', 'Invalid date format.');
        header("Location: edit-appointment.php?id=" . $appointment_id);
        exit;
    }

    if (!in_array($status, ['active', 'completed', 'cancelled'], true)) {
        set_flash_message('error', 'Invalid appointment status selected.');
        header("Location: edit-appointment.php?id=" . $appointment_id);
        exit;
    }

    try {
        // If status is 'active', enforce concurrency locking, duplicate check, and 4-car mechanic limit
        if ($status === 'active') {
            // Begin database transaction
            $pdo->beginTransaction();

            // REQUIREMENT 8: Global mechanic row locking in ascending ID order
            $stmt_lock = $pdo->query("SELECT id, name, is_active FROM mechanics WHERE is_active = 1 ORDER BY id ASC FOR UPDATE");
            $active_mechanics = $stmt_lock->fetchAll();

            // REQUIREMENT 11: Do not allow an inactive mechanic to receive an active appointment
            $selected_mechanic = null;
            foreach ($active_mechanics as $m) {
                if ((int) $m['id'] === $mechanic_id) {
                    $selected_mechanic = $m;
                    break;
                }
            }

            if (!$selected_mechanic) {
                $pdo->rollBack();
                set_flash_message('error', 'Cannot assign appointment: the selected mechanic is inactive or does not exist.');
                header("Location: edit-appointment.php?id=" . $appointment_id);
                exit;
            }

            $mechanic_name = $selected_mechanic['name'];

            // REQUIREMENT 9: Recheck duplicate active appointment for the phone & date, excluding this appointment
            $stmt_client = $pdo->prepare("
                SELECT COUNT(*) AS active_client_count 
                FROM appointments 
                WHERE phone = :phone 
                  AND appointment_date = :date 
                  AND status = 'active'
                  AND id != :current_id
            ");
            $stmt_client->execute([
                ':phone'      => $phone,
                ':date'       => $appointment_date,
                ':current_id' => $appointment_id
            ]);
            $client_count = (int) $stmt_client->fetch()['active_client_count'];

            if ($client_count > 0) {
                $pdo->rollBack();
                set_flash_message('error', "Rescheduling Rejected: Client with phone {$phone} already has another active appointment on {$appointment_date}.");
                header("Location: edit-appointment.php?id=" . $appointment_id);
                exit;
            }

            // REQUIREMENT 10: Recheck maximum 4 active appointments per mechanic per date, excluding this appointment
            $stmt_capacity = $pdo->prepare("
                SELECT COUNT(*) AS active_mech_count 
                FROM appointments 
                WHERE mechanic_id = :mechanic_id 
                  AND appointment_date = :date 
                  AND status = 'active'
                  AND id != :current_id
            ");
            $stmt_capacity->execute([
                ':mechanic_id' => $mechanic_id,
                ':date'        => $appointment_date,
                ':current_id'  => $appointment_id
            ]);
            $active_mech_count = (int) $stmt_capacity->fetch()['active_mech_count'];

            if ($active_mech_count >= MAX_DAILY_APPOINTMENTS) {
                $pdo->rollBack();
                set_flash_message('error', "Rescheduling Rejected: {$mechanic_name} already has 4 active appointments on {$appointment_date}. Please choose another available mechanic or date.");
                header("Location: edit-appointment.php?id=" . $appointment_id);
                exit;
            }

            // Update appointment record
            $stmt_update = $pdo->prepare("
                UPDATE appointments 
                SET client_name = :client_name,
                    address = :address,
                    phone = :phone,
                    car_license = :car_license,
                    car_engine = :car_engine,
                    appointment_date = :appointment_date,
                    mechanic_id = :mechanic_id,
                    status = :status
                WHERE id = :id
            ");
            $stmt_update->execute([
                ':client_name'      => $client_name,
                ':address'          => $address,
                ':phone'            => $phone,
                ':car_license'      => $car_license,
                ':car_engine'       => $car_engine,
                ':appointment_date' => $appointment_date,
                ':mechanic_id'      => $mechanic_id,
                ':status'           => 'active',
                ':id'               => $appointment_id
            ]);

            $pdo->commit();

            set_flash_message('success', "Appointment #{$appointment_id} updated and confirmed for {$appointment_date} with {$mechanic_name}.");
            header("Location: appointments.php");
            exit;

        } else {
            // Updating status to 'completed' or 'cancelled' does not occupy a slot
            $stmt_update = $pdo->prepare("
                UPDATE appointments 
                SET client_name = :client_name,
                    address = :address,
                    phone = :phone,
                    car_license = :car_license,
                    car_engine = :car_engine,
                    appointment_date = :appointment_date,
                    mechanic_id = :mechanic_id,
                    status = :status
                WHERE id = :id
            ");
            $stmt_update->execute([
                ':client_name'      => $client_name,
                ':address'          => $address,
                ':phone'            => $phone,
                ':car_license'      => $car_license,
                ':car_engine'       => $car_engine,
                ':appointment_date' => $appointment_date,
                ':mechanic_id'      => $mechanic_id,
                ':status'           => $status,
                ':id'               => $appointment_id
            ]);

            set_flash_message('success', "Appointment #{$appointment_id} updated to " . ucfirst($status) . ".");
            header("Location: appointments.php");
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Failed to update appointment: " . $e->getMessage());
        set_flash_message('error', 'A system error occurred while updating the appointment.');
        header("Location: edit-appointment.php?id=" . $appointment_id);
        exit;
    }
}

$page_title = "Edit Appointment #" . $appointment_id;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header-bar">
    <div>
        <h1>Edit Appointment #<?php echo (int) $appointment['id']; ?></h1>
        <p style="color: var(--text-muted);">Modify client details, change appointment date, reassign mechanic, or update status.</p>
    </div>
    <div>
        <a href="appointments.php" class="btn btn-secondary">&larr; Back to Appointments</a>
    </div>
</div>

<div class="card" style="max-width: 820px; margin: 0 auto;">
    <form method="POST" action="edit-appointment.php?id=<?php echo (int) $appointment['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

        <div class="form-grid">
            
            <!-- Client Name -->
            <div class="form-group">
                <label for="client_name" class="form-label">Client Name <span class="required">*</span></label>
                <input type="text" id="client_name" name="client_name" class="form-control" value="<?php echo htmlspecialchars($appointment['client_name']); ?>" required>
            </div>

            <!-- Phone Number -->
            <div class="form-group">
                <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($appointment['phone']); ?>" required>
                <span class="form-hint">Used to check duplicate bookings (numbers only).</span>
            </div>

            <!-- Client Address -->
            <div class="form-group full-width">
                <label for="address" class="form-label">Client Address <span class="required">*</span></label>
                <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($appointment['address']); ?>" required>
            </div>

            <!-- Car License -->
            <div class="form-group">
                <label for="car_license" class="form-label">Car License / Registration No. <span class="required">*</span></label>
                <input type="text" id="car_license" name="car_license" class="form-control" value="<?php echo htmlspecialchars($appointment['car_license']); ?>" required>
            </div>

            <!-- Car Engine Number -->
            <div class="form-group">
                <label for="car_engine" class="form-label">Car Engine Number <span class="required">*</span></label>
                <input type="text" id="car_engine" name="car_engine" class="form-control" value="<?php echo htmlspecialchars($appointment['car_engine']); ?>" required>
                <span class="form-hint">Must contain numbers only.</span>
            </div>

            <!-- Appointment Date -->
            <div class="form-group">
                <label for="appointment_date" class="form-label">Appointment Date <span class="required">*</span></label>
                <input type="date" id="appointment_date" name="appointment_date" class="form-control" value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>" required>
            </div>

            <!-- Assigned Mechanic -->
            <div class="form-group">
                <label for="mechanic_id" class="form-label">Assigned Senior Mechanic <span class="required">*</span></label>
                <select id="mechanic_id" name="mechanic_id" class="form-control" required>
                    <?php foreach ($all_mechanics as $m): ?>
                        <?php
                        $is_current = ((int)$m['id'] === (int)$appointment['mechanic_id']);
                        $is_active = ((int)$m['is_active'] === 1);
                        ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $is_current ? 'selected' : ''; ?> <?php echo (!$is_active) ? 'disabled' : ''; ?>>
                            <?php echo htmlspecialchars($m['name']); ?>
                            <?php echo (!$is_active) ? ' (Inactive)' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="form-hint">Capacity rules (max 4 per day) apply if status is active.</span>
            </div>

            <!-- Appointment Status -->
            <div class="form-group full-width">
                <label for="status" class="form-label">Appointment Status <span class="required">*</span></label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active" <?php echo ($appointment['status'] === 'active') ? 'selected' : ''; ?>>Active (Enforces capacity and duplicate checks)</option>
                    <option value="completed" <?php echo ($appointment['status'] === 'completed') ? 'selected' : ''; ?>>Completed (Service finished)</option>
                    <option value="cancelled" <?php echo ($appointment['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled (Service cancelled)</option>
                </select>
            </div>

        </div>

        <div style="display: flex; gap: 12px; margin-top: 15px;">
            <button type="submit" class="btn btn-primary btn-lg">
                💾 Save Changes & Recheck
            </button>
            <a href="appointments.php" class="btn btn-secondary btn-lg">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
