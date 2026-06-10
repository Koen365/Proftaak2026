<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">
    <div class="page-header">
        <h1 class="page-title">📖 Wiki</h1>
        <p>Guides, strategies, towers, enemies and more</p>
    </div>

    <div class="wiki-layout">
        <!-- Sidebar -->
        <aside class="wiki-sidebar">
            <div class="wiki-search-box">
                <input type="text" id="wiki-search" class="form-input" placeholder="Search wiki…">
            </div>
            <nav class="wiki-nav">
                <div class="wiki-nav-group">
                    <div class="wiki-nav-title">Getting Started</div>
                    <a class="wiki-nav-link active" data-article="intro" href="#">Introduction</a>
                    <a class="wiki-nav-link" data-article="mechanics" href="#">Game Mechanics</a>
                </div>
                <div class="wiki-nav-group">
                    <div class="wiki-nav-title">Towers</div>
                    <a class="wiki-nav-link" data-article="tower-basic" href="#">Basic Tower</a>
                    <a class="wiki-nav-link" data-article="tower-sniper" href="#">Sniper Tower</a>
                    <a class="wiki-nav-link" data-article="tower-mg" href="#">Machine Gun Tower</a>
                </div>
                <div class="wiki-nav-group">
                    <div class="wiki-nav-title">Enemies</div>
                    <a class="wiki-nav-link" data-article="enemy-basic" href="#">Basic Enemy</a>
                    <a class="wiki-nav-link" data-article="enemy-fast" href="#">Fast Enemy</a>
                    <a class="wiki-nav-link" data-article="enemy-tank" href="#">Tank Enemy</a>
                </div>
                <div class="wiki-nav-group">
                    <div class="wiki-nav-title">Strategies</div>
                    <a class="wiki-nav-link" data-article="strategy-early" href="#">Early Game</a>
                    <a class="wiki-nav-link" data-article="strategy-late" href="#">Late Game</a>
                </div>
            </nav>
        </aside>

        <!-- Main content -->
        <article class="wiki-article" id="wiki-article">
            <div id="wiki-content"></div>
        </article>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
