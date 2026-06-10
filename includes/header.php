<?php
// header.php is included from many depths — use BASE_PATH/BASE_URL from config
if (!defined('BASE_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
$_currentPage = basename($_SERVER['PHP_SELF']);
$_currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tower Defense Companion</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/responsive.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="logo" href="<?= $base ?>/index.php">
            <span class="logo-icon">🗼</span>
            <span>TowerDefense<strong>HQ</strong></span>
        </a>

        <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Menu">&#9776;</button>

        <nav class="main-nav" id="main-nav">
            <a href="<?= $base ?>/index.php"        class="<?= $_currentPage==='index.php'?'active':'' ?>">Home</a>
            <a href="<?= $base ?>/game/info.php"    class="<?= $_currentPage==='info.php'?'active':'' ?>">Game Info</a>
            <a href="<?= $base ?>/leaderboard.php"  class="<?= $_currentPage==='leaderboard.php'?'active':'' ?>">Leaderboard</a>
            <a href="<?= $base ?>/wiki.php"         class="<?= $_currentPage==='wiki.php'?'active':'' ?>">Wiki</a>
            <a href="<?= $base ?>/collection.php"   class="<?= $_currentPage==='collection.php'?'active':'' ?>">Collection</a>
            <a href="<?= $base ?>/news.php"         class="<?= $_currentPage==='news.php'?'active':'' ?>">News</a>
            <a href="<?= $base ?>/minigames/" class="<?= $_currentDir==='minigames' && $_currentPage!=='lootbox.php'?'active':'' ?>">Minigames</a>
            <a href="<?= $base ?>/minigames/lootbox.php" class="<?= $_currentPage==='lootbox.php'?'active':'' ?>">🎰 Loot Box</a>
            <a href="<?= $base ?>/redeem.php" class="<?= $_currentPage==='redeem.php'?'active':'' ?>">🎟️ Redeem</a>
        </nav>

        <div class="header-actions">
            <?php if (isLoggedIn()): ?>
                <div class="user-menu" id="user-menu">
                    <?php
                    // Fetch coin balance for header display
                    $headerCoins = 0;
                    try {
                        $db_h = (new \Config\Database())->getConnection();
                        if ($db_h) {
                            $cs = $db_h->prepare("SELECT coins FROM users WHERE id=?");
                            $cs->execute([getCurrentUserId()]);
                            $headerCoins = (int)($cs->fetchColumn() ?? 0);
                        }
                    } catch(Exception $e) {}
                    ?>
                    <a href="<?= $base ?>/minigames/lootbox.php" class="header-coins" title="Your coins — click to visit Loot Box">
                        🪙 <?= number_format($headerCoins) ?>
                    </a>
                    <button class="user-menu-btn" id="user-menu-btn">
                        <span class="user-avatar-small"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></span>
                        <span><?= sanitizeOutput($_SESSION['username'] ?? 'User') ?></span>
                        <span class="chevron">▾</span>
                    </button>
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="<?= $base ?>/profile.php">👤 My Profile</a>
                        <a href="<?= $base ?>/collection.php">📚 Collection</a>
                        <a href="<?= $base ?>/minigames/">🎮 Minigames</a>
                        <a href="<?= $base ?>/minigames/lootbox.php">🎰 Loot Box</a>
                        <a href="<?= $base ?>/redeem.php">🎟️ Redeem Code</a>
                        <?php if (isAdmin()): ?>
                        <a href="<?= $base ?>/admin/index.php">⚙️ Admin Panel</a>
                        <a href="<?= $base ?>/admin/codes.php">🎟️ Redeem Codes</a>
                        <?php endif; ?>
                        <hr>
                        <a href="<?= $base ?>/auth/logout.php" class="logout-link">🚪 Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= $base ?>/auth/login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="<?= $base ?>/auth/register.php" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<div id="toast-container"></div>
