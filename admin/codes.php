<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn())  redirect(BASE_URL . '/auth/login.php');
if (!isAdmin())     { setFlash('Access denied.', 'error'); redirect(BASE_URL . '/index.php'); }

$db   = (new \Config\Database())->getConnection();
$uid  = getCurrentUserId();
$base = BASE_URL;
$error = '';
$msg   = '';

// ── Handle POST actions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code        = strtoupper(trim($_POST['code']        ?? ''));
        $type        = $_POST['reward_type']  ?? 'coins';
        $value       = (int)($_POST['reward_value'] ?? 0);
        $description = trim($_POST['description']   ?? '');
        $max_uses    = (int)($_POST['max_uses']      ?? 0);
        $expires_raw = trim($_POST['expires_at']     ?? '');
        $expires_at  = $expires_raw ? date('Y-m-d H:i:s', strtotime($expires_raw)) : null;

        if (!$code) {
            $error = 'Code cannot be empty.';
        } elseif (!in_array($type, ['coins','unlockable','all_unlockables'])) {
            $error = 'Invalid reward type.';
        } elseif ($type !== 'all_unlockables' && $value <= 0 && $type === 'coins') {
            $error = 'Coin reward must be greater than 0.';
        } else {
            try {
                $db->prepare("INSERT INTO redeem_codes (code, reward_type, reward_value, description, max_uses, expires_at, created_by)
                              VALUES (?,?,?,?,?,?,?)")
                   ->execute([$code, $type, $value, $description, $max_uses, $expires_at, $uid]);
                $msg = "Code <strong>$code</strong> created successfully.";
                logActivity($uid, 'create_code', $code);
            } catch (Exception $e) {
                $error = 'Code already exists or database error.';
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['code_id'] ?? 0);
        $db->prepare("UPDATE redeem_codes SET active = 1 - active WHERE id = ?")->execute([$id]);
        $msg = 'Code status toggled.';
    }

    if ($action === 'delete') {
        $id = (int)($_POST['code_id'] ?? 0);
        $db->prepare("DELETE FROM redeem_codes WHERE id = ?")->execute([$id]);
        $msg = 'Code deleted.';
        logActivity($uid, 'delete_code', (string)$id);
    }

    if ($action === 'reset_uses') {
        $id = (int)($_POST['code_id'] ?? 0);
        $db->prepare("UPDATE redeem_codes SET times_used = 0 WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM redeem_code_uses WHERE code_id = ?")->execute([$id]);
        $msg = 'Usage count reset.';
    }
}

