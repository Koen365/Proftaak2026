<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
if (!isAdmin())    { setFlash('Access denied.','error'); redirect(BASE_URL . '/index.php'); }

$db = (new \Config\Database())->getConnection();
$base = BASE_URL;

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'], $_POST['score_id'])) {
    if ($_POST['action']==='delete') {
        try { $db->prepare("DELETE FROM scores WHERE id=?")->execute([(int)$_POST['score_id']]); setFlash('Score deleted.','success'); }
        catch(Exception $e){ setFlash('Delete failed.','error'); }
        redirect(BASE_URL . '/admin/scores.php');
    }
}

$scores = [];
try {
    $scores = $db->query("SELECT s.id, u.username, m.name as map_name, s.score, s.waves_survived, s.time_survived, s.created_at
                          FROM scores s
                          LEFT JOIN users u ON s.user_id=u.id
                          LEFT JOIN maps m ON s.map_id=m.id
                          ORDER BY s.created_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e){}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>"><?= sanitizeOutput($flash['message']) ?></div>
<?php endif; ?>
<section class="section">
    <div class="page-header">
        <h1 class="page-title">📊 Manage Scores</h1>
        <a href="<?= $base ?>/admin/index.php" class="btn btn-outline btn-sm">← Admin Panel</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Player</th><th>Map</th><th>Score</th><th>Waves</th><th>Time</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($scores as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= sanitizeOutput($row['username']??'—') ?></td>
                <td><?= sanitizeOutput($row['map_name']??'—') ?></td>
                <td><?= formatNumber($row['score']) ?></td>
                <td><?= (int)$row['waves_survived'] ?></td>
                <td><?= floor($row['time_survived']/60) ?>m <?= $row['time_survived']%60 ?>s</td>
                <td><?= formatTimestamp($row['created_at']) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this score?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="score_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background:#f44336;color:#fff">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($scores)): ?><tr><td colspan="8" class="empty-cell">No scores recorded yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
