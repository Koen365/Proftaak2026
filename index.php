<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();

$db   = (new \Config\Database())->getConnection();
$flash = getFlash();

// Fetch latest 3 news items
$newsItems = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT id, title, content, created_at FROM news ORDER BY created_at DESC LIMIT 3");
        $newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { /* no news table yet */ }
}

// User stats
$bestScore = 0; $rank = 'N/A'; $unlockCount = 0;
if (isLoggedIn() && $db) {
    $uid = getCurrentUserId();
    try {
        $s = $db->prepare("SELECT MAX(score) as bs FROM scores WHERE user_id=?");
        $s->execute([$uid]); $row = $s->fetch(PDO::FETCH_ASSOC);
        $bestScore = $row['bs'] ?? 0;
    } catch (Exception $e) {}
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM user_unlockables WHERE user_id=?");
        $s->execute([$uid]); $unlockCount = $s->fetchColumn();
    } catch (Exception $e) {}
}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>

<main class="container">
<?php if ($flash): ?>
<div class="alert alert-<?= sanitizeOutput($flash['type']) ?>">
    <?= sanitizeOutput($flash['message']) ?>
    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <div class="hero-badge">🗼 Tower Defense Platform</div>
        <h1>Dominate the<br><span class="gradient-text">Battlefield</span></h1>
        <p>Track scores, unlock rewards, compete globally, and master every map.</p>
        <div class="hero-btns">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/game/info.php"  class="btn btn-primary btn-lg">Explore Game</a>
                <a href="<?= BASE_URL ?>/leaderboard.php" class="btn btn-outline btn-lg">Leaderboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
                <a href="<?= BASE_URL ?>/auth/login.php"    class="btn btn-outline btn-lg">Login</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-graphic">
        <div class="hero-tower-preview">
            <div class="ht ht1">🗼</div>
            <div class="ht ht2">🏰</div>
            <div class="ht ht3">⚡</div>
            <div class="hero-wave">🌊 Wave 14</div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="section">
    <h2 class="section-title">Everything you need</h2>
    <div class="card-grid five">
        <div class="feature-card">
            <div class="fc-icon">🏆</div>
            <h3>Leaderboards</h3>
            <p>Compete globally — filter by score, waves, or time.</p>
            <a href="<?= BASE_URL ?>/leaderboard.php" class="btn btn-outline btn-sm">View Rankings</a>
        </div>
        <div class="feature-card">
            <div class="fc-icon">📚</div>
            <h3>Collection Book</h3>
            <p>Track every tower, enemy, badge and cosmetic you own.</p>
            <a href="<?= BASE_URL ?>/collection.php" class="btn btn-outline btn-sm">Open Collection</a>
        </div>
        <div class="feature-card">
                    <div class="fc-icon">🎮</div>
                    <h3>Minigames</h3>
                    <p>Play Tower Builder and Survival Trial to earn coins and unlock rewards.</p>
                    <a href="<?= BASE_URL ?>/minigames/" class="btn btn-outline btn-sm">Play Now</a>
                </div>
                <div class="feature-card">
                    <div class="fc-icon">🎰</div>
                    <h3>Loot Box</h3>
                    <p>Spend coins to spin for legendary towers, cosmetics and badges.</p>
                    <a href="<?= BASE_URL ?>/minigames/lootbox.php" class="btn btn-outline btn-sm">Open Loot Box</a>
                </div>
        <div class="feature-card">
            <div class="fc-icon">📰</div>
            <h3>News & Updates</h3>
            <p>Stay up to date with patches, events and releases.</p>
            <a href="<?= BASE_URL ?>/news.php" class="btn btn-outline btn-sm">Read News</a>
        </div>
    </div>
</section>

<?php if (isLoggedIn()): ?>
<!-- USER STATS -->
<section class="section">
    <h2 class="section-title">Your Stats</h2>
    <div class="card-grid three">
        <div class="stat-card"><div class="stat-icon">🏅</div><div class="stat-val"><?= formatNumber($bestScore) ?></div><div class="stat-label">Best Score</div></div>
        <div class="stat-card"><div class="stat-icon">🔓</div><div class="stat-val"><?= $unlockCount ?></div><div class="stat-label">Unlockables</div></div>
        <div class="stat-card"><div class="stat-icon">🎯</div><div class="stat-val"><?= sanitizeOutput($_SESSION['username'] ?? '') ?></div><div class="stat-label">Commander</div></div>
    </div>
</section>
<?php endif; ?>

<!-- LATEST NEWS -->
<section class="section">
    <div class="section-header">
        <h2 class="section-title">Latest News</h2>
        <a href="<?= BASE_URL ?>/news.php" class="btn btn-outline btn-sm">All News →</a>
    </div>
    <?php if (empty($newsItems)): ?>
    <div class="card-grid three">
        <div class="news-card featured-card">
            <div class="news-tag">Update</div>
            <h3>Welcome to TowerDefenseHQ!</h3>
            <p class="news-date">May 2026</p>
            <p>The platform is live. Register an account, compete on leaderboards, and unlock exclusive rewards.</p>
        </div>
        <div class="news-card">
            <div class="news-tag">Event</div>
            <h3>Tower Builder Challenge Live</h3>
            <p class="news-date">May 2026</p>
            <p>Play the Tower Builder minigame to earn exclusive skins and badges for your collection.</p>
        </div>
        <div class="news-card">
            <div class="news-tag">Feature</div>
            <h3>Endless Survival Trial</h3>
            <p class="news-date">May 2026</p>
            <p>How long can you survive? New leaderboard category for Survival Trial high scores.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card-grid three">
        <?php foreach ($newsItems as $n): ?>
        <div class="news-card">
            <h3><?= sanitizeOutput($n['title']) ?></h3>
            <p class="news-date"><?= formatTimestamp($n['created_at']) ?></p>
            <p><?= sanitizeOutput(mb_substr($n['content'], 0, 120)) ?>…</p>
            <a href="<?= BASE_URL ?>/news.php?id=<?= (int)$n['id'] ?>" class="btn btn-outline btn-sm">Read More</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

</main>

<?php include BASE_PATH . '/includes/footer.php'; ?>
