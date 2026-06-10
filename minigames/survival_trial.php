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
        <h1 class="page-title">⚔️ Endless Survival Trial</h1>
        <p>Survive as long as possible — exclusive leaderboard rewards</p>
    </div>

    <div class="minigame-layout">
        <aside class="mg-sidebar">
            <div class="mg-panel">
                <h3>Survival Stats</h3>
                <div class="mg-stat"><span>⏱️ Time</span><strong id="st-time">0s</strong></div>
                <div class="mg-stat"><span>🌊 Wave</span><strong id="st-wave">0</strong></div>
                <div class="mg-stat"><span>💀 Kills</span><strong id="st-kills">0</strong></div>
                <div class="mg-stat"><span>🏆 Score</span><strong id="st-score">0</strong></div>
                <div class="mg-stat"><span>🔥 Combo</span><strong id="st-combo">x1.0</strong></div>
            </div>
            <div class="mg-panel">
                <h3>Towers</h3>
                <div class="tower-palette" id="st-palette">
                    <div id="st-palette-loading" style="color:#aaa;font-size:0.85rem;padding:0.5rem">Loading towers…</div>
                </div>
                <div class="mg-stat" style="margin-top:.5rem"><span>💰 Gold</span><strong id="st-gold">200</strong></div>
            </div>
            <div class="mg-panel">
                <button id="st-start" class="btn btn-primary btn-full">▶ Start Game</button>
                <button id="st-reset" class="btn btn-outline btn-full" style="margin-top:.5rem">↺ Reset</button>
            </div>
            <div class="mg-panel" id="st-difficulty-info">
                <h3>Difficulty</h3>
                <p>Wave 5: Fast enemies appear</p>
                <p>Wave 10: Tank enemies appear</p>
                <p>Wave 15: Mixed elite waves</p>
                <p>Unlock special towers from the Loot Box!</p>
            </div>
        </aside>
        <div class="mg-game-area">
            <div id="st-grid" class="mg-grid"></div>
            <div id="st-message" class="mg-message" style="display:none"></div>
        </div>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
(function(){
const GRID_W=16, GRID_H=12;
const PATH=[
    {x:0,y:5},{x:1,y:5},{x:2,y:5},{x:3,y:5},{x:4,y:5},{x:5,y:5},
    {x:5,y:4},{x:5,y:3},{x:5,y:2},{x:5,y:1},{x:5,y:0},
    {x:6,y:0},{x:7,y:0},{x:8,y:0},{x:9,y:0},{x:10,y:0},
    {x:10,y:1},{x:10,y:2},{x:10,y:3},{x:10,y:4},{x:10,y:5},{x:10,y:6},
    {x:11,y:6},{x:12,y:6},{x:13,y:6},{x:14,y:6},{x:15,y:6}
];

// All possible tower definitions (including loot box towers)
const ALL_TOWER_DEFS = {
    basic:  {icon:'🗼', cost:100,  dmg:25,  spd:1.0, range:3, label:'Basic',       rarity:'default'},
    sniper: {icon:'🎯', cost:300,  dmg:120, spd:0.5, range:6, label:'Sniper',      rarity:'default'},
    mg:     {icon:'⚡', cost:200,  dmg:12,  spd:5.0, range:2, label:'MG',          rarity:'default'},
    // Loot box towers
    poison: {icon:'☠️', cost:150,  dmg:18,  spd:1.5, range:3, label:'Poison',      rarity:'uncommon',  unlockName:'Poison Tower'},
    cannon: {icon:'💥', cost:350,  dmg:80,  spd:0.8, range:4, label:'Cannon',      rarity:'rare',      unlockName:'Cannon Tower'},
    tesla:  {icon:'⚡', cost:450,  dmg:55,  spd:2.0, range:4, label:'Tesla',       rarity:'epic',      unlockName:'Tesla Tower'},
    frost:  {icon:'❄️', cost:500,  dmg:35,  spd:1.2, range:4, label:'Frost',       rarity:'legendary', unlockName:'Frost Tower'},
};

// TOWER_DEFS populated after fetching unlocks
let TOWER_DEFS = {};

let state={gold:200,lives:30,wave:0,score:0,kills:0,time:0,combo:1,running:false,started:false};
let towers={},enemies=[],selectedType='basic',loopId=null,tickCount=0;

// Build grid
const gridEl=document.getElementById('st-grid');
gridEl.style.gridTemplateColumns=`repeat(${GRID_W},1fr)`;
const cells={};
for(let y=0;y<GRID_H;y++) for(let x=0;x<GRID_W;x++){
    const c=document.createElement('div');
    c.className='mg-cell';
    c.dataset.x=x; c.dataset.y=y;
    if(PATH.some(p=>p.x===x&&p.y===y)) c.classList.add('mg-path');
    c.addEventListener('click',()=>handleClick(x,y,c));
    gridEl.appendChild(c);
    cells[x+','+y]=c;
}
cells[PATH[0].x+','+PATH[0].y]?.classList.add('mg-start');
cells[PATH[PATH.length-1].x+','+PATH[PATH.length-1].y]?.classList.add('mg-end');

// ---- Load unlocks and build tower palette ----
function buildTowerPalette(unlockedTowerNames) {
    const palette = document.getElementById('st-palette');
    palette.innerHTML = '';

    const availableTypes = ['basic', 'sniper', 'mg'];
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
            document.querySelectorAll('#st-palette .tp-item').forEach(i=>i.classList.remove('selected'));
            item.classList.add('selected');
            selectedType = type;
        });

        palette.appendChild(item);
    });

    selectedType = availableTypes[0];
}

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
        buildTowerPalette([]);
    });

