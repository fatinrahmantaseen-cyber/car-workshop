<?php
// Car Workshop Appointment Management System
// Admin Appointments List & Status Management

$is_admin_area = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login('login.php');

$page_title = "Manage Appointments";

// --------------------------------------------------------------------------
// 1. Handle Quick Status Updates (e.g. Mark Completed, Cancel)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_status_change') {
    $csrf_token     = $_POST['csrf_token'] ?? '';
    $appointment_id = (int) ($_POST['appointment_id'] ?? 0);
    $new_status     = sanitize_input($_POST['new_status'] ?? '');

    if (!verify_csrf_token($csrf_token)) {
        set_flash_message('error', 'Security token expired. Please try again.');
        header("Location: appointments.php");
        exit;
    }

    if (!in_array($new_status, ['active', 'completed', 'cancelled'], true) || $appointment_id <= 0) {
        set_flash_message('error', 'Invalid status update request.');
        header("Location: appointments.php");
        exit;
    }

    try {
        // If changing to 'active', redirect to edit-appointment.php for full capacity & duplicate checking
        if ($new_status === 'active') {
            header("Location: edit-appointment.php?id=" . $appointment_id);
            exit;
        }

        // Marking as completed or cancelled does not require capacity checks
        $update_stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE id = :id");
        $update_stmt->execute([
            ':status' => $new_status,
            ':id'     => $appointment_id
        ]);

        set_flash_message('success', "Appointment #{$appointment_id} status updated to " . ucfirst($new_status) . ".");
        header("Location: appointments.php");
        exit;

    } catch (PDOException $e) {
        error_log("Status update failed: " . $e->getMessage());
        set_flash_message('error', 'Failed to update appointment status.');
        header("Location: appointments.php");
        exit;
    }
}

// --------------------------------------------------------------------------
// 2. Filter Parameters
// --------------------------------------------------------------------------
$filter_date     = isset($_GET['date']) ? trim($_GET['date']) : '';
$filter_mechanic = isset($_GET['mechanic_id']) ? (int) $_GET['mechanic_id'] : 0;
$filter_status   = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search_query    = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch all active mechanics for the filter dropdown
$mechanics_list = get_active_mechanics($pdo);

// --------------------------------------------------------------------------
// 3. Build Dynamic SQL Query
// --------------------------------------------------------------------------
$sql = "
    SELECT a.id, a.client_name, a.phone, a.address, a.car_license, a.car_engine,
           a.appointment_date, a.status, a.created_at,
           m.id AS mechanic_id, m.name AS mechanic_name
    FROM appointments a
    JOIN mechanics m ON a.mechanic_id = m.id
    WHERE 1=1
";
$params = [];

if (!empty($filter_date)) {
    $sql .= " AND a.appointment_date = :date";
    $params[':date'] = $filter_date;
}

if ($filter_mechanic > 0) {
    $sql .= " AND a.mechanic_id = :mechanic_id";
    $params[':mechanic_id'] = $filter_mechanic;
}

if (in_array($filter_status, ['active', 'completed', 'cancelled'], true)) {
    $sql .= " AND a.status = :status";
    $params[':status'] = $filter_status;
}

if (!empty($search_query)) {
    $sql .= " AND (a.client_name LIKE :q OR a.phone LIKE :q OR a.car_license LIKE :q OR a.car_engine LIKE :q)";
    $params[':q'] = "%" . $search_query . "%";
}

$sql .= " ORDER BY a.appointment_date DESC, a.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Appointments listing failed: " . $e->getMessage());
    set_flash_message('error', 'Failed to load appointments from database.');
    $appointments = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header-bar">
    <div>
        <h1>All Client Appointments</h1>
        <p style="color: var(--text-muted);">View, search, filter, and reschedule appointments across all senior mechanics.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="dashboard.php" class="btn btn-secondary">&larr; Dashboard</a>
        <a href="../index.php" target="_blank" class="btn btn-primary">+ New Booking</a>
    </div>
</div>

