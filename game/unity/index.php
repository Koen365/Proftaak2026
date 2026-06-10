<?php
/*
 * Unity WebGL Player Page
 * ─────────────────────────────────────────────────────────────────────────────
 * HOW TO DEPLOY YOUR UNITY BUILD HERE:
 *
 * 1. In Unity → File → Build Settings → WebGL → Build
 * 2. Copy the contents of your Build output folder into:
 *       C:\laragon\www\Proftaak\game\unity\Build\
 *    The folder should contain:
 *       Build/
 *         YourGame.data.gz  (or .br)
 *         YourGame.framework.js.gz
 *         YourGame.loader.js
 *         YourGame.wasm.gz
 *
 * 3. Update the four $build_* variables below to match your actual filenames.
 *
 * 4. (Optional) Enable compression in Unity Player Settings → WebGL →
 *    Publishing Settings → Compression Format → Gzip or Brotli.
 *    Then uncomment the matching AddType lines in the .htaccess stub at the
 *    bottom of this file.
 *
 * That's it — the rest is handled automatically.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();

// ── Build file names ─────────────────────────────────────────────────────────
// Change these to match your actual Unity build output filenames.
$build_loader    = 'Build/TowerDefense.loader.js';
$build_data      = 'Build/TowerDefense.data.gz';
$build_framework = 'Build/TowerDefense.framework.js.gz';
$build_wasm      = 'Build/TowerDefense.wasm.gz';

// Detect whether the build has actually been dropped in yet
$build_ready = file_exists(__DIR__ . '/' . $build_loader);

$base = BASE_URL;
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">

    <div class="page-header">
        <h1 class="page-title">🎮 Play in Browser</h1>
        <p>Tower Defense — WebGL Edition</p>
    </div>

    <?php if (!$build_ready): ?>
    <!-- ── PLACEHOLDER shown until the Unity build is dropped in ── -->
    <div class="unity-placeholder">
        <div class="unity-ph-inner">
            <div class="unity-ph-icon">🗼</div>
            <h2>Game Coming Soon</h2>
            <p>The in-browser WebGL build hasn't been deployed yet.</p>
            <div class="unity-deploy-steps">
                <h3>How to deploy your Unity build:</h3>
                <ol>
                    <li>Open your project in Unity</li>
                    <li>Go to <strong>File → Build Settings</strong></li>
                    <li>Select <strong>WebGL</strong> and click <strong>Build</strong></li>
                    <li>Copy the <code>Build/</code> folder into:<br>
                        <code>game/unity/Build/</code></li>
                    <li>Update the filenames in <code>game/unity/index.php</code></li>
                    <li>Refresh this page — the game will appear automatically</li>
                </ol>
            </div>
            <a href="<?= $base ?>/game/info.php" class="btn btn-outline" style="margin-top:1.5rem">
                ← View Game Info Instead
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- ── LIVE UNITY PLAYER ── -->
    <div class="unity-wrapper" id="unity-wrapper">

        <!-- Loading bar shown while Unity initialises -->
        <div id="unity-loading-bar">
            <div id="unity-logo">🗼</div>
            <div id="unity-progress-bar-empty">
                <div id="unity-progress-bar-full"></div>
            </div>
            <div id="unity-loading-text">Loading…</div>
        </div>

        <!-- The canvas Unity renders into -->
        <canvas id="unity-canvas" tabindex="-1"></canvas>

        <!-- Footer bar below canvas -->
        <div id="unity-footer">
            <div id="unity-webgl-logo"></div>
            <div id="unity-footer-right">
                <div id="unity-fullscreen-button" title="Fullscreen" onclick="unityFullscreen()">⛶</div>
            </div>
        </div>
    </div>

    <!-- Unity loader script -->
    <script src="<?= $base ?>/game/unity/<?= htmlspecialchars($build_loader) ?>"></script>
    <script>
    var buildUrl   = "<?= $base ?>/game/unity/Build";
    var config = {
        dataUrl:          buildUrl + "/<?= basename($build_data) ?>",
        frameworkUrl:     buildUrl + "/<?= basename($build_framework) ?>",
        codeUrl:          buildUrl + "/<?= basename($build_wasm) ?>",
        streamingAssetsUrl: "StreamingAssets",
        companyName:      "TowerDefenseHQ",
        productName:      "Tower Defense",
        productVersion:   "1.0",
    };

    var canvas       = document.getElementById("unity-canvas");
    var loadingBar   = document.getElementById("unity-loading-bar");
    var progressFull = document.getElementById("unity-progress-bar-full");
    var loadingText  = document.getElementById("unity-loading-text");
    var unityInstance = null;

    // Show loading bar
    loadingBar.style.display = "block";

    createUnityInstance(canvas, config, function(progress) {
        progressFull.style.width = (100 * progress) + "%";
        loadingText.textContent  = "Loading… " + Math.round(progress * 100) + "%";
    }).then(function(instance) {
        unityInstance = instance;
        loadingBar.style.display = "none";
    }).catch(function(message) {
        alert("Failed to load Unity game:\n" + message);
    });

    function unityFullscreen() {
        if (unityInstance) unityInstance.SetFullscreen(1);
    }

    // ── Unity → PHP bridge (called from C# via SendMessage / jslib) ──────────
    // Unity can call these functions from C# using Application.ExternalCall()
    // or a .jslib plugin.

    /**
     * Called by Unity after a game session ends.
     * Usage in C#:  Application.ExternalCall("UploadScore", score+","+waves+","+time+","+mapId);
     */
    window.UploadScore = function(csvData) {
        var parts = csvData.split(",");
        var payload = {
            score:          parseInt(parts[0]) || 0,
            waves_survived: parseInt(parts[1]) || 0,
            time_survived:  parseInt(parts[2]) || 0,
            map_id:         parseInt(parts[3]) || 1,
        };
        fetch("<?= $base ?>/api/upload_score.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(payload)
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.success) console.log("Score saved:", d.score_id);
        })
        .catch(function(e){ console.warn("Score upload failed:", e); });
    };

    /**
     * Called by Unity to check if the current user is logged in.
     * Returns a JSON string back into Unity via SendMessage.
     * Usage in C#: see Docs/UnityBridge.md
     */
    window.GetUserInfo = function() {
        fetch("<?= $base ?>/api/get_user_data.php")
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (unityInstance && d.success) {
                var json = JSON.stringify(d.data);
                // Send user data into Unity GameObject "Bridge", method "OnUserData"
                unityInstance.SendMessage("Bridge", "OnUserData", json);
            }
        });
    };
    </script>
    <?php endif; ?>

