<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (!$username || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!validateUsername($username)) {
        $error = 'Username must be 3–20 characters (letters, numbers, underscores only).';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email address.';
    } elseif (!validatePassword($password)) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = (new \Config\Database())->getConnection();
        // Check duplicates
        $s = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $s->execute([$username, $email]);
        if ($s->fetch()) {
            $error = 'Username or email already taken.';
        } else {
            $user = new \Classes\User($db);
            $user->username = $username;
            $user->email    = $email;
            $user->password = hashPassword($password);
            $user->role     = 'user';
            if ($user->create()) {
                $uid = $db->lastInsertId();
                $_SESSION['user_id']   = $uid;
                $_SESSION['username']  = $username;
                $_SESSION['user_role'] = 'user';
                logActivity($uid, 'register');
                setFlash('Account created! Welcome, ' . sanitizeOutput($username) . '!', 'success');
                redirect(BASE_URL . '/index.php');
            } else {
                $error = 'Registration failed — please try again.';
            }
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
            <h2>Create Account</h2>
            <p>Join the battle, Commander</p>
        </div>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitizeOutput($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input class="form-input" type="text" name="username" required maxlength="20"
                       value="<?= sanitizeOutput($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" type="email" name="email" required
                       value="<?= sanitizeOutput($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input class="form-input" type="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input class="form-input" type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
        </form>
        <div class="auth-links">
            <a href="<?= $base ?>/auth/login.php">Already have an account? Login</a>
        </div>
    </div>
</div>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
