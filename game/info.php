<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
$db = (new \Config\Database())->getConnection();
$base = BASE_URL;

$maps    = [];
$towers  = [];
$enemies = [];
if ($db) {
    try { $maps    = $db->query("SELECT * FROM maps ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);    } catch(Exception $e){}
    try { $towers  = $db->query("SELECT * FROM towers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);  } catch(Exception $e){}
    try { $enemies = $db->query("SELECT * FROM enemies ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">
    <div class="page-header">
        <h1 class="page-title">🎮 Game Information</h1>
        <p>Everything you need to know about the game</p>
    </div>

    <!-- About -->
    <div class="game-about card">
        <h2>About Tower Defense</h2>
        <p>Build strategic towers across diverse maps to stop endless enemy waves. Earn gold, upgrade your arsenal, unlock new towers and enemies, and climb the global leaderboard.</p>
        <div class="game-features-grid">
            <div class="gf-item"><span class="gf-icon">🗼</span><span>Strategic Tower Placement</span></div>
            <div class="gf-item"><span class="gf-icon">🌊</span><span>Endless Enemy Waves</span></div>
            <div class="gf-item"><span class="gf-icon">🏆</span><span>Global Leaderboards</span></div>
            <div class="gf-item"><span class="gf-icon">🔓</span><span>Unlockable Content</span></div>
            <div class="gf-item"><span class="gf-icon">🗺️</span><span>Multiple Maps</span></div>
            <div class="gf-item"><span class="gf-icon">🎮</span><span>Browser Minigames</span></div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs" id="info-tabs" style="margin-top:2rem">
        <button class="tab-btn active" data-tab="maps-tab">Maps</button>
        <button class="tab-btn" data-tab="towers-tab">Towers</button>
        <button class="tab-btn" data-tab="enemies-tab">Enemies</button>
    </div>

    <!-- Maps -->
    <div class="tab-panel active" id="maps-tab">
        <?php if (empty($maps)): ?>
        <div class="empty-state"><div class="empty-icon">🗺️</div><p>No maps in database yet.</p></div>
        <?php else: ?>
        <div class="card-grid two">
        <?php foreach ($maps as $m): ?>
        <div class="game-card">
            <div class="game-card-icon">🗺️</div>
            <div class="game-card-body">
                <h3><?= sanitizeOutput($m['name']) ?></h3>
                <p><?= sanitizeOutput($m['description']) ?></p>
                <div class="game-card-stats">
                    <span>Grid: <?= (int)$m['grid_width'] ?> × <?= (int)$m['grid_height'] ?></span>
                </div>
                <a href="<?= $base ?>/minigames/tower_builder.php" class="btn btn-outline btn-sm">Preview Map</a>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Towers -->
    <div class="tab-panel" id="towers-tab">
        <?php if (empty($towers)): ?>
        <div class="empty-state"><div class="empty-icon">🗼</div><p>No towers in database yet.</p></div>
        <?php else: ?>
        <div class="card-grid three">
        <?php foreach ($towers as $t): ?>
        <div class="game-card">
            <div class="game-card-icon">🗼</div>
            <div class="game-card-body">
                <h3><?= sanitizeOutput($t['name']) ?> <?= !$t['unlocked_by_default']?'🔒':'' ?></h3>
                <p><?= sanitizeOutput($t['description']) ?></p>
                <div class="stat-bar-list">
                    <div class="sbl-row"><span>Damage</span><div class="sbl-bar"><div class="sbl-fill" style="width:<?= min(100, $t['damage']) ?>%"></div></div><span><?= $t['damage'] ?></span></div>
                    <div class="sbl-row"><span>Speed</span><div class="sbl-bar"><div class="sbl-fill" style="width:<?= min(100,$t['attack_speed']*20) ?>%"></div></div><span><?= $t['attack_speed'] ?>/s</span></div>
                    <div class="sbl-row"><span>Range</span><div class="sbl-bar"><div class="sbl-fill" style="width:<?= min(100,($t['range']/200)*100) ?>%"></div></div><span><?= $t['range'] ?></span></div>
                </div>
                <div class="game-card-cost">💰 <?= formatNumber($t['cost']) ?> gold</div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Enemies -->
    <div class="tab-panel" id="enemies-tab">
        <?php if (empty($enemies)): ?>
        <div class="empty-state"><div class="empty-icon">👾</div><p>No enemies in database yet.</p></div>
        <?php else: ?>
        <div class="card-grid three">
        <?php foreach ($enemies as $e): ?>
        <div class="game-card enemy-card">
            <div class="game-card-icon">👾</div>
            <div class="game-card-body">
                <h3><?= sanitizeOutput($e['name']) ?></h3>
                <p><?= sanitizeOutput($e['description']) ?></p>
                <div class="stat-bar-list">
                    <div class="sbl-row"><span>HP</span><div class="sbl-bar"><div class="sbl-fill enemy-fill" style="width:<?= min(100,($e['health']/300)*100) ?>%"></div></div><span><?= $e['health'] ?></span></div>
                    <div class="sbl-row"><span>Speed</span><div class="sbl-bar"><div class="sbl-fill enemy-fill" style="width:<?= min(100,$e['speed']*33) ?>%"></div></div><span><?= $e['speed'] ?></span></div>
                </div>
                <div class="game-card-cost">🏆 <?= $e['reward'] ?> gold reward</div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
document.querySelectorAll('#info-tabs .tab-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('#info-tabs .tab-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).classList.add('active');
    });
});
</script>
