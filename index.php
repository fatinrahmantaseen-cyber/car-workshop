<?php
// Car Workshop Appointment Management System
// Homepage & Appointment Booking Page

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Handle AJAX request for dynamic mechanic availability when appointment date changes
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_slots') {
    header('Content-Type: application/json; charset=utf-8');
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';

    // Validate date format (YYYY-MM-DD)
    if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid appointment date format.'
        ]);
        exit;
    }

    $mechanics_with_slots = get_all_mechanics_with_slots($pdo, $date);

    echo json_encode([
        'success'   => true,
        'date'      => $date,
        'mechanics' => $mechanics_with_slots
    ]);
    exit;
}

$page_title = "Book an Appointment";
$today = date('Y-m-d');
$old_data = get_form_old_data();
$selected_date = !empty($old_data['appointment_date']) ? $old_data['appointment_date'] : $today;
$mechanics = get_all_mechanics_with_slots($pdo, $selected_date);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcoming Hero Section -->
<section class="hero-section">
    <div class="hero-pill">🚗 Premium Car Care & Maintenance</div>
    <h1 class="hero-title">Expert Automotive Service with Dedicated Senior Mechanics</h1>
    <p class="hero-subtitle">
        Schedule your vehicle service online with your chosen senior specialist. Each mechanic handles a maximum of 4 cars per day to guarantee top-tier craftsmanship and zero chaos.
    </p>
    <div class="hero-actions">
        <a href="#booking-section" class="btn btn-secondary btn-lg">📅 Book an Appointment</a>
        <a href="#mechanics-section" class="btn btn-secondary btn-lg" style="background: transparent; color: #ffffff; border: 1px solid rgba(255,255,255,0.6);">Meet Our Mechanics</a>
    </div>
</section>

<!-- Senior Mechanics Introduction Section -->
<section id="mechanics-section" class="section-block">
    <div class="section-header">
        <span class="section-tag">Our Dedicated Team</span>
        <h2 class="section-title">Senior Workshop Mechanics</h2>
        <p class="section-desc">
            Our certified specialists have decades of collective diagnostic and repair expertise. Each mechanic maintains a strict 4-car daily limit.
        </p>
    </div>

    <div class="mechanics-showcase">
        <?php foreach ($mechanics as $mech): ?>
            <div class="mechanic-profile-card">
                <div class="profile-avatar">👨‍🔧</div>
                <h3 class="profile-name"><?php echo htmlspecialchars($mech['name']); ?></h3>
                <p class="profile-role">Senior Specialist</p>
                <p class="profile-phone">📞 <?php echo htmlspecialchars($mech['phone']); ?></p>
                <span class="profile-badge">Max 4 Active Cars/Day</span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- How Booking Works Section -->
<section class="section-block">
    <div class="section-header">
        <span class="section-tag">Simple & Transparent</span>
        <h2 class="section-title">How Our Booking System Works</h2>
        <p class="section-desc">Follow four simple steps to schedule your car inspection without long workshop queues.</p>
    </div>

    <div class="steps-grid">
        <div class="step-card">
            <span class="step-number">1</span>
            <h3 class="step-title">Select Service Date</h3>
            <p class="step-desc">Pick your desired date. The system immediately checks live mechanic capacity for that day.</p>
        </div>
        <div class="step-card">
            <span class="step-number">2</span>
            <h3 class="step-title">Choose Your Mechanic</h3>
            <p class="step-desc">Pick any available senior mechanic. Real-time slot badges show exact remaining openings.</p>
        </div>
        <div class="step-card">
            <span class="step-number">3</span>
            <h3 class="step-title">Provide Vehicle Details</h3>
            <p class="step-desc">Enter your contact info, car registration/license, and numeric engine identification number.</p>
        </div>
        <div class="step-card">
            <span class="step-number">4</span>
            <h3 class="step-title">Instant Booking</h3>
            <p class="step-desc">Your appointment is reserved directly under the designated mechanic without double-booking.</p>
        </div>
    </div>
</section>

