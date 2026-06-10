<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
$base    = BASE_URL;
$coins   = 0;
$uid     = getCurrentUserId();
if (isLoggedIn()) {
    try {
        $db = (new \Config\Database())->getConnection();
        $s  = $db->prepare("SELECT coins FROM users WHERE id=?");
        $s->execute([$uid]);
        $coins = (int)($s->fetchColumn() ?? 0);
    } catch(Exception $e){}
}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">

    <div class="page-header">
        <h1 class="page-title">🎮 Minigames</h1>
        <p>Play to earn coins, unlock rewards and climb the leaderboard</p>
    </div>

    <?php if (isLoggedIn()): ?>
    <div class="lootbox-balance" style="margin-bottom:2rem">
        <div class="lb-coin-display">
            <span class="lb-coin-icon">🪙</span>
            <span class="lb-coin-val"><?= number_format($coins) ?></span>
            <span class="lb-coin-label">coins available</span>
        </div>
        <a href="<?= $base ?>/minigames/lootbox.php" class="btn btn-primary btn-sm">Open Loot Box →</a>
    </div>
    <?php endif; ?>

    <div class="mg-landing-grid">

        <!-- Tower Builder Challenge -->
        <div class="mg-card">
            <div class="mg-card-banner" style="background:linear-gradient(135deg,#0d2137,#1a3a5c)">🗼</div>
            <div class="mg-card-body">
                <div class="mg-card-title">Tower Builder Challenge</div>
                <div class="mg-card-desc">
                    Place towers on a budget to survive enemy waves. Earn coins and unlock towers
                    by reaching score milestones. Multiple waves with increasing difficulty.
                </div>
                <div class="mg-card-meta">
                    <span class="mg-tag free">Free to play</span>
                    <span class="mg-tag coins">Earns 🪙 coins</span>
                    <span class="mg-tag">Unlocks towers</span>
                </div>
                <a href="<?= $base ?>/minigames/tower_builder.php" class="btn btn-primary btn-full" style="margin-top:12px">Play Now</a>
            </div>
        </div>

        <!-- Endless Survival Trial -->
        <div class="mg-card">
            <div class="mg-card-banner" style="background:linear-gradient(135deg,#1a0a0a,#3d1010)">⚔️</div>
            <div class="mg-card-body">
                <div class="mg-card-title">Endless Survival Trial</div>
                <div class="mg-card-desc">
                    Survive infinite waves with combo multipliers. The longer you last, the more
                    coins you earn. Reach wave milestones to unlock the Machine Gun Tower and Tank enemies.
                </div>
                <div class="mg-card-meta">
                    <span class="mg-tag free">Free to play</span>
                    <span class="mg-tag coins">Earns 🪙 coins</span>
                    <span class="mg-tag">Leaderboard</span>
                </div>
                <a href="<?= $base ?>/minigames/survival_trial.php" class="btn btn-primary btn-full" style="margin-top:12px">Play Now</a>
            </div>
        </div>

        <!-- Loot Box -->
        <div class="mg-card">
            <div class="mg-card-banner" style="background:linear-gradient(135deg,#1a0d2e,#3d1a5c)">🎰</div>
            <div class="mg-card-body">
                <div class="mg-card-title">Loot Box</div>
                <div class="mg-card-desc">
                    Spend coins on a weighted-random spin. Win towers, cosmetics, badges, avatars and
                    titles. Legendary items have a 1-in-1000 equivalent chance. Mega Spin gives
                    5× better legendary odds.
                </div>
                <div class="mg-card-meta">
                    <span class="mg-tag coins">Costs 🪙 100 / 500</span>
                    <span class="mg-tag" style="color:var(--gold);border-color:rgba(255,213,79,.3)">Legendary drops</span>
                </div>
                <?php if (isLoggedIn()): ?>
                <a href="<?= $base ?>/minigames/lootbox.php" class="btn btn-outline btn-full" style="margin-top:12px;border-color:var(--gold);color:var(--gold)">Open Loot Box (<?= number_format($coins) ?> 🪙)</a>
                <?php else: ?>
                <a href="<?= $base ?>/auth/login.php" class="btn btn-outline btn-full" style="margin-top:12px">Login to Spin</a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- How coins work -->
    <div class="card" style="margin-top:2.5rem">
        <h3 style="margin-bottom:1rem">🪙 How to Earn Coins</h3>
        <div class="card-grid three">
            <div style="text-align:center;padding:12px">
                <div style="font-size:2rem;margin-bottom:8px">🏆</div>
                <strong>Score Points</strong>
                <p style="color:var(--text-muted);font-size:13px;margin-top:4px">Every 100 score = 1 coin</p>
            </div>
            <div style="text-align:center;padding:12px">
                <div style="font-size:2rem;margin-bottom:8px">🌊</div>
                <strong>Survive Waves</strong>
                <p style="color:var(--text-muted);font-size:13px;margin-top:4px">Each wave cleared = 5 coins</p>
            </div>
            <div style="text-align:center;padding:12px">
                <div style="font-size:2rem;margin-bottom:8px">🎰</div>
                <strong>Spin the Loot Box</strong>
                <p style="color:var(--text-muted);font-size:13px;margin-top:4px">Standard 100 🪙 · Mega 500 🪙</p>
            </div>
        </div>
    </div>

    <!-- Unlock roadmap -->
    <div class="card" style="margin-top:1.5rem">
        <h3 style="margin-bottom:1rem">🔓 Unlock Roadmap</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Item</th><th>Rarity</th><th>How to Unlock</th></tr></thead>
                <tbody>
                    <tr><td>🎯 Sniper Tower</td>   <td><span class="rarity-badge rarity-rare">Rare</span></td>    <td>Reach 1,000 total score</td></tr>
                    <tr><td>⚡ Machine Gun Tower</td><td><span class="rarity-badge rarity-epic">Epic</span></td>    <td>Survive 10 waves in one run</td></tr>
                    <tr><td>🗺️ Desert Map</td>       <td><span class="rarity-badge rarity-uncommon">Uncommon</span></td><td>Reach 500 total score</td></tr>
                    <tr><td>⚔️ Survival Trial</td>   <td><span class="rarity-badge rarity-rare">Rare</span></td>    <td>Reach 2,000 total score</td></tr>
                    <tr><td>💨 Fast Enemy</td>       <td><span class="rarity-badge rarity-uncommon">Uncommon</span></td><td>Survive 5 waves in one run</td></tr>
                    <tr><td>🛡️ Tank Enemy</td>       <td><span class="rarity-badge rarity-rare">Rare</span></td>    <td>Survive 15 waves in one run</td></tr>
                    <tr><td>❄️ Frost Tower</td>      <td><span class="rarity-badge rarity-legendary">Legendary</span></td><td>Loot Box spin (~0.1% chance)</td></tr>
                    <tr><td>🦅 Phoenix Avatar</td>   <td><span class="rarity-badge rarity-legendary">Legendary</span></td><td>Loot Box spin (~0.1% chance)</td></tr>
                    <tr><td>💎 Mythic Badge</td>     <td><span class="rarity-badge rarity-legendary">Legendary</span></td><td>Loot Box spin (~0.1% chance)</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