<!-- Filter & Search Toolbar -->
<div class="filter-card">
    <form method="GET" action="appointments.php" class="filter-grid">
        
        <!-- Search Keyword -->
        <div class="form-group" style="margin-bottom: 0;">
            <label for="q" class="form-label" style="font-size: 0.85rem;">Search</label>
            <input type="text" id="q" name="q" class="form-control" style="padding: 8px 12px;" placeholder="Client, Phone, License..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>

        <!-- Filter by Date -->
        <div class="form-group" style="margin-bottom: 0;">
            <label for="date" class="form-label" style="font-size: 0.85rem;">Appointment Date</label>
            <input type="date" id="date" name="date" class="form-control" style="padding: 8px 12px;" value="<?php echo htmlspecialchars($filter_date); ?>">
        </div>

        <!-- Filter by Mechanic -->
        <div class="form-group" style="margin-bottom: 0;">
            <label for="mechanic_id" class="form-label" style="font-size: 0.85rem;">Mechanic</label>
            <select id="mechanic_id" name="mechanic_id" class="form-control" style="padding: 8px 12px;">
                <option value="0">All Mechanics</option>
                <?php foreach ($mechanics_list as $m): ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo ($filter_mechanic === (int)$m['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filter by Status -->
        <div class="form-group" style="margin-bottom: 0;">
            <label for="status" class="form-label" style="font-size: 0.85rem;">Status</label>
            <select id="status" name="status" class="form-control" style="padding: 8px 12px;">
                <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                <option value="active" <?php echo ($filter_status === 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="completed" <?php echo ($filter_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo ($filter_status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">Filter</button>
            <?php if (!empty($filter_date) || $filter_mechanic > 0 || $filter_status !== 'all' || !empty($search_query)): ?>
                <a href="appointments.php" class="btn btn-secondary" style="padding: 8px 14px;">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Appointments Table -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 18px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0;">
            Appointments List (<?php echo count($appointments); ?> found)
        </h2>
    </div>

    <?php if (empty($appointments)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
            No appointments match the selected criteria.
        </p>
    <?php else: ?>
        <div class="table-responsive" style="border: none; box-shadow: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Car Registration</th>
                        <th>Engine No.</th>
                        <th>Appointment Date</th>
                        <th>Assigned Mechanic</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                        <?php
                        $status_class = 'badge-active';
                        if ($appt['status'] === 'completed') $status_class = 'badge-completed';
                        if ($appt['status'] === 'cancelled') $status_class = 'badge-cancelled';
                        ?>
                        <tr>
                            <td>#<?php echo (int) $appt['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($appt['client_name']); ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($appt['address']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($appt['phone']); ?></td>
                            <td><code><?php echo htmlspecialchars($appt['car_license']); ?></code></td>
                            <td><code><?php echo htmlspecialchars($appt['car_engine']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($appt['appointment_date']); ?></strong></td>
                            <td>👨‍🔧 <?php echo htmlspecialchars($appt['mechanic_name']); ?></td>
                            <td>
                                <span class="badge-status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($appt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="edit-appointment.php?id=<?php echo (int) $appt['id']; ?>" class="btn btn-sm btn-outline-primary" title="Reschedule Date or Reassign Mechanic">
                                        ✏️ Edit
                                    </a>

                                    <?php if ($appt['status'] === 'active'): ?>
                                        <!-- Quick Mark as Completed -->
                                        <form method="POST" action="appointments.php" style="display: inline;" onsubmit="return confirm('Mark this appointment as completed?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="quick_status_change">
                                            <input type="hidden" name="appointment_id" value="<?php echo (int) $appt['id']; ?>">
                                            <input type="hidden" name="new_status" value="completed">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Mark Completed">✓</button>
                                        </form>

                                        <!-- Quick Cancel -->
                                        <form method="POST" action="appointments.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="quick_status_change">
                                            <input type="hidden" name="appointment_id" value="<?php echo (int) $appt['id']; ?>">
                                            <input type="hidden" name="new_status" value="cancelled">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Appointment">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
