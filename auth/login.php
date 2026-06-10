<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email format.';
    } else {
        $db   = (new \Config\Database())->getConnection();
        $user = new \Classes\User($db);
        $user->email = $email;
        if ($user->readByEmail() && verifyPassword($password, $user->password)) {
            $_SESSION['user_id']   = $user->id;
            $_SESSION['username']  = $user->username;
            $_SESSION['user_role'] = $user->role;
            logActivity($user->id, 'login');
            setFlash('Welcome back, ' . sanitizeOutput($user->username) . '!', 'success');
            redirect(BASE_URL . '/index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">🗼</div>
            <h2>Login</h2>
            <p>Welcome back, Commander</p>
        </div>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitizeOutput($error) ?></div>
        <?php endif; ?>
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= sanitizeOutput($flash['message']) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" type="email" name="email" required
                       value="<?= sanitizeOutput($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input class="form-input" type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Login</button>
        </form>
        <div class="auth-links">
            <a href="<?= $base ?>/auth/reset_password.php">Forgot password?</a>
            <span>|</span>
            <a href="<?= $base ?>/auth/register.php">Create account</a>
        </div>
    </div>
</div>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