</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>

<style>
/* ── Placeholder ─────────────────────────────── */
.unity-placeholder {
    display:flex; align-items:center; justify-content:center;
    min-height:500px;
    background:var(--card); border:2px dashed var(--border);
    border-radius:var(--radius); padding:40px;
}
.unity-ph-inner { text-align:center; max-width:560px; }
.unity-ph-icon  { font-size:5rem; margin-bottom:20px; }
.unity-ph-inner h2 { font-size:1.8rem; margin-bottom:10px; }
.unity-ph-inner > p { color:var(--text-muted); margin-bottom:24px; }
.unity-deploy-steps {
    text-align:left;
    background:var(--bg3); border:1px solid var(--border);
    border-radius:var(--radius); padding:20px; margin-bottom:8px;
}
.unity-deploy-steps h3 { font-size:0.95rem; margin-bottom:12px; color:var(--accent); }
.unity-deploy-steps ol { padding-left:20px; }
.unity-deploy-steps li { margin-bottom:8px; color:var(--text-muted); font-size:14px; line-height:1.6; }
.unity-deploy-steps code {
    background:var(--bg); padding:2px 6px; border-radius:4px;
    font-size:12px; color:var(--accent); font-family:monospace;
}

/* ── Live player ──────────────────────────────── */
.unity-wrapper {
    background:#000; border-radius:var(--radius); overflow:hidden;
    border:1px solid var(--border); position:relative;
    display:flex; flex-direction:column; align-items:center;
}
#unity-canvas {
    width:960px; max-width:100%; height:600px;
    display:block; background:#000;
}
#unity-loading-bar {
    display:none; position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%);
    text-align:center; z-index:10;
}
#unity-logo { font-size:3rem; margin-bottom:16px; }
#unity-progress-bar-empty {
    width:280px; height:10px; background:var(--border);
    border-radius:5px; overflow:hidden; margin:0 auto 10px;
}
#unity-progress-bar-full {
    height:100%; width:0%;
    background:linear-gradient(90deg,var(--accent),var(--accent2));
    border-radius:5px; transition:width .15s ease;
}
#unity-loading-text { color:var(--text-muted); font-size:13px; }
#unity-footer {
    width:100%; display:flex; justify-content:flex-end;
    background:var(--bg2); padding:6px 12px; border-top:1px solid var(--border);
}
#unity-fullscreen-button {
    cursor:pointer; font-size:20px; color:var(--text-muted);
    transition:color .2s;
}
#unity-fullscreen-button:hover { color:var(--accent); }
</style>
