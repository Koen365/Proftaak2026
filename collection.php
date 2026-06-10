<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
$db  = (new \Config\Database())->getConnection();
$uid = getCurrentUserId();
$base = BASE_URL;

// All unlockables (include unlock condition columns)
$all = [];
if ($db) {
    try {
        $all = $db->query("SELECT * FROM unlockables ORDER BY
            FIELD(rarity,'common','uncommon','rare','epic','legendary'), type, name")
            ->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e){}
}
// User's unlocked IDs + timestamps
$ownedMap = []; // id => unlocked_at
if ($db) {
    try {
        $s = $db->prepare("SELECT unlockable_id, unlocked_at FROM user_unlockables WHERE user_id=?");
        $s->execute([$uid]);
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ownedMap[$row['unlockable_id']] = $row['unlocked_at'];
        }
    } catch(Exception $e){}
}
// Auto-grant default items
foreach ($all as $item) {
    if ($item['unlocked_by_default'] && !isset($ownedMap[$item['id']])) {
        $ownedMap[$item['id']] = date('Y-m-d H:i:s');
    }
}
$owned = array_keys($ownedMap);

// User progress stats (for unlock hints)
$manager    = new \Classes\UnlockManager($db);
$progress   = $manager->getProgressUnlocks($uid);
$totalScore = $progress['total_score'];
$totalWaves = $progress['total_waves'];
$coins      = $progress['coins'];

// Recently unlocked (last 3 unlocks by time)
$recentIds = [];
if (!empty($ownedMap)) {
    arsort($ownedMap); // sort by date desc
    $recentIds = array_slice(array_keys($ownedMap), 0, 3);
}
$total   = count($all);
$unlockedCount = count($owned);
$pct     = $total > 0 ? round($unlockedCount/$total*100) : 0;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">
    <div class="page-header">
        <h1 class="page-title">📚 Collection Book</h1>
        <p>Track everything you've unlocked</p>
    </div>

    <!-- Completion bar -->
    <div class="collection-progress">
        <div class="cp-info">
            <span><?= $unlockedCount ?> / <?= $total ?> unlocked</span>
            <span class="cp-pct"><?= $pct ?>% complete</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
    </div>

    <!-- Filters -->
    <div class="collection-controls">
        <select id="col-type" class="form-input">
            <option value="all">All Types</option>
            <option value="tower">Towers</option>
            <option value="enemy">Enemies</option>
            <option value="map">Maps</option>
            <option value="minigame">Minigames</option>
        </select>
        <select id="col-rarity" class="form-input">
            <option value="all">All Rarities</option>
            <option value="common">Common</option>
            <option value="uncommon">Uncommon</option>
            <option value="rare">Rare</option>
            <option value="epic">Epic</option>
            <option value="legendary">Legendary</option>
        </select>
        <input type="text" id="col-search" class="form-input" placeholder="Search…">
        <label class="col-toggle"><input type="checkbox" id="col-locked"> Show locked only</label>
    </div>

    <div class="collection-grid" id="col-grid">
    <?php foreach ($all as $item):
        $isOwned  = in_array($item['id'], $owned);
        $isRecent = in_array($item['id'], $recentIds);
        $rarClass = getRarityClass($item['rarity']);
        $typeIcons = ['tower'=>'🗼','enemy'=>'👾','map'=>'🗺️','minigame'=>'🎮',
                      'cosmetic'=>'✨','badge'=>'🏅','avatar'=>'👤','title'=>'📛'];
        $icon = $typeIcons[$item['type']] ?? '📦';

        // Build unlock hint for locked items
        $hint = '';
        if (!$isOwned) {
            if ($item['unlock_type'] === 'score' && $item['unlock_score']) {
                $pct  = min(100, round($totalScore / $item['unlock_score'] * 100));
                $hint = "Reach " . number_format($item['unlock_score']) . " total score ($pct%)";
            } elseif ($item['unlock_type'] === 'waves' && $item['unlock_waves']) {
                $pct  = min(100, round($totalWaves / $item['unlock_waves'] * 100));
                $hint = "Survive " . $item['unlock_waves'] . " waves in one game ($pct%)";
            } elseif ($item['unlock_type'] === 'lootbox') {
                $hint = "Spin the Loot Box to unlock";
            }
        }
    ?>
    <div class="col-item <?= $isOwned?'':'col-locked' ?> <?= $isRecent?'col-recent':'' ?> <?= $rarClass ?>"
         data-type="<?= sanitizeOutput($item['type']) ?>"
         data-rarity="<?= sanitizeOutput($item['rarity']) ?>"
         data-name="<?= strtolower(sanitizeOutput($item['name'])) ?>"
         data-owned="<?= $isOwned?'1':'0' ?>">
        <?php if ($isRecent && $isOwned): ?>
        <div class="col-new-badge">NEW!</div>
        <?php endif; ?>
        <div class="col-item-img">
            <?= $icon ?>
            <?php if (!$isOwned): ?><div class="col-lock-overlay">🔒</div><?php endif; ?>
        </div>
        <div class="col-item-body">
            <div class="col-item-name"><?= sanitizeOutput($item['name']) ?></div>
            <div class="col-item-meta">
                <span class="col-type-badge"><?= ucfirst($item['type']) ?></span>
                <span class="rarity-badge <?= $rarClass ?>"><?= getRarityDisplay($item['rarity']) ?></span>
            </div>
            <?php if ($isOwned): ?>
                <div class="col-owned-badge">✓ Owned</div>
            <?php elseif ($hint): ?>
                <div class="col-hint"><?= sanitizeOutput($hint) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($all)): ?>
    <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-icon">📚</div>
        <p>No collection items found. Set up the database first.</p>
    </div>
    <?php endif; ?>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
(function(){
    var typeEl   = document.getElementById('col-type');
    var rarEl    = document.getElementById('col-rarity');
    var searchEl = document.getElementById('col-search');
    var lockEl   = document.getElementById('col-locked');

    function filter(){
        var t = typeEl.value, r = rarEl.value, q = searchEl.value.toLowerCase(), lockedOnly = lockEl.checked;
        document.querySelectorAll('.col-item').forEach(function(el){
            var ok = (t==='all'||el.dataset.type===t)
                  && (r==='all'||el.dataset.rarity===r)
                  && (!q||el.dataset.name.includes(q))
                  && (!lockedOnly||el.dataset.owned==='0');
            el.style.display = ok ? '' : 'none';
        });
    }
    [typeEl,rarEl,searchEl,lockEl].forEach(function(el){ el.addEventListener('change',filter); el.addEventListener('input',filter); });
})();
</script>
