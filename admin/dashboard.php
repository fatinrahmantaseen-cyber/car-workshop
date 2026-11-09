<?php
// Car Workshop Appointment Management System
// Admin Dashboard Overview

$is_admin_area = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login('login.php');

$page_title = "Admin Dashboard";
$today = date('Y-m-d');

// --------------------------------------------------------------------------
// 1. Fetch Dashboard Statistics
// --------------------------------------------------------------------------
try {
    // Total Appointments
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM appointments");
    $total_appointments = (int) $stmt->fetch()['total'];

    // Active Appointments
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'active'");
    $active_appointments = (int) $stmt->fetch()['total'];

    // Completed Appointments
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'completed'");
    $completed_appointments = (int) $stmt->fetch()['total'];

    // Cancelled Appointments
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM appointments WHERE status = 'cancelled'");
    $cancelled_appointments = (int) $stmt->fetch()['total'];

    // Today's Appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM appointments WHERE appointment_date = :today");
    $stmt->execute([':today' => $today]);
    $today_appointments = (int) $stmt->fetch()['total'];

    // Active Mechanics
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM mechanics WHERE is_active = 1");
    $total_mechanics = (int) $stmt->fetch()['total'];

    // Recent 5 Appointments
    $recent_stmt = $pdo->prepare("
        SELECT a.id, a.client_name, a.phone, a.car_license, a.appointment_date, a.status,
               m.name AS mechanic_name
        FROM appointments a
        JOIN mechanics m ON a.mechanic_id = m.id
        ORDER BY a.id DESC
        LIMIT 5
    ");
    $recent_stmt->execute();
    $recent_appointments = $recent_stmt->fetchAll();

    // Today's mechanic workload
    $mechanics_workload = get_all_mechanics_with_slots($pdo, $today);

} catch (PDOException $e) {
    error_log("Dashboard query failed: " . $e->getMessage());
    set_flash_message('error', 'Error retrieving statistics. Please try refreshing.');
    $total_appointments = $active_appointments = $completed_appointments = $cancelled_appointments = $today_appointments = $total_mechanics = 0;
    $recent_appointments = $mechanics_workload = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header-bar">
    <div>
        <h1>Dashboard Overview</h1>
        <p style="color: var(--text-muted);">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>. Here is the current workshop status.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="appointments.php" class="btn btn-primary">📋 Manage Appointments</a>
        <a href="../index.php" target="_blank" class="btn btn-secondary">🌐 Open Client Site</a>
    </div>
</div>

<!-- Metrics Statistics Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-number"><?php echo $total_appointments; ?></span>
        <span class="stat-label">Total Appointments</span>
    </div>
    <div class="stat-card stat-today">
        <span class="stat-number"><?php echo $today_appointments; ?></span>
        <span class="stat-label">Today's Appointments</span>
    </div>
    <div class="stat-card stat-active">
        <span class="stat-number"><?php echo $active_appointments; ?></span>
        <span class="stat-label">Active Bookings</span>
    </div>
    <div class="stat-card stat-completed">
        <span class="stat-number"><?php echo $completed_appointments; ?></span>
        <span class="stat-label">Completed</span>
    </div>
    <div class="stat-card stat-cancelled">
        <span class="stat-number"><?php echo $cancelled_appointments; ?></span>
        <span class="stat-label">Cancelled</span>
    </div>
</div>

<!-- Mechanics Daily Workload (Today) -->
<div class="card" style="margin-bottom: 30px;">
    <h2 class="card-title">👨‍🔧 Today's Mechanic Capacity (<?php echo htmlspecialchars($today); ?>)</h2>
    <div class="mechanics-showcase" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
        <?php foreach ($mechanics_workload as $mech): ?>
            <?php
            $is_full = $mech['available_slots'] <= 0;
            $badge_class = $is_full ? 'badge-cancelled' : ($mech['available_slots'] === 1 ? 'slot-warning' : 'badge-active');
            ?>
            <div class="mechanic-profile-card" style="padding: 16px;">
                <h4 style="font-size: 1.05rem; margin-bottom: 4px;"><?php echo htmlspecialchars($mech['name']); ?></h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">Active: <?php echo $mech['active_count']; ?> / 4</p>
                <span class="badge-status <?php echo $badge_class; ?>">
                    <?php echo $is_full ? 'Fully Booked' : $mech['available_slots'] . ' Slots Left'; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Appointments Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">Recent Appointments</h2>
        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All &rarr;</a>
    </div>

    <?php if (empty($recent_appointments)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 25px;">No appointments found in the system yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Car Registration</th>
                        <th>Date</th>
                        <th>Mechanic</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_appointments as $appt): ?>
                        <?php
                        $status_class = 'badge-active';
                        if ($appt['status'] === 'completed') $status_class = 'badge-completed';
                        if ($appt['status'] === 'cancelled') $status_class = 'badge-cancelled';
                        ?>
                        <tr>
                            <td>#<?php echo (int) $appt['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($appt['client_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($appt['phone']); ?></td>
                            <td><?php echo htmlspecialchars($appt['car_license']); ?></td>
                            <td><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($appt['mechanic_name']); ?></td>
                            <td><span class="badge-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($appt['status']); ?></span></td>
                            <td>
                                <a href="edit-appointment.php?id=<?php echo (int) $appt['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
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
