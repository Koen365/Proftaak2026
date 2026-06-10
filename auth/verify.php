<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();

// If already logged in, go home
if (isLoggedIn()) redirect(BASE_URL . '/index.php');

// In a full production app this page would validate an email token.
// For now it auto-confirms registration and logs the user in if they
// came here right after signing up (session var set in register.php).
$base = BASE_URL;

if (isset($_SESSION['verify_user_id'])) {
    $uid = (int)$_SESSION['verify_user_id'];
    unset($_SESSION['verify_user_id'], $_SESSION['verify_token']);

    // Load user and log them in
    $db   = (new \Config\Database())->getConnection();
    $user = new \Classes\User($db);
    $user->id = $uid;
    if ($user->readById()) {
        $_SESSION['user_id']   = $user->id;
        $_SESSION['username']  = $user->username;
        $_SESSION['user_role'] = $user->role;
        setFlash('Account verified! Welcome, ' . sanitizeOutput($user->username) . '!', 'success');
        redirect(BASE_URL . '/index.php');
    }
}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">✉️</div>
            <h2>Email Verification</h2>
            <p>Check your inbox for a verification link.</p>
        </div>
        <div class="alert alert-info">
            In development mode, email sending is disabled.
            You can <a href="<?= $base ?>/auth/login.php">log in directly</a>.
        </div>
        <div class="auth-links">
            <a href="<?= $base ?>/auth/login.php">Go to Login</a>
        </div>
    </div>
</div>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
