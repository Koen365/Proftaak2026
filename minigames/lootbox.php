<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');

$db      = (new \Config\Database())->getConnection();
$manager = new \Classes\UnlockManager($db);
$uid     = getCurrentUserId();
$base    = BASE_URL;

$coins   = $manager->getCoins($uid);
$history = $manager->getSpinHistory($uid, 10);

// Rarity icons/colours (shared with JS below)
$rarityMeta = [
    'common'    => ['icon'=>'⬜', 'label'=>'Common',    'class'=>'rarity-common'],
    'uncommon'  => ['icon'=>'🟩', 'label'=>'Uncommon',  'class'=>'rarity-uncommon'],
    'rare'      => ['icon'=>'🟦', 'label'=>'Rare',      'class'=>'rarity-rare'],
    'epic'      => ['icon'=>'🟪', 'label'=>'Epic',      'class'=>'rarity-epic'],
    'legendary' => ['icon'=>'🟨', 'label'=>'Legendary', 'class'=>'rarity-legendary'],
];
?>
<?php include BASE_PATH . '/includes/header.php'; ?>

<main class="container">
<section class="section">

    <div class="page-header">
        <h1 class="page-title">🎰 Loot Box</h1>
        <p>Spin to win exclusive towers, cosmetics, badges and more</p>
    </div>

    <!-- Coin balance -->
    <div class="lootbox-balance">
        <div class="lb-coin-display">
            <span class="lb-coin-icon">🪙</span>
            <span class="lb-coin-val" id="coin-display"><?= number_format($coins) ?></span>
            <span class="lb-coin-label">coins</span>
        </div>
        <a href="<?= $base ?>/minigames/tower_builder.php" class="btn btn-outline btn-sm">Earn More Coins →</a>
    </div>

    <!-- Rarity odds info -->
    <div class="lootbox-odds">
        <h3>Drop Rates</h3>
        <div class="odds-grid">
            <div class="odds-item common">    <span>⬜</span> Common     <strong>~37%</strong></div>
            <div class="odds-item uncommon">  <span>🟩</span> Uncommon   <strong>~18%</strong></div>
            <div class="odds-item rare">      <span>🟦</span> Rare       <strong>~20%</strong></div>
            <div class="odds-item epic">      <span>🟪</span> Epic       <strong>~24%</strong></div>
            <div class="odds-item legendary"> <span>🟨</span> Legendary  <strong>~1%</strong> <span class="odds-note">(×5 on Mega)</span></div>
        </div>
    </div>

    <!-- The box -->
    <div class="lootbox-stage">

        <!-- Spinning reel -->
        <div class="lb-reel-wrap">
            <div class="lb-reel-window">
                <div class="lb-reel" id="lb-reel">
                    <!-- Items injected by JS -->
                </div>
            </div>
            <div class="lb-reel-pointer"></div>
        </div>

        <!-- Result card (hidden until spin) -->
        <div class="lb-result-card" id="lb-result" style="display:none">
            <div class="lb-result-inner" id="lb-result-inner"></div>
        </div>

        <!-- Spin buttons -->
        <div class="lb-actions">
            <button class="btn btn-outline lb-spin-btn" id="btn-standard" data-type="standard">
                🎰 Spin  <span class="lb-btn-cost">100 🪙</span>
            </button>
            <button class="btn btn-primary lb-spin-btn" id="btn-mega" data-type="mega">
                ⚡ Mega Spin  <span class="lb-btn-cost">500 🪙</span>
                <span class="lb-btn-tag">5× Legendary odds</span>
            </button>
        </div>

        <p id="lb-error" class="lb-error" style="display:none"></p>
    </div>

    <!-- Progress unlocks (earn by playing TD) -->
    <div class="section" style="margin-top:3rem">
        <h2 class="section-title">🎮 Unlock by Playing</h2>
        <p style="color:var(--text-muted);margin-bottom:1.5rem">
            These items unlock automatically when you reach a score or wave milestone.
            Play the minigames to progress!
        </p>
        <?php
        $progress = $manager->getProgressUnlocks($uid);
        $items    = $progress['items'];
        ?>
        <div class="unlock-progress-grid">
        <?php foreach ($items as $item):
            $meta = $rarityMeta[$item['rarity']] ?? $rarityMeta['common'];
            $owned = (bool)$item['owned'];
        ?>
        <div class="unlock-card <?= $owned ? 'unlock-owned' : '' ?>">
            <div class="unlock-card-top">
                <span class="unlock-type-icon">
                    <?= ['tower'=>'🗼','enemy'=>'👾','map'=>'🗺️','minigame'=>'🎮','cosmetic'=>'✨','badge'=>'🏅','avatar'=>'👤','title'=>'📛'][$item['type']] ?? '📦' ?>
                </span>
                <span class="rarity-badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                <?php if ($owned): ?><span class="owned-check">✅</span><?php endif; ?>
            </div>
            <div class="unlock-card-name"><?= sanitizeOutput($item['name']) ?></div>
            <div class="unlock-card-desc"><?= sanitizeOutput($item['description']) ?></div>
            <?php if (!$owned): ?>
            <div class="unlock-card-progress">
                <div class="progress-bar" style="margin-bottom:4px">
                    <div class="progress-fill" style="width:<?= $item['progress_pct'] ?>%"></div>
                </div>
                <div class="unlock-progress-label"><?= sanitizeOutput($item['progress_label']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Spin history -->
    <?php if (!empty($history)): ?>
    <div class="section" style="margin-top:2rem">
        <h2 class="section-title">📜 Recent Spins</h2>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Item</th><th>Type</th><th>Rarity</th><th>Cost</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($history as $h):
                    $meta = $rarityMeta[$h['rarity']] ?? $rarityMeta['common'];
                ?>
                <tr>
                    <td><?= sanitizeOutput($h['name']) ?></td>
                    <td><?= ucfirst($h['type']) ?></td>
                    <td><span class="rarity-badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span></td>
                    <td>🪙 <?= number_format($h['spin_cost']) ?></td>
                    <td><?= formatTimestamp($h['spun_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</section>
</main>

<?php include BASE_PATH . '/includes/footer.php'; ?>

<style>
/* ─── Loot box page ──────────────────────────────── */
.lootbox-balance {
  display:flex; align-items:center; justify-content:space-between;
  background:var(--card); border:1px solid var(--border);
  border-radius:var(--radius); padding:16px 24px; margin-bottom:24px;
}
.lb-coin-display { display:flex; align-items:center; gap:10px; }
.lb-coin-icon  { font-size:1.8rem; }
.lb-coin-val   { font-family:var(--font-head); font-size:2rem; font-weight:700; color:var(--gold); }
.lb-coin-label { color:var(--text-muted); font-size:14px; }

/* Odds */
.lootbox-odds { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:20px 24px; margin-bottom:28px; }
.lootbox-odds h3 { font-size:14px; text-transform:uppercase; letter-spacing:.6px; color:var(--text-muted); margin-bottom:14px; }
.odds-grid { display:flex; gap:12px; flex-wrap:wrap; }
.odds-item { display:flex; align-items:center; gap:8px; font-size:13px; padding:8px 14px; border-radius:8px; background:var(--bg3); border:1px solid var(--border); }
.odds-item strong { color:var(--text); margin-left:4px; }
.odds-note { color:var(--accent); font-size:11px; margin-left:4px; }
.odds-item.legendary { border-color:rgba(255,213,79,.3); }
.odds-item.epic      { border-color:rgba(171,71,188,.3); }
.odds-item.rare      { border-color:rgba(66,165,245,.3); }

/* Stage */
.lootbox-stage { text-align:center; }

/* Reel */
.lb-reel-wrap { position:relative; display:inline-block; margin-bottom:24px; }
.lb-reel-window {
  width:640px; max-width:100%; height:110px;
  overflow:hidden; border-radius:12px;
  border:2px solid var(--border); background:var(--bg2);
  position:relative;
}
.lb-reel-window::before,
.lb-reel-window::after {
  content:''; position:absolute; top:0; bottom:0; width:80px; z-index:2; pointer-events:none;
}
.lb-reel-window::before { left:0;  background:linear-gradient(90deg,var(--bg2),transparent); }
.lb-reel-window::after  { right:0; background:linear-gradient(-90deg,var(--bg2),transparent); }
.lb-reel {
  display:flex; align-items:center; gap:8px; padding:12px 8px;
  transition:transform 0s;
  will-change:transform;
}
.lb-reel-item {
  flex-shrink:0; width:88px; height:88px;
  background:var(--card); border:2px solid var(--border);
  border-radius:10px; display:flex; flex-direction:column;
  align-items:center; justify-content:center; gap:4px;
  font-size:11px; font-weight:600; user-select:none;
}
.lb-reel-item .ri-icon { font-size:26px; }
.lb-reel-item.common    { border-color:rgba(144,164,174,.5); }
.lb-reel-item.uncommon  { border-color:rgba(102,187,106,.5); }
.lb-reel-item.rare      { border-color:rgba(66,165,245,.5); }
.lb-reel-item.epic      { border-color:rgba(171,71,188,.5); }
.lb-reel-item.legendary { border-color:var(--gold); box-shadow:0 0 12px rgba(255,213,79,.3); }

.lb-reel-pointer {
  position:absolute; top:0; bottom:0; left:50%; transform:translateX(-50%);
  width:2px; background:var(--accent); z-index:3;
  box-shadow:0 0 8px var(--accent);
}
.lb-reel-pointer::before,
.lb-reel-pointer::after {
  content:''; position:absolute; left:50%; transform:translateX(-50%);
  border:6px solid transparent;
}
.lb-reel-pointer::before { top:-1px;    border-top-color:var(--accent); }
.lb-reel-pointer::after  { bottom:-1px; border-bottom-color:var(--accent); }

/* Result card */
.lb-result-card {
  margin:0 auto 24px; max-width:320px;
  animation:popIn .4s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popIn { from{transform:scale(.5);opacity:0} to{transform:scale(1);opacity:1} }
.lb-result-inner {
  background:var(--card); border:2px solid var(--border);
  border-radius:var(--radius); padding:24px; text-align:center;
}
.lb-result-inner .res-icon   { font-size:4rem; margin-bottom:8px; }
.lb-result-inner .res-name   { font-size:1.4rem; font-weight:700; margin-bottom:4px; }
.lb-result-inner .res-type   { color:var(--text-muted); font-size:13px; margin-bottom:8px; }
.lb-result-inner .res-desc   { color:var(--text-muted); font-size:13px; margin-bottom:14px; }
.lb-result-inner .res-new    { color:var(--success); font-weight:700; font-size:14px; }
.lb-result-inner .res-dupe   { color:var(--warning); font-size:13px; }

/* Spin buttons */
.lb-actions { display:flex; justify-content:center; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
.lb-spin-btn { flex-direction:column; gap:2px; padding:14px 28px; position:relative; }
.lb-btn-cost { font-size:12px; opacity:.8; }
.lb-btn-tag  { font-size:10px; background:rgba(255,213,79,.2); color:var(--gold); border-radius:4px; padding:1px 6px; }
.lb-error { color:var(--error); font-size:14px; margin-top:8px; }

/* Progress unlock grid */
.unlock-progress-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
.unlock-card {
  background:var(--card); border:1px solid var(--border);
  border-radius:var(--radius); padding:16px;
  transition:var(--transition);
}
.unlock-card.unlock-owned { border-color:var(--success); opacity:.7; }
.unlock-card:not(.unlock-owned):hover { border-color:var(--accent); }
.unlock-card-top { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
.unlock-type-icon { font-size:1.4rem; }
.owned-check { margin-left:auto; }
.unlock-card-name { font-weight:700; font-size:14px; margin-bottom:4px; }
.unlock-card-desc { color:var(--text-muted); font-size:12px; margin-bottom:10px; line-height:1.5; }
.unlock-progress-label { font-size:11px; color:var(--text-muted); }

/* Rarity badges */
.rarity-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.rarity-common    { background:rgba(144,164,174,.15); color:#90a4ae; }
.rarity-uncommon  { background:rgba(102,187,106,.15); color:#66bb6a; }
.rarity-rare      { background:rgba(66,165,245,.15);  color:#42a5f5; }
.rarity-epic      { background:rgba(171,71,188,.15);  color:#ab47bc; }
.rarity-legendary { background:rgba(255,213,79,.15);  color:var(--gold); border:1px solid rgba(255,213,79,.3); }
</style>

<script>
(function () {

// ── Reel item definitions (mirrors DB loot pool) ──────────────────────────
const REEL_ITEMS = [
    {icon:'🏅', name:'Commander Badge', rarity:'common'},
    {icon:'👤', name:'Default Avatar',  rarity:'common'},
    {icon:'📛', name:'Rookie Title',    rarity:'common'},
    {icon:'☠️', name:'Poison Tower',    rarity:'uncommon'},
    {icon:'🎨', name:'Dark Theme',      rarity:'uncommon'},
    {icon:'💥', name:'Cannon Tower',    rarity:'rare'},
    {icon:'🖼️', name:'Pixel Skin',     rarity:'rare'},
    {icon:'🏆', name:'Survivor Badge',  rarity:'rare'},
    {icon:'🧑‍🎤', name:'Knight Avatar', rarity:'rare'},
    {icon:'📖', name:'Veteran Title',   rarity:'rare'},
    {icon:'⚡', name:'Tesla Tower',     rarity:'epic'},
    {icon:'💡', name:'Neon Skin',       rarity:'epic'},
    {icon:'🍀', name:'Lucky Badge',     rarity:'epic'},
    {icon:'🐉', name:'Dragon Avatar',   rarity:'epic'},
    {icon:'🖼️', name:'Gold Frame',     rarity:'epic'},
    {icon:'❄️', name:'Frost Tower',    rarity:'legendary'},
    {icon:'🌟', name:'Gold Theme',      rarity:'legendary'},
    {icon:'💎', name:'Mythic Badge',    rarity:'legendary'},
    {icon:'🦅', name:'Phoenix Avatar',  rarity:'legendary'},
    {icon:'👑', name:'Mythic Title',    rarity:'legendary'},
    {icon:'🗼', name:'Legend Badge',    rarity:'legendary'},
];

const RARITY_ICONS = {common:'⬜',uncommon:'🟩',rare:'🟦',epic:'🟪',legendary:'🟨'};

// ── Build reel ────────────────────────────────────────────────────────────
const reel = document.getElementById('lb-reel');
// Repeat items enough to scroll nicely
function buildReel(highlightName) {
    reel.innerHTML = '';
    // 4 loops + extra
    const allItems = [...REEL_ITEMS,...REEL_ITEMS,...REEL_ITEMS,...REEL_ITEMS,...REEL_ITEMS,...REEL_ITEMS];
    // Shuffle a bit
    allItems.sort(() => Math.random() - 0.5);
    allItems.forEach(function(item) {
        const el = document.createElement('div');
        el.className = 'lb-reel-item ' + item.rarity;
        el.innerHTML = '<span class="ri-icon">' + item.icon + '</span><span>' + item.name.split(' ')[0] + '</span>';
        reel.appendChild(el);
    });
    return allItems;
}
buildReel();

// ── Spin animation ────────────────────────────────────────────────────────
function animateSpin(wonItem, isMega, onDone) {
    const ITEM_W  = 96; // 88px + 8px gap
    const VISIBLE = 640;
    const CENTER  = VISIBLE / 2;

    // Re-build reel with winner placed near end
    const items = buildReel();
    const totalW = items.length * ITEM_W;

    // Place winner ~80% through reel
    const wonIdx = Math.floor(items.length * 0.82);
    const elList = reel.querySelectorAll('.lb-reel-item');
    if (elList[wonIdx]) {
        const ri = REEL_ITEMS.find(function(r){ return r.name === wonItem.name; });
        if (ri) {
            elList[wonIdx].className = 'lb-reel-item ' + wonItem.rarity;
            elList[wonIdx].querySelector('.ri-icon').textContent = ri ? ri.icon : '❓';
            elList[wonIdx].querySelector('span:last-child').textContent = wonItem.name.split(' ')[0];
        }
    }

    // Start offset
    reel.style.transition = 'none';
    reel.style.transform  = 'translateX(0)';

    const targetX  = -(wonIdx * ITEM_W - CENTER + ITEM_W / 2);
    const spinTime = isMega ? 4500 : 3200;

    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            reel.style.transition = 'transform ' + spinTime + 'ms cubic-bezier(0.25, 0.1, 0.1, 1)';
            reel.style.transform  = 'translateX(' + targetX + 'px)';
        });
    });

    setTimeout(onDone, spinTime + 200);
}

// ── Show result card ──────────────────────────────────────────────────────
function showResult(data) {
    const item = data.item;
    const ri   = REEL_ITEMS.find(function(r){ return r.name === item.name; }) || {icon:'❓'};
    const rc   = document.getElementById('lb-result');
    const rcIn = document.getElementById('lb-result-inner');

    const dupeMsg  = data.already_owned
        ? '<div class="res-dupe">⚠️ Already owned — no duplicate added</div>'
        : '<div class="res-new">🎉 NEW ITEM ADDED TO COLLECTION!</div>';

    rcIn.innerHTML = `
        <div class="res-icon">${ri.icon}</div>
        <div class="rarity-badge rarity-${item.rarity}" style="margin-bottom:8px">${item.rarity.toUpperCase()}</div>
        <div class="res-name">${item.name}</div>
        <div class="res-type">${item.type}</div>
        <div class="res-desc">${item.description || ''}</div>
        ${dupeMsg}
    `;
    rcIn.style.borderColor = {
        common:'#90a4ae',uncommon:'#66bb6a',rare:'#42a5f5',
        epic:'#ab47bc',legendary:'#ffd54f'
    }[item.rarity] || 'var(--border)';

    if (item.rarity === 'legendary') {
        rcIn.style.boxShadow = '0 0 30px rgba(255,213,79,.4)';
    }
    rc.style.display = '';
    // Bounce animation restart
    rc.style.animation = 'none';
    rc.offsetHeight; // force reflow
    rc.style.animation = '';
}

// ── Spin handler ──────────────────────────────────────────────────────────
var spinning = false;

document.querySelectorAll('.lb-spin-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (spinning) return;
        spinning = true;

        const type     = this.dataset.type;
        const errEl    = document.getElementById('lb-error');
        const resultEl = document.getElementById('lb-result');
        errEl.style.display    = 'none';
        resultEl.style.display = 'none';

        document.querySelectorAll('.lb-spin-btn').forEach(function(b){ b.disabled = true; });

        // Call API
        fetch('<?= $base ?>/api/spin.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ type: type })
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (!data.success) {
                errEl.textContent    = data.message || 'Spin failed.';
                errEl.style.display  = '';
                document.querySelectorAll('.lb-spin-btn').forEach(function(b){ b.disabled = false; });
                spinning = false;
                return;
            }

            // Update coin display
            const newCoins = parseInt(document.getElementById('coin-display').textContent.replace(/,/g,'')) - data.coins_spent;
            document.getElementById('coin-display').textContent = newCoins.toLocaleString();

            // Animate reel, then show result
            animateSpin(data.item, type === 'mega', function() {
                showResult(data);
                if (data.is_new) {
                    showToast('🎉 Unlocked: ' + data.item.name + '!', 'success', 5000);
                }
                document.querySelectorAll('.lb-spin-btn').forEach(function(b){ b.disabled = false; });
                spinning = false;
            });
        })
        .catch(function() {
            errEl.textContent   = 'Server error — try again.';
            errEl.style.display = '';
            document.querySelectorAll('.lb-spin-btn').forEach(function(b){ b.disabled = false; });
            spinning = false;
        });
    });
});

})();
</script>
