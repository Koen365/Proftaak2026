<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn())  { redirect(BASE_URL . '/auth/login.php'); }
if (!isAdmin())     { setFlash('Access denied.','error'); redirect(BASE_URL . '/index.php'); }

$db    = (new \Config\Database())->getConnection();
$stats = ['users'=>0,'scores'=>0,'news'=>0];
if ($db) {
    try { $stats['users']  = $db->query("SELECT COUNT(*) FROM users")->fetchColumn(); }  catch(Exception $e){}
    try { $stats['scores'] = $db->query("SELECT COUNT(*) FROM scores")->fetchColumn(); } catch(Exception $e){}
    try { $stats['news']   = $db->query("SELECT COUNT(*) FROM news")->fetchColumn(); }   catch(Exception $e){}
}
$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>"><?= sanitizeOutput($flash['message']) ?></div>
<?php endif; ?>
<section class="section">
    <div class="page-header">
        <h1 class="page-title">⚙️ Admin Panel</h1>
        <p>Site management dashboard</p>
    </div>

    <div class="card-grid three">
        <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-val"><?= $stats['users'] ?></div><div class="stat-label">Total Users</div></div>
        <div class="stat-card"><div class="stat-icon">🏆</div><div class="stat-val"><?= $stats['scores'] ?></div><div class="stat-label">Total Scores</div></div>
        <div class="stat-card"><div class="stat-icon">📰</div><div class="stat-val"><?= $stats['news'] ?></div><div class="stat-label">News Articles</div></div>
    </div>

    <div class="card-grid three" style="margin-top:2rem">
        <a href="<?= $base ?>/admin/users.php" class="action-card">
            <div class="ac-icon">👥</div>
            <div><h3>Manage Users</h3><p>View, edit and delete accounts</p></div>
        </a>
        <a href="<?= $base ?>/admin/scores.php" class="action-card">
            <div class="ac-icon">📊</div>
            <div><h3>Manage Scores</h3><p>Review and delete scores</p></div>
        </a>
        <a href="<?= $base ?>/admin/news.php" class="action-card">
            <div class="ac-icon">📰</div>
            <div><h3>Post News</h3><p>Create and manage news articles</p></div>
        </a>
        <a href="<?= $base ?>/admin/codes.php" class="action-card">
            <div class="ac-icon">🎟️</div>
            <div><h3>Redeem Codes</h3><p>Create and manage reward codes</p></div>
        </a>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
