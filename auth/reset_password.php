<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();

$message = ''; $type = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!validateEmail($email)) {
        $message = 'Enter a valid email address.';
        $type = 'error';
    } else {
        // Always show same message for security
        $message = 'If that email is registered, a reset link has been sent.';
        $type = 'success';
    }
}
$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">🔑</div>
            <h2>Reset Password</h2>
            <p>Enter your email to receive a reset link</p>
        </div>
        <?php if ($message): ?>
        <div class="alert alert-<?= $type ?>"><?= sanitizeOutput($message) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input class="form-input" type="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
        </form>
        <div class="auth-links">
            <a href="<?= $base ?>/auth/login.php">Back to Login</a>
        </div>
    </div>
</div>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
