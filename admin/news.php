<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
if (!isAdmin())    { setFlash('Access denied.','error'); redirect(BASE_URL . '/index.php'); }

$db = (new \Config\Database())->getConnection();
$base = BASE_URL;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title && $content) {
            try {
                $db->prepare("INSERT INTO news (title,content,author_id) VALUES (?,?,?)")
                   ->execute([$title, $content, getCurrentUserId()]);
                setFlash('News article published!','success');
            } catch(Exception $e){ setFlash('Failed to publish.','error'); }
        } else { setFlash('Title and content are required.','error'); }
        redirect(BASE_URL . '/admin/news.php');
    }
    if ($action === 'delete' && isset($_POST['news_id'])) {
        try { $db->prepare("DELETE FROM news WHERE id=?")->execute([(int)$_POST['news_id']]); setFlash('Article deleted.','success'); }
        catch(Exception $e){ setFlash('Delete failed.','error'); }
        redirect(BASE_URL . '/admin/news.php');
    }
}

$articles = [];
try {
    $articles = $db->query("SELECT n.id,n.title,n.content,n.created_at,u.username as author
                            FROM news n LEFT JOIN users u ON n.author_id=u.id
                            ORDER BY n.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e){}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>"><?= sanitizeOutput($flash['message']) ?></div>
<?php endif; ?>
<section class="section">
    <div class="page-header">
        <h1 class="page-title">📰 Manage News</h1>
        <a href="<?= $base ?>/admin/index.php" class="btn btn-outline btn-sm">← Admin Panel</a>
    </div>

    <!-- Create form -->
    <div class="card" style="margin-bottom:2rem">
        <h3 style="margin-bottom:1rem">Post New Article</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input class="form-input" type="text" name="title" required placeholder="Article title…">
            </div>
            <div class="form-group">
                <label class="form-label">Content</label>
                <textarea class="form-input" name="content" rows="6" required placeholder="Write your article here…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Publish</button>
        </form>
    </div>

    <!-- Existing articles -->
    <h3>Published Articles</h3>
    <?php if (empty($articles)): ?>
    <div class="empty-state"><div class="empty-icon">📰</div><p>No articles yet.</p></div>
    <?php else: foreach ($articles as $a): ?>
    <div class="news-article" style="margin-bottom:1rem">
        <div class="news-article-header">
            <strong><?= sanitizeOutput($a['title']) ?></strong>
            <span class="news-date"><?= formatTimestamp($a['created_at']) ?></span>
            <span>by <?= sanitizeOutput($a['author']??'Admin') ?></span>
            <form method="POST" action="" style="display:inline;margin-left:1rem" onsubmit="return confirm('Delete this article?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="news_id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn-sm" style="background:#f44336;color:#fff">Delete</button>
            </form>
        </div>
        <p style="margin-top:.5rem;color:var(--text-muted)"><?= sanitizeOutput(mb_substr($a['content'],0,200)) ?>…</p>
    </div>
    <?php endforeach; endif; ?>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
