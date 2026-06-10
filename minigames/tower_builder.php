<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">
    <div class="page-header">
        <h1 class="page-title">🎮 Tower Builder Challenge</h1>
        <p>Place towers strategically to survive enemy waves</p>
    </div>

    <div class="minigame-layout">
        <!-- Sidebar -->
        <aside class="mg-sidebar">
            <div class="mg-panel">
                <h3>Resources</h3>
                <div class="mg-stat"><span>💰 Gold</span><strong id="mg-gold">500</strong></div>
                <div class="mg-stat"><span>🌊 Wave</span><strong id="mg-wave">1</strong></div>
                <div class="mg-stat"><span>❤️ Lives</span><strong id="mg-lives">20</strong></div>
                <div class="mg-stat"><span>🏆 Score</span><strong id="mg-score">0</strong></div>
            </div>
            <div class="mg-panel">
                <h3>Towers</h3>
                <div class="tower-palette" id="tower-palette">
                    <!-- Towers are rendered dynamically based on unlocks -->
                    <div id="tower-palette-loading" style="color:#aaa;font-size:0.85rem;padding:0.5rem">Loading towers…</div>
                </div>
            </div>
            <div class="mg-panel">
                <button id="mg-start-wave" class="btn btn-primary btn-full">▶ Start Wave</button>
                <button id="mg-reset" class="btn btn-outline btn-full" style="margin-top:.5rem">↺ Reset</button>
            </div>
            <div class="mg-panel">
                <h3>How to Play</h3>
                <ul class="mg-rules">
                    <li>Click a tower type, then click the grid to place</li>
                    <li>Towers cannot go on the path</li>
                    <li>Click a placed tower to remove it (+refund)</li>
                    <li>Press Start Wave to begin</li>
                    <li>Survive as many waves as possible!</li>
                    <li>Unlock special towers from the Loot Box!</li>
                </ul>
            </div>
        </aside>

        <!-- Game area -->
        <div class="mg-game-area">
            <div id="mg-grid" class="mg-grid"></div>
            <div id="mg-message" class="mg-message" style="display:none"></div>
        </div>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
