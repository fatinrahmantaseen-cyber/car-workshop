<?php
// Car Workshop Appointment Management System
// Admin Authentication & Initial Setup

$is_admin_area = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect straight to dashboard
if (is_admin_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

// Check how many admin accounts exist in the database
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM admins");
$admin_count = (int) $stmt->fetch()['total'];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submitted_csrf)) {
        set_flash_message('error', 'Security check failed (invalid session token). Please try again.');
        header("Location: login.php");
        exit;
    }

    $form_action = $_POST['form_action'] ?? 'login';

    // ----------------------------------------------------------------------
    // Initial Admin Account Creation (Only active when admins table is empty)
    // ----------------------------------------------------------------------
    if ($form_action === 'setup_admin' && $admin_count === 0) {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($password)) {
            set_flash_message('error', 'Username and password cannot be empty.');
            header("Location: login.php");
            exit;
        }

        if (strlen($username) < 3) {
            set_flash_message('error', 'Username must be at least 3 characters long.');
            header("Location: login.php");
            exit;
        }

        if (strlen($password) < 6) {
            set_flash_message('error', 'Password must be at least 6 characters long for security.');
            header("Location: login.php");
            exit;
        }

        if ($password !== $confirm_password) {
            set_flash_message('error', 'Passwords do not match. Please re-enter your password.');
            header("Location: login.php");
            exit;
        }

        // Hash password securely using standard PHP password_hash
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $insert_stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (:username, :password)");
            $insert_stmt->execute([
                ':username' => $username,
                ':password' => $hashed_password
            ]);

            set_flash_message('success', 'Admin account successfully configured! Please log in below.');
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            error_log("Failed to create admin: " . $e->getMessage());
            set_flash_message('error', 'Database error creating admin account. Please try again.');
            header("Location: login.php");
            exit;
        }
    }

    // ----------------------------------------------------------------------
    // Standard Admin Login Authentication
    // ----------------------------------------------------------------------
    if ($form_action === 'login') {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            set_flash_message('error', 'Please enter both username and password.');
            header("Location: login.php");
            exit;
        }

        $auth_stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = :username LIMIT 1");
        $auth_stmt->execute([':username' => $username]);
        $admin = $auth_stmt->fetch();

        // Verify password against stored bcrypt/argon2 hash
        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = (int) $admin['id'];
            $_SESSION['admin_username']  = $admin['username'];

            set_flash_message('success', 'Welcome back, ' . htmlspecialchars($admin['username']) . '!');
            header("Location: dashboard.php");
            exit;
        } else {
            set_flash_message('error', 'Invalid username or password.');
            header("Location: login.php");
            exit;
        }
    }
}

$page_title = ($admin_count === 0) ? "Initial Admin Setup" : "Admin Login";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        
        <?php if ($admin_count === 0): ?>
            <!-- Initial Admin Account Creation Form -->
            <h1 class="auth-title">🔐 Initial Admin Setup</h1>
            <p class="auth-subtitle">No admin accounts detected. Create your initial administrator account below.</p>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                <input type="hidden" name="form_action" value="setup_admin">

                <div class="form-group">
                    <label for="username" class="form-label">Administrator Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. admin" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">New Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                    <span class="form-hint">Stored safely as a bcrypt password hash.</span>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 15px;">
                    ✨ Create Admin Account
                </button>
            </form>

        <?php else: ?>
            <!-- Standard Admin Login Form -->
            <h1 class="auth-title">🔒 Admin Portal Login</h1>
            <p class="auth-subtitle">Authorized workshop management staff only.</p>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                <input type="hidden" name="form_action" value="login">

                <div class="form-group">
                    <label for="username" class="form-label">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 15px;">
                    Sign In to Admin Panel
                </button>
            </form>

        <?php endif; ?>

    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