var articles = {
    'intro': {title:'Introduction to Tower Defense',cat:'Getting Started',
        body:`<p>Welcome to the Tower Defense Wiki. This is your comprehensive guide to mastering every aspect of the game.</p>
        <h3>What is Tower Defense?</h3>
        <p>Tower defense is a strategy genre where you place defensive towers to stop waves of enemies from reaching your base. Each wave is stronger than the last, and you earn resources by defeating enemies.</p>
        <h3>Core Loop</h3>
        <ol><li>Plan your tower placement</li><li>Start the wave</li><li>Earn gold from kills</li><li>Build/upgrade towers</li><li>Repeat until you survive or fail</li></ol>
        <div class="wiki-tip">💡 <strong>Tip:</strong> Cover as much path as possible with your towers. Corners are prime placement spots.</div>`},
    'mechanics': {title:'Game Mechanics',cat:'Getting Started',
        body:`<h3>Resources (Gold)</h3><p>You start each game with a budget. Killing enemies rewards gold which you spend on towers.</p>
        <h3>Lives</h3><p>You have 20 lives. Every enemy that reaches your base costs 1 life. Reach 0 and the game ends.</p>
        <h3>Waves</h3><p>Enemies arrive in waves. Each wave has more enemies with scaling health and speed.</p>
        <h3>Scoring</h3><p>Your score = kills × wave multiplier + time bonus. Combo kills boost the multiplier.</p>`},
    'tower-basic': {title:'Basic Tower',cat:'Towers',
        body:`<div class="wiki-stat-block"><div class="ws-title">Basic Tower</div><div class="ws-rarity common">Common</div>
        <table class="ws-table"><tr><td>Damage</td><td>10</td></tr><tr><td>Attack Speed</td><td>1.0/s</td></tr><tr><td>Range</td><td>3 tiles</td></tr><tr><td>Cost</td><td>100 gold</td></tr></table></div>
        <p>The standard all-rounder. Ideal for beginners and filling coverage gaps. Not the best at anything, but reliable everywhere.</p>
        <h3>Best Used For</h3><ul><li>Covering long straight paths</li><li>Budget defense early game</li><li>Filler between specialized towers</li></ul>
        <div class="wiki-tip">💡 Unlock at: Default (available from start)</div>`},
    'tower-sniper': {title:'Sniper Tower',cat:'Towers',
        body:`<div class="wiki-stat-block"><div class="ws-title">Sniper Tower</div><div class="ws-rarity rare">Rare</div>
        <table class="ws-table"><tr><td>Damage</td><td>50</td></tr><tr><td>Attack Speed</td><td>0.5/s</td></tr><tr><td>Range</td><td>6 tiles</td></tr><tr><td>Cost</td><td>500 gold</td></tr></table></div>
        <p>High damage, long range, but slow attack speed. Excellent against Tank enemies.</p>
        <h3>Best Used For</h3><ul><li>Eliminating high-HP targets</li><li>Back-line support</li><li>Open map positions with long sight lines</li></ul>
        <div class="wiki-tip">🔒 Unlock: Reach 1,000 score in any game</div>`},
    'tower-mg': {title:'Machine Gun Tower',cat:'Towers',
        body:`<div class="wiki-stat-block"><div class="ws-title">Machine Gun Tower</div><div class="ws-rarity epic">Epic</div>
        <table class="ws-table"><tr><td>Damage</td><td>5</td></tr><tr><td>Attack Speed</td><td>5.0/s</td></tr><tr><td>Range</td><td>2.5 tiles</td></tr><tr><td>Cost</td><td>300 gold</td></tr></table></div>
        <p>Shreds fast enemies and groups. Low individual damage, but exceptional DPS through rate of fire.</p>
        <div class="wiki-tip">🔒 Unlock: Survive 10 waves in a single game</div>`},
    'enemy-basic': {title:'Basic Enemy',cat:'Enemies',
        body:`<div class="wiki-stat-block"><div class="ws-title">Basic Enemy</div><div class="ws-rarity common">Common</div>
        <table class="ws-table"><tr><td>HP</td><td>100</td></tr><tr><td>Speed</td><td>1.0</td></tr><tr><td>Reward</td><td>10 gold</td></tr></table></div>
        <p>Standard enemy — balanced stats. Forms the bulk of early waves.</p>`},
    'enemy-fast': {title:'Fast Enemy',cat:'Enemies',
        body:`<div class="wiki-stat-block"><div class="ws-title">Fast Enemy</div><div class="ws-rarity uncommon">Uncommon</div>
        <table class="ws-table"><tr><td>HP</td><td>50</td></tr><tr><td>Speed</td><td>3.0</td></tr><tr><td>Reward</td><td>15 gold</td></tr></table></div>
        <p>Moves extremely fast but has low HP. Machine Gun towers are the counter.</p>`},
    'enemy-tank': {title:'Tank Enemy',cat:'Enemies',
        body:`<div class="wiki-stat-block"><div class="ws-title">Tank Enemy</div><div class="ws-rarity rare">Rare</div>
        <table class="ws-table"><tr><td>HP</td><td>500</td></tr><tr><td>Speed</td><td>0.5</td></tr><tr><td>Reward</td><td>50 gold</td></tr></table></div>
        <p>Extremely high HP but very slow. Focus Sniper Towers on tanks immediately.</p>`},
    'strategy-early': {title:'Early Game Strategy',cat:'Strategies',
        body:`<h3>First 5 Waves</h3><ol><li>Place 3–4 Basic Towers on corners</li><li>Save gold — don't overspend</li><li>Target corners/bends for maximum coverage</li><li>Don't buy expensive towers until wave 3+</li></ol>
        <div class="wiki-tip">💡 Corners let a tower hit enemies on multiple segments of the path.</div>`},
    'strategy-late': {title:'Late Game Strategy',cat:'Strategies',
        body:`<h3>Wave 10+</h3><p>By the late game you need specialization:</p><ul><li>Snipers for Tanks in the back</li><li>MG towers on fast-enemy choke points</li><li>Keep Basic towers for filler DPS</li><li>Never stop building — gold means nothing if you lose</li></ul>`}
};

function showArticle(key){
    var a = articles[key];
    if(!a) return;
    var html = '<div class="wiki-article-header"><span class="wiki-article-cat">'+a.cat+'</span><h2>'+a.title+'</h2></div>';
    html += '<div class="wiki-article-body">'+a.body+'</div>';
    document.getElementById('wiki-content').innerHTML = html;
    document.querySelectorAll('.wiki-nav-link').forEach(function(l){ l.classList.remove('active'); if(l.dataset.article===key) l.classList.add('active'); });
}
showArticle('intro');

document.querySelectorAll('.wiki-nav-link').forEach(function(l){
    l.addEventListener('click',function(e){ e.preventDefault(); showArticle(this.dataset.article); });
});

document.getElementById('wiki-search').addEventListener('input', function(){
    var q = this.value.toLowerCase();
    document.querySelectorAll('.wiki-nav-link').forEach(function(l){
        var matches = l.textContent.toLowerCase().includes(q);
        l.style.display = q&&!matches?'none':'';
    });
});
</script>
