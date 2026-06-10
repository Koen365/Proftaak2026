<?php
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
$base = BASE_URL;
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col">
            <div class="footer-logo">🗼 TowerDefenseHQ</div>
            <p>Your ultimate companion for tower defense games. Compete, collect, and conquer.</p>
            <p class="copyright">&copy; <?= date('Y') ?> TowerDefenseHQ</p>
        </div>
        <div class="footer-col">
            <h4>Navigate</h4>
            <ul>
                <li><a href="<?= $base ?>/index.php">Home</a></li>
                <li><a href="<?= $base ?>/game/info.php">Game Info</a></li>
                <li><a href="<?= $base ?>/leaderboard.php">Leaderboard</a></li>
                <li><a href="<?= $base ?>/wiki.php">Wiki</a></li>
                <li><a href="<?= $base ?>/news.php">News</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Play</h4>
            <ul>
                <li><a href="<?= $base ?>/minigames/tower_builder.php">Tower Builder</a></li>
                <li><a href="<?= $base ?>/minigames/survival_trial.php">Survival Trial</a></li>
                <li><a href="<?= $base ?>/collection.php">Collection Book</a></li>
                <li><a href="<?= $base ?>/profile.php">My Profile</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Account</h4>
            <ul>
                <?php if (isLoggedIn()): ?>
                <li><a href="<?= $base ?>/profile.php">Profile</a></li>
                <li><a href="<?= $base ?>/auth/logout.php">Logout</a></li>
                <?php else: ?>
                <li><a href="<?= $base ?>/auth/login.php">Login</a></li>
                <li><a href="<?= $base ?>/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</footer>
<script src="<?= $base ?>/assets/js/main.js"></script>
</body>
</html>