function handleClick(x,y,cell){
    const key=x+','+y;
    if(towers[key]){
        const def=TOWER_DEFS[towers[key].type];
        state.gold+=Math.floor(def.cost*0.5);
        delete towers[key]; cell.innerHTML=''; cell.classList.remove('mg-has-tower');
        updateUI(); return;
    }
    if(cell.classList.contains('mg-path')) return;
    if(!TOWER_DEFS[selectedType]) return;
    const def=TOWER_DEFS[selectedType];
    if(state.gold<def.cost){ showMsg('Not enough gold!','error'); return; }
    state.gold-=def.cost;
    towers[key]={type:selectedType,x,y,cooldown:0};
    cell.innerHTML=def.icon; cell.classList.add('mg-has-tower'); cell.style.fontSize='18px';
    updateUI();
}

document.getElementById('st-start').addEventListener('click',function(){
    if(state.running) return;
    if(!state.started) state.started=true;
    nextWave();
    if(!loopId) runLoop();
    this.textContent='⏸ Running…';
    this.disabled=true;
});
document.getElementById('st-reset').addEventListener('click',resetGame);

let waveTimer=0;
function nextWave(){
    state.wave++;
    state.running=true;
    waveTimer=0;
    const count=8+state.wave*3;
    for(let i=0;i<count;i++){
        const roll=Math.random();
        const type = state.wave>=15&&roll<0.3?'tank'
                   : state.wave>=10&&roll<0.4?'tank'
                   : state.wave>=5&&roll<0.4?'fast':'basic';
        // Reduced HP scaling and lower base values
        const hpMult=1+state.wave*0.10;
        const hp={basic:60,fast:30,tank:220}[type]*hpMult;
        enemies.push({pathIdx:0,progress:0,hp:Math.round(hp),maxHp:Math.round(hp),
            // Reduced enemy speeds significantly
            speed:{basic:0.015,fast:0.038,tank:0.008}[type],type,delay:i*15,dead:false});
    }
}

function runLoop(){
    function frame(){
        tickCount++;
        // Timer
        if(tickCount%60===0){ state.time++; }
        // Decay combo
        if(tickCount%120===0&&state.combo>1) state.combo=Math.max(1,state.combo-0.1);
        // Move enemies
        enemies.forEach(e=>{
            if(e.dead){return;} if(e.delay>0){e.delay--;return;}
            e.progress+=e.speed;
            if(e.progress>=1){e.pathIdx++;e.progress=0;}
            if(e.pathIdx>=PATH.length){e.dead=true;state.lives=Math.max(0,state.lives-1);state.combo=1;}
        });
        // Towers shoot
        Object.values(towers).forEach(t=>{
            t.cooldown=Math.max(0,t.cooldown-1);
            if(t.cooldown>0) return;
            const def=TOWER_DEFS[t.type];
            if(!def) return;
            let target=null,bd=Infinity;
            enemies.filter(e=>!e.dead&&e.delay===0).forEach(e=>{
                const ep=PATH[Math.min(e.pathIdx,PATH.length-1)];
                const d=Math.hypot(ep.x-t.x,ep.y-t.y);
                if(d<=def.range&&d<bd){bd=d;target=e;}
            });
            if(target){
                // Frost tower: slow the target
                if(t.type==='frost' && !target.slowed){
                    target.speed*=0.5; target.slowed=true;
                    setTimeout(()=>{ if(target){target.speed*=2;target.slowed=false;} },2000);
                }
                // Tesla: chain lightning
                if(t.type==='tesla'){
                    const ep=PATH[Math.min(target.pathIdx,PATH.length-1)];
                    enemies.filter(e=>!e.dead&&e!==target).forEach(e2=>{
                        const ep2=PATH[Math.min(e2.pathIdx,PATH.length-1)];
                        if(Math.hypot(ep2.x-ep.x,ep2.y-ep.y)<=2){
                            e2.hp-=Math.floor(def.dmg*0.6);
                            if(e2.hp<=0){e2.dead=true;state.kills++;state.gold+=8;state.combo=Math.min(5,state.combo+0.1);state.score+=Math.round(20*state.combo*(1+state.wave*0.1));}
                        }
                    });
                }
                // Cannon: splash
                if(t.type==='cannon'){
                    const ep=PATH[Math.min(target.pathIdx,PATH.length-1)];
                    enemies.filter(e=>!e.dead&&e!==target).forEach(e2=>{
                        const ep2=PATH[Math.min(e2.pathIdx,PATH.length-1)];
                        if(Math.hypot(ep2.x-ep.x,ep2.y-ep.y)<=1.5){
                            e2.hp-=Math.floor(def.dmg*0.5);
                            if(e2.hp<=0){e2.dead=true;state.kills++;state.gold+=8;state.combo=Math.min(5,state.combo+0.1);state.score+=Math.round(20*state.combo*(1+state.wave*0.1));}
                        }
                    });
                }
                target.hp-=def.dmg;
                t.cooldown=Math.round(60/def.spd);
                if(target.hp<=0){
                    target.dead=true;
                    state.kills++;
                    state.gold+=8;
                    state.combo=Math.min(5,state.combo+0.1);
                    state.score+=Math.round(20*state.combo*(1+state.wave*0.1));
                }
            }
        });
        enemies=enemies.filter(e=>!e.dead);
        renderEnemies();
        updateUI();
        if(state.lives<=0){endGame();return;}
        // Auto next wave when clear
        if(enemies.length===0&&state.running){
            state.running=false;
            state.gold+=60+state.wave*25;
            state.score+=150*state.wave;
            showMsg('Wave '+state.wave+' survived! +bonus gold','success');
            setTimeout(()=>{
                if(state.lives>0){ nextWave(); }
            },2500);
        }
        loopId=requestAnimationFrame(frame);
    }
    loopId=requestAnimationFrame(frame);
}

