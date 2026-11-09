<?php
// Car Workshop Appointment Management System
// Help & Guidelines Page

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Help & Guidelines";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Help & Appointment Guidelines</h1>
    <p>Everything you need to know about our appointment booking policies and workshop procedures.</p>
</div>

<div class="help-grid">
    <!-- Section 1: How to Book -->
    <div class="help-card">
        <h2 class="help-card-title">📖 How to Book an Appointment</h2>
        <p>Booking your car service is fast and straightforward:</p>
        <ul>
            <li><strong>Step 1:</strong> Visit the <a href="index.php">Book Appointment</a> page.</li>
            <li><strong>Step 2:</strong> Enter your full name, mobile phone number, and physical address.</li>
            <li><strong>Step 3:</strong> Enter your vehicle's license/registration number and numeric engine number.</li>
            <li><strong>Step 4:</strong> Select your desired appointment date. (Past dates cannot be selected).</li>
            <li><strong>Step 5:</strong> Pick your preferred senior mechanic from the available list.</li>
            <li><strong>Step 6:</strong> Click <em>Submit Appointment Request</em> to finalize your reservation.</li>
        </ul>
    </div>

    <!-- Section 2: 4-Car Daily Limit -->
    <div class="help-card">
        <h2 class="help-card-title">⚙️ 4-Car Daily Limit Policy</h2>
        <p>Our workshop employs 5 certified senior mechanics: <strong>Rahim, Karim, Jashim, Masud, and Helal</strong>.</p>
        <div class="rule-highlight">
            <strong>Rule:</strong> Each senior mechanic is strictly limited to a maximum of <strong>4 active vehicles per day</strong>.
        </div>
        <p>
            This policy guarantees that our mechanics have sufficient time to thoroughly diagnose and repair every vehicle without rushing or delays.
        </p>
        <p style="margin-top: 10px;">
            If a mechanic reaches 4 bookings on your chosen date, their status changes to <span class="slot-badge slot-full" style="display:inline;">Fully Booked</span>. You may choose another available mechanic on that date or select a different date.
        </p>
    </div>

    <!-- Section 3: Duplicate Booking Prevention -->
    <div class="help-card">
        <h2 class="help-card-title">🚫 Duplicate Booking Policy</h2>
        <p>To keep the booking system fair and prevent reservation hoarding:</p>
        <div class="rule-highlight">
            <strong>Rule:</strong> The same client phone number cannot hold more than <strong>one active appointment on the same date</strong>, even with different mechanics.
        </div>
        <p>
            If you attempt to book a second car under the same phone number for the same day, the system will notify you that an active appointment already exists on that date.
        </p>
    </div>

    <!-- Section 4: Workshop Hours & Support -->
    <div class="help-card">
        <h2 class="help-card-title">🕒 Operating Hours & Support</h2>
        <p>Our workshop operates during the following hours:</p>
        <ul>
            <li><strong>Saturday – Thursday:</strong> 8:00 AM – 6:00 PM</li>
            <li><strong>Friday:</strong> Closed (Weekly Holiday)</li>
            <li><strong>Helpline:</strong> +880 1700-000000 / +880 1800-000000</li>
            <li><strong>Support Email:</strong> service@carworkshop.local</li>
        </ul>
        <p style="margin-top: 10px;">
            For urgent rescheduling or special vehicle inquiries, please reach out directly to our management desk.
        </p>
    </div>
</div>

<div style="margin-top: 30px; text-align: center;">
    <a href="index.php" class="btn btn-primary btn-lg">📅 Go to Appointment Booking</a>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
