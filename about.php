<?php
// Car Workshop Appointment Management System
// About Us Page

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "About Us";
$mechanics = get_active_mechanics($pdo);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>About Our Car Workshop</h1>
    <p>Delivering dependable, professional vehicle repairs and maintenance since 2010.</p>
</div>

<div class="card">
    <h2 class="card-title">Our Commitment to Quality</h2>
    <p style="margin-bottom: 15px;">
        At our Car Workshop, we believe in uncompromised mechanical excellence. Instead of overbooking our garage and causing delays, we enforce a strict <strong>4 active vehicles per day limit</strong> for each senior mechanic.
    </p>
    <p>
        This ensures that every engine tune-up, brake service, electrical diagnostic, and suspension overhaul receives thorough personal attention from a certified specialist.
    </p>
</div>

<div class="section-block" style="margin-top: 30px;">
    <div class="section-header" style="text-align: left;">
        <span class="section-tag">Dedicated Craftsmen</span>
        <h2 class="section-title">Our Senior Mechanics Team</h2>
        <p class="section-desc" style="margin: 0;">Meet the five master technicians assigned to your vehicles.</p>
    </div>

    <div class="mechanics-showcase">
        <?php foreach ($mechanics as $mech): ?>
            <div class="mechanic-profile-card">
                <div class="profile-avatar">👨‍🔧</div>
                <h3 class="profile-name"><?php echo htmlspecialchars($mech['name']); ?></h3>
                <p class="profile-role">Senior Automotive Specialist</p>
                <p class="profile-phone">📞 <?php echo htmlspecialchars($mech['phone']); ?></p>
                <span class="profile-badge">Daily Limit: 4 Cars</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div style="margin-top: 30px; text-align: center;">
    <a href="index.php#booking-section" class="btn btn-primary btn-lg">📅 Book Your Appointment Now</a>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
