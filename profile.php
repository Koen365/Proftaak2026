<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');

$db  = (new \Config\Database())->getConnection();
$uid = getCurrentUserId();
$base = BASE_URL;

$user = new \Classes\User($db);
$user->id = $uid;
$user->readById();

// Stats
$bestScore = 0; $gamesPlayed = 0; $unlockCount = 0;
if ($db) {
    try {
        $s = $db->prepare("SELECT COUNT(*) as gp, MAX(score) as bs FROM scores WHERE user_id=?");
        $s->execute([$uid]); $r = $s->fetch(PDO::FETCH_ASSOC);
        $gamesPlayed = $r['gp'] ?? 0;
        $bestScore   = $r['bs'] ?? 0;
    } catch(Exception $e){}
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM user_unlockables WHERE user_id=?");
        $s->execute([$uid]); $unlockCount = $s->fetchColumn();
    } catch(Exception $e){}
}

// Recent scores
$recentScores = [];
if ($db) {
    try {
        $s = $db->prepare("SELECT s.score, s.waves_survived, s.time_survived, s.created_at, m.name as map_name
                           FROM scores s LEFT JOIN maps m ON s.map_id=m.id
                           WHERE s.user_id=? ORDER BY s.created_at DESC LIMIT 10");
        $s->execute([$uid]); $recentScores = $s->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
}

// Profile update
$success = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail    = trim($_POST['email'] ?? '');
    $newPass     = $_POST['new_password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';
    if (!validateUsername($newUsername)) { $error = 'Invalid username format.'; }
    elseif (!validateEmail($newEmail))   { $error = 'Invalid email address.'; }
    elseif ($newPass && $newPass !== $confirm) { $error = 'Passwords do not match.'; }
    else {
        $user->username = $newUsername;
        $user->email    = $newEmail;
        $user->role     = $user->role;
        if ($user->update()) {
            if ($newPass) {
                $user->password = hashPassword($newPass);
                $user->updatePassword();
            }
            $_SESSION['username'] = $newUsername;
            $success = 'Profile updated!';
        } else {
            $error = 'Update failed — username or email may already be taken.';
        }
    }
}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">
    <div class="profile-layout">
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-avatar-wrap">
                <div class="profile-avatar-large"><?= strtoupper(substr($user->username,0,1)) ?></div>
                <div class="profile-rank-badge"><?= isAdmin()?'⚙️ Admin':'🎖️ Player' ?></div>
            </div>
            <h2 class="profile-username"><?= sanitizeOutput($user->username) ?></h2>
            <p class="profile-email"><?= sanitizeOutput($user->email) ?></p>
            <p class="profile-since">Member since <?= formatTimestamp($user->created_at) ?></p>
            <div class="profile-stats-mini">
                <div class="psm"><div class="psm-val"><?= formatNumber($bestScore) ?></div><div class="psm-label">Best Score</div></div>
                <div class="psm"><div class="psm-val"><?= $gamesPlayed ?></div><div class="psm-label">Games</div></div>
                <div class="psm"><div class="psm-val"><?= $unlockCount ?></div><div class="psm-label">Unlocks</div></div>
            </div>
        </div>

        <!-- Main -->
        <div class="profile-main">
            <!-- Tabs -->
            <div class="tabs" id="profile-tabs">
                <button class="tab-btn active" data-tab="scores">Match History</button>
                <button class="tab-btn" data-tab="settings">Settings</button>
            </div>

            <!-- Scores tab -->
            <div class="tab-panel active" id="tab-scores">
                <h3>Recent Games</h3>
                <?php if (empty($recentScores)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎮</div>
                    <p>No scores yet. Go play!</p>
                </div>
                <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Map</th><th>Score</th><th>Waves</th><th>Time</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach($recentScores as $row): ?>
                        <tr>
                            <td><?= sanitizeOutput($row['map_name'] ?? '—') ?></td>
                            <td><strong><?= formatNumber($row['score']) ?></strong></td>
                            <td><?= (int)$row['waves_survived'] ?></td>
                            <td><?= floor($row['time_survived']/60) ?>m <?= $row['time_survived']%60 ?>s</td>
                            <td><?= formatTimestamp($row['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Settings tab -->
            <div class="tab-panel" id="tab-settings">
                <h3>Edit Profile</h3>
                <?php if ($error): ?><div class="alert alert-error"><?= sanitizeOutput($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= sanitizeOutput($success) ?></div><?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input class="form-input" type="text" name="username" required value="<?= sanitizeOutput($user->username) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input class="form-input" type="email" name="email" required value="<?= sanitizeOutput($user->email) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password <small>(leave blank to keep)</small></label>
                        <input class="form-input" type="password" name="new_password" minlength="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input class="form-input" type="password" name="confirm_password">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
document.querySelectorAll('#profile-tabs .tab-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('#profile-tabs .tab-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById('tab-'+this.dataset.tab).classList.add('active');
    });
});
</script>