<!-- Appointment Booking Form Section -->
<section id="booking-section" class="section-block">
    <div class="section-header">
        <span class="section-tag">Online Reservation</span>
        <h2 class="section-title">Book Your Workshop Appointment</h2>
        <p class="section-desc">Enter your vehicle information below and select your preferred specialist.</p>
    </div>

    <div class="form-card">
        <form id="appointment-form" action="actions/book-appointment.php" method="POST" novalidate>
            <div class="form-grid">
                
                <!-- Client Name -->
                <div class="form-group">
                    <label for="client_name" class="form-label">Client Name <span class="required">*</span></label>
                    <input type="text" id="client_name" name="client_name" class="form-control" placeholder="Enter your full name" value="<?php echo htmlspecialchars($old_data['client_name'] ?? ''); ?>" required>
                    <span class="error-text" id="error-client_name">Please enter your name.</span>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. 01711234567" value="<?php echo htmlspecialchars($old_data['phone'] ?? ''); ?>" required>
                    <span class="form-hint">Used to verify your appointment (numeric digits only).</span>
                    <span class="error-text" id="error-phone">Please enter a valid numeric phone number.</span>
                </div>

                <!-- Client Address -->
                <div class="form-group full-width">
                    <label for="address" class="form-label">Client Address <span class="required">*</span></label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Street address, area, and city" value="<?php echo htmlspecialchars($old_data['address'] ?? ''); ?>" required>
                    <span class="error-text" id="error-address">Please enter your address.</span>
                </div>

                <!-- Car License / Registration Number -->
                <div class="form-group">
                    <label for="car_license" class="form-label">Car License / Registration No. <span class="required">*</span></label>
                    <input type="text" id="car_license" name="car_license" class="form-control" placeholder="e.g. DHK-METRO-GA-1234" value="<?php echo htmlspecialchars($old_data['car_license'] ?? ''); ?>" required>
                    <span class="error-text" id="error-car_license">Please enter your car registration number.</span>
                </div>

                <!-- Car Engine Number -->
                <div class="form-group">
                    <label for="car_engine" class="form-label">Car Engine Number <span class="required">*</span></label>
                    <input type="text" id="car_engine" name="car_engine" class="form-control" placeholder="e.g. 8492015" value="<?php echo htmlspecialchars($old_data['car_engine'] ?? ''); ?>" required>
                    <span class="form-hint">Must contain numbers only.</span>
                    <span class="error-text" id="error-car_engine">Engine number must contain only numbers.</span>
                </div>

                <!-- Appointment Date -->
                <div class="form-group full-width">
                    <label for="appointment_date" class="form-label">Appointment Date <span class="required">*</span></label>
                    <input type="date" id="appointment_date" name="appointment_date" class="form-control" min="<?php echo $today; ?>" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                    <span class="form-hint">Mechanic slot availability updates automatically when you change this date. Past dates are not allowed.</span>
                    <span class="error-text" id="error-appointment_date">Please select a valid future or current date.</span>
                </div>

                <!-- Mechanic Selection -->
                <div class="form-group full-width">
                    <div class="availability-status-bar">
                        <label class="form-label" style="margin-bottom: 0;">Select Preferred Senior Mechanic <span class="required">*</span></label>
                        <span class="live-indicator" id="availability-status">
                            📅 Showing availability for: <strong id="date-display-label"><?php echo htmlspecialchars($selected_date); ?></strong>
                        </span>
                    </div>

                    <div id="mechanic-selection-container" class="mechanic-selection-grid">
                        <?php foreach ($mechanics as $mech): ?>
                            <?php
                            $is_full = ($mech['available_slots'] <= 0);
                            $badge_class = 'slot-available';
                            $slot_text = $mech['name'] . ' — ' . $mech['available_slots'] . ' slots available';

                            if ($mech['available_slots'] === 1) {
                                $badge_class = 'slot-warning';
                            } elseif ($is_full) {
                                $badge_class = 'slot-full';
                                $slot_text = $mech['name'] . ' — Fully Booked';
                            }

                            $is_checked = isset($old_data['mechanic_id']) && (int) $old_data['mechanic_id'] === (int) $mech['id'] && !$is_full;
                            ?>
                            <label class="mechanic-radio-item <?php echo $is_full ? 'disabled' : ''; ?>" data-mechanic-id="<?php echo $mech['id']; ?>">
                                <input type="radio" name="mechanic_id" value="<?php echo $mech['id']; ?>" <?php echo $is_full ? 'disabled' : ''; ?> <?php echo $is_checked ? 'checked' : ''; ?> required>
                                <div class="mechanic-radio-card">
                                    <span class="radio-mech-name"><?php echo htmlspecialchars($mech['name']); ?></span>
                                    <span class="radio-mech-phone">📞 <?php echo htmlspecialchars($mech['phone']); ?></span>
                                    <span class="slot-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($slot_text); ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <span class="error-text" id="error-mechanic_id" style="margin-top: 8px;">Please select an available mechanic.</span>
                </div>

            </div>

            <!-- Submit Button -->
            <button type="submit" id="submit-booking-btn" class="btn btn-primary btn-block btn-lg">
                🔧 Submit Appointment Request
            </button>
        </form>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
