<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
$db = (new \Config\Database())->getConnection();
$news = [];
if ($db) {
    try {
        $news = $db->query("SELECT n.id, n.title, n.content, n.created_at, u.username as author
                            FROM news n LEFT JOIN users u ON n.author_id=u.id
                            ORDER BY n.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
}
$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">
    <div class="page-header">
        <h1 class="page-title">📰 News & Updates</h1>
        <p>Latest patches, events and announcements</p>
    </div>

    <?php if (empty($news)): ?>
    <!-- Static news when DB is empty -->
    <div class="news-list">
        <?php $staticNews = [
            ['tag'=>'Update',  'title'=>'Platform Launch!',               'date'=>'May 2026', 'content'=>'TowerDefenseHQ is officially live. Register an account, post your scores, and climb the leaderboard.'],
            ['tag'=>'Feature', 'title'=>'Tower Builder Challenge',        'date'=>'May 2026', 'content'=>'Play the browser-based Tower Builder minigame to earn exclusive cosmetics, badges and titles.'],
            ['tag'=>'Feature', 'title'=>'Endless Survival Trial Live',    'date'=>'May 2026', 'content'=>'How long can you survive? The Endless Survival Trial has its own dedicated leaderboard.'],
            ['tag'=>'Guide',   'title'=>'Wiki is Open',                   'date'=>'May 2026', 'content'=>'Check the wiki for tower guides, enemy breakdowns, and full strategy articles for every game mode.'],
        ]; foreach($staticNews as $i => $n): ?>
        <article class="news-article <?= $i===0?'news-featured':'' ?>">
            <div class="news-article-header">
                <span class="news-tag"><?= $n['tag'] ?></span>
                <span class="news-date"><?= $n['date'] ?></span>
            </div>
            <h2 class="news-article-title"><?= $n['title'] ?></h2>
            <p><?= $n['content'] ?></p>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="news-list">
        <?php foreach($news as $i => $n): ?>
        <article class="news-article <?= $i===0?'news-featured':'' ?>">
            <div class="news-article-header">
                <span class="news-tag">Update</span>
                <span class="news-date"><?= formatTimestamp($n['created_at']) ?></span>
                <?php if($n['author']): ?><span class="news-author">by <?= sanitizeOutput($n['author']) ?></span><?php endif; ?>
            </div>
            <h2 class="news-article-title"><?= sanitizeOutput($n['title']) ?></h2>
            <p><?= sanitizeOutput(mb_substr($n['content'],0,300)) ?><?= mb_strlen($n['content'])>300?'…':'' ?></p>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
    <div style="margin-top:2rem">
        <a href="<?= $base ?>/admin/news.php" class="btn btn-primary">+ Post News</a>
    </div>
    <?php endif; ?>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