// ============================================================
// Tower Builder Challenge – Self-contained game engine
// ============================================================
(function(){
const GRID_W = 16, GRID_H = 12;
const PATH = [
    {x:0,y:1},{x:1,y:1},{x:2,y:1},{x:3,y:1},{x:4,y:1},
    {x:4,y:2},{x:4,y:3},{x:4,y:4},{x:4,y:5},
    {x:5,y:5},{x:6,y:5},{x:7,y:5},{x:8,y:5},{x:9,y:5},{x:10,y:5},
    {x:10,y:4},{x:10,y:3},{x:10,y:2},{x:10,y:1},{x:10,y:0},
    {x:11,y:0},{x:12,y:0},{x:13,y:0},{x:14,y:0},{x:15,y:0}
];

// All possible tower definitions (including loot box towers)
const ALL_TOWER_DEFS = {
    basic:  {icon:'🗼', cost:100,  dmg:25,  spd:1.0, range:3, color:'#4fc3f7',  label:'Basic',       rarity:'default'},
    sniper: {icon:'🎯', cost:300,  dmg:120, spd:0.5, range:6, color:'#ab47bc',  label:'Sniper',      rarity:'default'},
    mg:     {icon:'⚡', cost:200,  dmg:12,  spd:5.0, range:2, color:'#ffd54f',  label:'Machine Gun', rarity:'default'},
    // Loot box towers
    poison: {icon:'☠️', cost:150,  dmg:18,  spd:1.5, range:3, color:'#66bb6a',  label:'Poison',      rarity:'uncommon',  unlockName:'Poison Tower'},
    cannon: {icon:'💥', cost:350,  dmg:80,  spd:0.8, range:4, color:'#ef5350',  label:'Cannon',      rarity:'rare',      unlockName:'Cannon Tower'},
    tesla:  {icon:'⚡', cost:450,  dmg:55,  spd:2.0, range:4, color:'#7e57c2',  label:'Tesla',       rarity:'epic',      unlockName:'Tesla Tower'},
    frost:  {icon:'❄️', cost:500,  dmg:35,  spd:1.2, range:4, color:'#29b6f6',  label:'Frost',       rarity:'legendary', unlockName:'Frost Tower'},
};

// TOWER_DEFS will be populated after fetching unlocks
let TOWER_DEFS = {};

let state = {gold:500, lives:20, wave:0, score:0, running:false};
let towers = {}; // key: "x,y"
let enemies = [];
let selectedType = 'basic';
let loopId = null;

// ---- Build grid ----
const grid = document.getElementById('mg-grid');
grid.style.gridTemplateColumns = `repeat(${GRID_W}, 1fr)`;
const cells = {};
for (let y=0;y<GRID_H;y++) for (let x=0;x<GRID_W;x++) {
    const cell = document.createElement('div');
    cell.className = 'mg-cell';
    cell.dataset.x = x; cell.dataset.y = y;
    const isPath = PATH.some(p=>p.x===x&&p.y===y);
    if (isPath) cell.classList.add('mg-path');
    cell.addEventListener('click', ()=>handleCellClick(x,y,cell));
    grid.appendChild(cell);
    cells[x+','+y] = cell;
}
// Mark start/end
cells[PATH[0].x+','+PATH[0].y]?.classList.add('mg-start');
cells[PATH[PATH.length-1].x+','+PATH[PATH.length-1].y]?.classList.add('mg-end');

// ---- Load unlocks and build tower palette ----
function buildTowerPalette(unlockedTowerNames) {
    const palette = document.getElementById('tower-palette');
    palette.innerHTML = '';

    // Always include base towers
    const availableTypes = ['basic', 'sniper', 'mg'];

    // Add loot box towers if unlocked
    ['poison', 'cannon', 'tesla', 'frost'].forEach(key => {
        const def = ALL_TOWER_DEFS[key];
        if (unlockedTowerNames.includes(def.unlockName)) {
            availableTypes.push(key);
        }
    });

    availableTypes.forEach((type, idx) => {
        const def = ALL_TOWER_DEFS[type];
        TOWER_DEFS[type] = def;

        const item = document.createElement('div');
        item.className = 'tp-item' + (idx === 0 ? ' selected' : '');
        item.dataset.type = type;

        // Rarity badge for special towers
        const rarityBadge = def.rarity !== 'default'
            ? `<div class="tp-rarity tp-rarity-${def.rarity}">${def.rarity}</div>`
            : '';

        item.innerHTML = `
            <div class="tp-icon">${def.icon}</div>
            <div class="tp-info">
                <div class="tp-name">${def.label}</div>
                <div class="tp-cost">${def.cost}g</div>
                ${rarityBadge}
            </div>`;

        item.addEventListener('click', () => {
            document.querySelectorAll('.tp-item').forEach(i=>i.classList.remove('selected'));
            item.classList.add('selected');
            selectedType = type;
        });

        palette.appendChild(item);
    });

    // Set default selection
    selectedType = availableTypes[0];
}

// Fetch user's unlocked items
fetch('<?= $base ?>/api/get_unlockables.php')
    .then(r => r.json())
    .then(data => {
        const unlockedNames = [];
        if (data.success && data.data) {
            data.data.forEach(item => {
                if (item.type === 'tower') unlockedNames.push(item.name);
            });
        }
        buildTowerPalette(unlockedNames);
    })
    .catch(() => {
        // On error, just show base towers
        buildTowerPalette([]);
    });

// ---- Cell click ----
function handleCellClick(x,y,cell){
    if(state.running) return;
    const key = x+','+y;
    if(towers[key]){
        // Refund
        const def = TOWER_DEFS[towers[key].type];
        state.gold += Math.floor(def.cost*0.5);
        delete towers[key];
        cell.innerHTML='';
        cell.classList.remove('mg-has-tower');
        updateUI();
        return;
    }
    if(cell.classList.contains('mg-path')) return;
    if(!TOWER_DEFS[selectedType]) return;
    const def = TOWER_DEFS[selectedType];
    if(state.gold < def.cost){ showMsg('Not enough gold!','error'); return; }
    state.gold -= def.cost;
    towers[key] = {type:selectedType, x, y, cooldown:0};
    cell.innerHTML = def.icon;
    cell.classList.add('mg-has-tower');
    cell.style.fontSize='18px';
    cell.title = `${def.label} – click to sell (${Math.floor(def.cost*0.5)}g)`;
    updateUI();
}

// ---- Wave start ----
document.getElementById('mg-start-wave').addEventListener('click', function(){
    if(state.running) return;
    state.wave++;
    state.running = true;
    this.disabled = true;
    spawnWave();
    runLoop();
});

document.getElementById('mg-reset').addEventListener('click', resetGame);

function spawnWave(){
    const count = 5 + state.wave * 2;
    for(let i=0;i<count;i++){
        const type = state.wave>=5&&Math.random()<0.2 ? 'tank'
                   : state.wave>=3&&Math.random()<0.3 ? 'fast' : 'basic';
        // Reduced HP scaling and lower base values for better balance
        const hp = {basic:60,fast:30,tank:200}[type] * (1+state.wave*0.10);
        enemies.push({pathIdx:0, progress:0, hp:Math.round(hp), maxHp:Math.round(hp),
            // Reduced enemy speeds significantly
            speed:{basic:0.018,fast:0.04,tank:0.01}[type], type, delay: i*18, id:Math.random()});
    }
}

function runLoop(){
    let tick = 0;
    function frame(){
        tick++;
        // Move enemies
        enemies.forEach(e=>{
            if(e.dead||e.delay>0){ if(e.delay>0) e.delay--; return; }
            e.progress += e.speed;
            if(e.progress >= 1){ e.pathIdx++; e.progress=0; }
            if(e.pathIdx >= PATH.length){ e.dead=true; state.lives=Math.max(0,state.lives-1); }
        });
        // Towers attack
        Object.values(towers).forEach(t=>{
            t.cooldown = Math.max(0, t.cooldown-1);
            if(t.cooldown>0) return;
            const def = TOWER_DEFS[t.type];
            if(!def) return;
            const spd = def.spd;
            const range = def.range;
            // Find closest live enemy in range
            let target = null, bestDist = Infinity;
            enemies.forEach(e=>{
                if(e.dead||e.delay>0) return;
                const ep = PATH[Math.min(e.pathIdx,PATH.length-1)];
                const dist = Math.hypot(ep.x-t.x, ep.y-t.y);
                if(dist<=range&&dist<bestDist){ bestDist=dist; target=e; }
            });
            if(target){
                // Frost tower: slow the target
                if(t.type==='frost' && !target.slowed){
                    target.speed *= 0.5;
                    target.slowed = true;
                    setTimeout(()=>{ if(target) { target.speed *= 2; target.slowed=false; } }, 2000);
                }
                // Tesla tower: chain damage to nearby enemies
                if(t.type==='tesla'){
                    enemies.forEach(e2=>{
                        if(!e2.dead&&e2!==target){
                            const ep2=PATH[Math.min(e2.pathIdx,PATH.length-1)];
                            const chainDist=Math.hypot(ep2.x-target.x,ep2.y-target.y);
                            if(chainDist<=2){ e2.hp-=Math.floor(def.dmg*0.6); if(e2.hp<=0){e2.dead=true;state.gold+=10;state.score+=20+(state.wave*5);} }
                        }
                    });
                }
                // Cannon: splash damage
                if(t.type==='cannon'){
                    const ep=PATH[Math.min(target.pathIdx,PATH.length-1)];
                    enemies.forEach(e2=>{
                        if(!e2.dead&&e2!==target){
                            const ep2=PATH[Math.min(e2.pathIdx,PATH.length-1)];
                            if(Math.hypot(ep2.x-ep.x,ep2.y-ep.y)<=1.5){ e2.hp-=Math.floor(def.dmg*0.5); if(e2.hp<=0){e2.dead=true;state.gold+=10;state.score+=20+(state.wave*5);} }
                        }
                    });
                }
                target.hp -= def.dmg;
                t.cooldown = Math.round(60/spd);
                if(target.hp<=0){ target.dead=true; state.gold+=10; state.score+=20+(state.wave*5); }
            }
        });
        // Remove dead
        enemies = enemies.filter(e=>!e.dead);
        // Render
        renderEnemies();
        updateUI();
        // Check wave done
        const alive = enemies.filter(e=>!e.dead);
        if(alive.length===0){
            cancelAnimationFrame(loopId);
            state.running=false;
            document.getElementById('mg-start-wave').disabled=false;
            state.gold += 50 + state.wave*20;
            state.score += 100 * state.wave;
            updateUI();
            showMsg('Wave '+state.wave+' complete! +bonus gold','success');
            if(state.lives<=0){ endGame(); }
            return;
        }
        if(state.lives<=0){ cancelAnimationFrame(loopId); endGame(); return; }
        loopId = requestAnimationFrame(frame);
    }
    loopId = requestAnimationFrame(frame);
}

function renderEnemies(){
    // Remove old enemy els
    document.querySelectorAll('.mg-enemy').forEach(el=>el.remove());
    enemies.filter(e=>!e.dead&&e.delay===0).forEach(e=>{
        const pos = PATH[Math.min(e.pathIdx,PATH.length-1)];
        const cell = cells[pos.x+','+pos.y];
        if(!cell) return;
        const el = document.createElement('div');
        el.className = 'mg-enemy';
        el.innerHTML = {basic:'👾',fast:'💨',tank:'🛡️'}[e.type]||'👾';
        if(e.slowed) el.style.opacity='0.6';
        // HP bar
        const pct = Math.max(0,e.hp/e.maxHp*100);
        el.style.cssText = `--hp:${pct}%`;
        cell.appendChild(el);
    });
}

function endGame(){
    state.running = false;
    showMsg('Game Over! You survived '+state.wave+' waves. Score: '+state.score,'error');
    document.getElementById('mg-start-wave').disabled = true;
    if(<?= isLoggedIn()?'true':'false' ?>){
        fetch('<?= $base ?>/api/upload_score.php',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-User-Id':'<?= getCurrentUserId()??0 ?>'},
            body:JSON.stringify({map_id:1,score:state.score,waves_survived:state.wave,time_survived:state.wave*30})
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.coins_earned > 0){
                showToast('🪙 +'+ d.coins_earned +' coins earned!', 'info', 4000);
            }
            if(d.new_unlocks && d.new_unlocks.length > 0){
                d.new_unlocks.forEach(function(u){
                    setTimeout(function(){
                        showToast('🔓 UNLOCKED: '+u.name+' ('+u.rarity+')!', 'success', 6000);
                    }, 800);
                });
            }
        }).catch(()=>{});
    }
}