// ── Fetch all codes ───────────────────────────────────────────────────────
$codes = $db->query("
    SELECT rc.*, u.username as creator,
           (SELECT COUNT(*) FROM redeem_code_uses WHERE code_id = rc.id) as use_count
    FROM redeem_codes rc
    LEFT JOIN users u ON rc.created_by = u.id
    ORDER BY rc.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Unlockables list for dropdown
$unlockables = $db->query("SELECT id, name, rarity FROM unlockables ORDER BY type, rarity")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">

<?php if ($msg):   ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= sanitizeOutput($error) ?></div><?php endif; ?>

<section class="section">
    <div class="page-header">
        <h1 class="page-title">🎟️ Redeem Codes</h1>
        <a href="<?= $base ?>/admin/index.php" class="btn btn-outline btn-sm">← Admin Panel</a>
    </div>

    <!-- ── Create new code ─────────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:2rem">
        <h3 style="margin-bottom:1.2rem">Create New Code</h3>
        <form method="POST" action="" id="create-form">
            <input type="hidden" name="action" value="create">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">

                <div class="form-group" style="margin:0">
                    <label class="form-label">Code</label>
                    <input class="form-input" type="text" name="code" required maxlength="50"
                           placeholder="e.g. SUMMER2026"
                           style="text-transform:uppercase;letter-spacing:1px;font-weight:700">
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label">Reward Type</label>
                    <select class="form-input" name="reward_type" id="reward-type" onchange="toggleValueField()">
                        <option value="coins">Coins</option>
                        <option value="unlockable">Specific Unlockable</option>
                        <option value="all_unlockables">All Unlockables</option>
                    </select>
                </div>

                <div class="form-group" style="margin:0" id="value-field-coins">
                    <label class="form-label">Coin Amount</label>
                    <input class="form-input" type="number" name="reward_value" min="1" value="100" placeholder="100">
                </div>

                <div class="form-group" style="margin:0;display:none" id="value-field-unlock">
                    <label class="form-label">Unlockable Item</label>
                    <select class="form-input" name="reward_value_unlock">
                        <?php foreach ($unlockables as $u): ?>
                        <option value="<?= (int)$u['id'] ?>">[<?= ucfirst($u['rarity']) ?>] <?= sanitizeOutput($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label">Max Uses <small style="color:var(--text-muted)">(0 = unlimited)</small></label>
                    <input class="form-input" type="number" name="max_uses" min="0" value="0">
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label">Expires At <small style="color:var(--text-muted)">(optional)</small></label>
                    <input class="form-input" type="datetime-local" name="expires_at">
                </div>

                <div class="form-group" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Description</label>
                    <input class="form-input" type="text" name="description" maxlength="255" placeholder="e.g. Weekend bonus for all players">
                </div>

            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:16px">Create Code</button>
        </form>
    </div>

    <!-- ── Existing codes table ────────────────────────────────────────── -->
    <h3 style="margin-bottom:1rem">All Codes (<?= count($codes) ?>)</h3>
    <div class="table-wrap">
        <table class="data-table" id="codes-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Description</th>
                    <th>Uses</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($codes)): ?>
            <tr><td colspan="9" class="empty-cell">No codes yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($codes as $c): ?>
            <?php
                $maxLabel = $c['max_uses'] == 0 ? '∞' : $c['max_uses'];
                $usesLabel = $c['use_count'] . ' / ' . $maxLabel;
                $expired = $c['expires_at'] && strtotime($c['expires_at']) < time();
                $full    = $c['max_uses'] > 0 && $c['times_used'] >= $c['max_uses'];
                $statusClass = !$c['active'] ? 'badge-danger' : ($expired || $full ? 'badge-warning' : 'badge-success');
                $statusText  = !$c['active'] ? 'Disabled' : ($expired ? 'Expired' : ($full ? 'Maxed' : 'Active'));

                if ($c['reward_type'] === 'coins') {
                    $valueLabel = '🪙 ' . number_format($c['reward_value']);
                } elseif ($c['reward_type'] === 'all_unlockables') {
                    $valueLabel = '🎁 All items';
                } else {
                    $unl = array_filter($unlockables, fn($u) => $u['id'] == $c['reward_value']);
                    $unl = reset($unl);
                    $valueLabel = '🔓 ' . ($unl ? $unl['name'] : 'id:'.$c['reward_value']);
                }
            ?>
            <tr>
                <td><code style="background:var(--bg3);padding:3px 8px;border-radius:4px;font-weight:700;letter-spacing:1px"><?= sanitizeOutput($c['code']) ?></code></td>
                <td><?= ucfirst(str_replace('_',' ',$c['reward_type'])) ?></td>
                <td><?= $valueLabel ?></td>
                <td style="color:var(--text-muted);font-size:13px"><?= sanitizeOutput($c['description']) ?></td>
                <td><?= $usesLabel ?></td>
                <td style="color:var(--text-muted);font-size:12px"><?= $c['expires_at'] ? date('d M Y H:i', strtotime($c['expires_at'])) : '—' ?></td>
                <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                <td style="color:var(--text-muted);font-size:13px"><?= sanitizeOutput($c['creator'] ?? '—') ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <!-- Toggle active -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action"  value="toggle">
                            <input type="hidden" name="code_id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline" title="<?= $c['active']?'Disable':'Enable' ?>">
                                <?= $c['active'] ? '⏸' : '▶' ?>
                            </button>
                        </form>
                        <!-- Reset uses -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action"  value="reset_uses">
                            <input type="hidden" name="code_id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline" title="Reset use count">↺</button>
                        </form>
                        <!-- Delete -->
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete code <?= sanitizeOutput($c['code']) ?>?')">
                            <input type="hidden" name="action"  value="delete">
                            <input type="hidden" name="code_id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:#f44336;color:#fff">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Quick-copy panel ─────────────────────────────────────────────── -->
    <div class="card" style="margin-top:2rem">
        <h3 style="margin-bottom:.8rem">Quick Share</h3>
        <p style="color:var(--text-muted);font-size:13px;margin-bottom:1rem">
            Send players to the redeem page:
            <code style="background:var(--bg3);padding:3px 8px;border-radius:4px">
                <?= sanitizeOutput((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].BASE_URL.'/redeem.php') ?>
            </code>
        </p>
    </div>

</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>

<script>
function toggleValueField() {
    var type = document.getElementById('reward-type').value;
    document.getElementById('value-field-coins').style.display  = type === 'coins'       ? '' : 'none';
    document.getElementById('value-field-unlock').style.display = type === 'unlockable'  ? '' : 'none';
    // Sync hidden/visible input so only the right one submits
    var coinInput   = document.querySelector('#value-field-coins input[name="reward_value"]');
    var unlockSel   = document.querySelector('#value-field-unlock select[name="reward_value_unlock"]');
    if (type === 'unlockable') {
        coinInput.name = '_reward_value_disabled';
        unlockSel.name = 'reward_value';
    } else {
        coinInput.name = 'reward_value';
        unlockSel.name = '_reward_value_unlock_disabled';
    }
}

// Filter table
document.addEventListener('DOMContentLoaded', function() {
    var input = document.createElement('input');
    input.type = 'text'; input.placeholder = 'Filter codes…';
    input.className = 'form-input'; input.style.maxWidth = '300px'; input.style.marginBottom = '12px';
    document.getElementById('codes-table').before(input);
    input.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#codes-table tbody tr').forEach(function(row) {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
});
</script>