function renderEnemies(){
    document.querySelectorAll('.mg-enemy').forEach(e=>e.remove());
    enemies.filter(e=>!e.dead&&e.delay===0).forEach(e=>{
        const pos=PATH[Math.min(e.pathIdx,PATH.length-1)];
        const cell=cells[pos.x+','+pos.y];
        if(!cell) return;
        const el=document.createElement('div');
        el.className='mg-enemy';
        el.innerHTML={basic:'👾',fast:'💨',tank:'🛡️'}[e.type]||'👾';
        if(e.slowed) el.style.opacity='0.6';
        el.style.setProperty('--hp',Math.max(0,e.hp/e.maxHp*100)+'%');
        cell.appendChild(el);
    });
}

function endGame(){
    cancelAnimationFrame(loopId); loopId=null;
    state.running=false;
    showMsg('Survived '+state.wave+' waves! Final score: '+state.score,'error');
    document.getElementById('st-start').textContent='▶ Start Game';
    document.getElementById('st-start').disabled=false;
    if(<?= isLoggedIn()?'true':'false' ?>){
        fetch('<?= $base ?>/api/upload_score.php',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-User-Id':'<?= getCurrentUserId()??0 ?>'},
            body:JSON.stringify({map_id:2,score:state.score,waves_survived:state.wave,time_survived:state.time})
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.coins_earned > 0){
                showToast('🪙 +'+d.coins_earned+' coins earned!','info',4000);
            }
            if(d.new_unlocks && d.new_unlocks.length > 0){
                d.new_unlocks.forEach(function(u){
                    setTimeout(function(){
                        showToast('🔓 UNLOCKED: '+u.name+' ('+u.rarity+')!','success',6000);
                    }, 800);
                });
            }
        }).catch(()=>{});
    }
}

function updateUI(){
    document.getElementById('st-time').textContent=state.time+'s';
    document.getElementById('st-wave').textContent=state.wave;
    document.getElementById('st-kills').textContent=state.kills;
    document.getElementById('st-score').textContent=state.score;
    document.getElementById('st-combo').textContent='x'+state.combo.toFixed(1);
    document.getElementById('st-gold').textContent=state.gold;
}

function showMsg(msg,type){
    const el=document.getElementById('st-message');
    el.textContent=msg; el.className='mg-message mg-msg-'+type; el.style.display='';
    clearTimeout(el._t); el._t=setTimeout(()=>el.style.display='none',3000);
}

function resetGame(){
    if(loopId){cancelAnimationFrame(loopId);loopId=null;}
    state={gold:200,lives:30,wave:0,score:0,kills:0,time:0,combo:1,running:false,started:false};
    towers={}; enemies=[]; tickCount=0;
    document.querySelectorAll('.mg-has-tower').forEach(c=>{c.innerHTML='';c.classList.remove('mg-has-tower');});
    document.querySelectorAll('.mg-enemy').forEach(e=>e.remove());
    document.getElementById('st-start').textContent='▶ Start Game';
    document.getElementById('st-start').disabled=false;
    document.getElementById('st-message').style.display='none';
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
