<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
$db = (new \Config\Database())->getConnection();
$maps = [];
if ($db) {
    try { $maps = $db->query("SELECT id, name FROM maps ORDER BY name")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
}
$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">
    <div class="page-header">
        <h1 class="page-title">🏆 Leaderboard</h1>
        <p>Top players ranked globally</p>
    </div>

    <div class="lb-controls">
        <div class="lb-tabs">
            <button class="lb-tab active" data-type="score">By Score</button>
            <button class="lb-tab" data-type="waves">By Waves</button>
            <button class="lb-tab" data-type="time">By Time</button>
        </div>
        <select id="map-filter" class="form-input" style="width:auto">
            <option value="">All Maps</option>
            <?php foreach($maps as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= sanitizeOutput($m['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="lb-loading" class="loading-state">Loading scores…</div>
    <div id="lb-empty"   class="empty-state" style="display:none">
        <div class="empty-icon">🏆</div>
        <p>No scores yet. Be the first!</p>
    </div>

    <div class="table-wrap" id="lb-table-wrap" style="display:none">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px">Rank</th>
                    <th>Player</th>
                    <th>Map</th>
                    <th id="lb-col-header">Score</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="lb-body"></tbody>
        </table>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
(function(){
    var currentType = 'score';
    var mapId = '';
    var userId = <?= getCurrentUserId() ?? 'null' ?>;

    document.querySelectorAll('.lb-tab').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.querySelectorAll('.lb-tab').forEach(function(b){ b.classList.remove('active'); });
            this.classList.add('active');
            currentType = this.dataset.type;
            load();
        });
    });
    document.getElementById('map-filter').addEventListener('change', function(){ mapId = this.value; load(); });

    function load(){
        document.getElementById('lb-loading').style.display = '';
        document.getElementById('lb-table-wrap').style.display = 'none';
        document.getElementById('lb-empty').style.display = 'none';
        var url = '<?= $base ?>/api/get_scores.php?type='+currentType+'&limit=50'+(mapId?'&map_id='+mapId:'');
        fetch(url).then(function(r){ return r.json(); }).then(function(data){
            document.getElementById('lb-loading').style.display = 'none';
            var rows = data.data || data;
            if(!Array.isArray(rows)||rows.length===0){
                document.getElementById('lb-empty').style.display = '';
                return;
            }
            var headers = {score:'Score',waves:'Waves Survived',time:'Time Survived'};
            document.getElementById('lb-col-header').textContent = headers[currentType];
            var html = '';
            rows.forEach(function(row,i){
                var rank = i+1;
                var medal = rank===1?'🥇':rank===2?'🥈':rank===3?'🥉':'#'+rank;
                var isMe = userId && row.user_id == userId;
                var val = currentType==='score' ? Number(row.score).toLocaleString()
                        : currentType==='waves' ? row.waves_survived+' waves'
                        : Math.floor(row.time_survived/60)+'m '+(row.time_survived%60)+'s';
                html += '<tr class="'+(isMe?'lb-me':'')+'">';
                html += '<td class="lb-rank">'+medal+'</td>';
                html += '<td class="lb-player">'+(isMe?'<strong>':'')+(row.username||'Unknown')+(isMe?'</strong>':'')+(isMe?' <span class="you-badge">You</span>':'')+'</td>';
                html += '<td>'+(row.map_name||'—')+'</td>';
                html += '<td class="lb-score">'+val+'</td>';
                html += '<td class="lb-date">'+(row.created_at?new Date(row.created_at).toLocaleDateString():'')+'</td>';
                html += '</tr>';
            });
            document.getElementById('lb-body').innerHTML = html;
            document.getElementById('lb-table-wrap').style.display = '';
        }).catch(function(){
            document.getElementById('lb-loading').textContent = 'Failed to load — make sure the database is set up.';
        });
    }
    load();
})();
</script>