function updateUI(){
    document.getElementById('mg-gold').textContent  = state.gold;
    document.getElementById('mg-wave').textContent  = state.wave;
    document.getElementById('mg-lives').textContent = state.lives;
    document.getElementById('mg-score').textContent = state.score;
}

function showMsg(msg, type){
    const el = document.getElementById('mg-message');
    el.textContent = msg;
    el.className = 'mg-message mg-msg-'+type;
    el.style.display='';
    clearTimeout(el._t);
    el._t = setTimeout(()=>{ el.style.display='none'; }, 3000);
}

function resetGame(){
    if(loopId) cancelAnimationFrame(loopId);
    state = {gold:500, lives:20, wave:0, score:0, running:false};
    towers = {}; enemies = [];
    document.querySelectorAll('.mg-has-tower').forEach(c=>{ c.innerHTML=''; c.classList.remove('mg-has-tower'); });
    document.querySelectorAll('.mg-enemy').forEach(e=>e.remove());
    document.getElementById('mg-start-wave').disabled = false;
    document.getElementById('mg-message').style.display='none';
    updateUI();
}

updateUI();
})();
</script>
<style>
.tp-rarity { font-size: 0.65rem; padding: 1px 4px; border-radius: 3px; margin-top: 2px; font-weight: bold; text-transform: uppercase; }
.tp-rarity-uncommon  { background: #388e3c; color: #fff; }
.tp-rarity-rare      { background: #1565c0; color: #fff; }
.tp-rarity-epic      { background: #6a1b9a; color: #fff; }
.tp-rarity-legendary { background: #e65100; color: #fff; }
</style>
