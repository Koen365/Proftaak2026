<?php
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');

$db   = (new \Config\Database())->getConnection();
$uid  = getCurrentUserId();
$base = BASE_URL;

$success = null; // ['message'=>'…','reward'=>'…']
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));

    if (!$code) {
        $error = 'Please enter a code.';
    } else {
        // Fetch code
        $stmt = $db->prepare("SELECT * FROM redeem_codes WHERE code = ? AND active = 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $error = 'Invalid or inactive code.';
        } elseif ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            $error = 'This code has expired.';
        } elseif ($row['max_uses'] > 0 && $row['times_used'] >= $row['max_uses']) {
            $error = 'This code has reached its maximum number of uses.';
        } else {
            // Check if this user already used it
            $used = $db->prepare("SELECT id FROM redeem_code_uses WHERE code_id = ? AND user_id = ?");
            $used->execute([$row['id'], $uid]);
            if ($used->fetch()) {
                $error = 'You have already used this code.';
            } else {
                // ── Apply reward ────────────────────────────────────────
                $rewardMsg = '';
                try {
                    $db->beginTransaction();

                    if ($row['reward_type'] === 'coins') {
                        $db->prepare("UPDATE users SET coins = coins + ? WHERE id = ?")
                           ->execute([$row['reward_value'], $uid]);
                        $rewardMsg = '🪙 ' . number_format($row['reward_value']) . ' coins added to your balance!';

                    } elseif ($row['reward_type'] === 'unlockable') {
                        $db->prepare("INSERT IGNORE INTO user_unlockables (user_id, unlockable_id) VALUES (?,?)")
                           ->execute([$uid, $row['reward_value']]);
                        // Get name
                        $uname = $db->prepare("SELECT name, rarity FROM unlockables WHERE id=?");
                        $uname->execute([$row['reward_value']]);
                        $urow = $uname->fetch(PDO::FETCH_ASSOC);
                        $rewardMsg = '🔓 Unlocked: ' . ($urow['name'] ?? 'item') . ' (' . ($urow['rarity'] ?? '') . ')!';

                    } elseif ($row['reward_type'] === 'all_unlockables') {
                        // Grant every unlockable
                        $all = $db->query("SELECT id FROM unlockables")->fetchAll(PDO::FETCH_COLUMN);
                        $ins = $db->prepare("INSERT IGNORE INTO user_unlockables (user_id, unlockable_id) VALUES (?,?)");
                        foreach ($all as $unlId) {
                            $ins->execute([$uid, $unlId]);
                        }
                        $rewardMsg = '🎁 Every item in the collection has been unlocked!';
                    }

                    // Record usage
                    $db->prepare("INSERT INTO redeem_code_uses (code_id, user_id) VALUES (?,?)")
                       ->execute([$row['id'], $uid]);
                    $db->prepare("UPDATE redeem_codes SET times_used = times_used + 1 WHERE id = ?")
                       ->execute([$row['id']]);

                    $db->commit();
                    $success = ['message' => $rewardMsg, 'description' => $row['description']];
                    logActivity($uid, 'redeem_code', $code);

                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

// Current coin balance
$coins = 0;
try {
    $s = $db->prepare("SELECT coins FROM users WHERE id=?");
    $s->execute([$uid]);
    $coins = (int)$s->fetchColumn();
} catch(Exception $e){}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<section class="section">

    <div class="page-header">
        <h1 class="page-title">🎁 Redeem Code</h1>
        <p>Enter a code to claim coins, unlockables or special rewards</p>
    </div>

    <div class="redeem-layout">

        <!-- Main redeem card -->
        <div class="redeem-card">

            <?php if ($success): ?>
            <div class="redeem-success">
                <div class="rs-icon">🎉</div>
                <h2>Code Redeemed!</h2>
                <p class="rs-reward"><?= sanitizeOutput($success['message']) ?></p>
                <?php if ($success['description']): ?>
                <p class="rs-desc"><?= sanitizeOutput($success['description']) ?></p>
                <?php endif; ?>
                <div class="rs-actions">
                    <a href="<?= $base ?>/minigames/lootbox.php" class="btn btn-primary">Open Loot Box</a>
                    <a href="<?= $base ?>/collection.php" class="btn btn-outline">View Collection</a>
                </div>
                <hr style="border-color:var(--border);margin:24px 0">
                <p style="color:var(--text-muted);font-size:13px">Redeem another code:</p>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= sanitizeOutput($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="redeem-form">
                <div class="redeem-input-row">
                    <input
                        type="text"
                        name="code"
                        class="form-input redeem-input"
                        placeholder="Enter code e.g. WELCOME100"
                        maxlength="50"
                        autocomplete="off"
                        autofocus
                        value="<?= sanitizeOutput(strtoupper($_POST['code'] ?? '')) ?>"
                    >
                    <button type="submit" class="btn btn-primary">Redeem</button>
                </div>
                <p class="redeem-hint">Codes are case-insensitive — <strong>FreeCoins5</strong> and <strong>freecoins5</strong> both work.</p>
            </form>

            <!-- Current balance -->
            <div class="redeem-balance">
                <span class="lb-coin-icon">🪙</span>
                <span>Current balance: <strong style="color:var(--gold)"><?= number_format($coins) ?> coins</strong></span>
            </div>
        </div>

        <!-- Info panel -->
        <div class="redeem-info-panel">
            <h3>About Codes</h3>
            <ul class="redeem-info-list">
                <li>💰 Some codes reward coins for the Loot Box</li>
                <li>🔓 Some codes unlock specific items instantly</li>
                <li>🎁 Special codes can unlock the entire collection</li>
                <li>⏳ Some codes expire or have limited uses</li>
                <li>🚫 Each code can only be used once per account</li>
            </ul>

            <div class="redeem-divider"></div>

            <h3>What to do with coins?</h3>
            <p>Use coins to spin the <a href="<?= $base ?>/minigames/lootbox.php">Loot Box</a> for a chance at legendary towers, cosmetics and titles.</p>
            <ul class="redeem-info-list" style="margin-top:8px">
                <li>🎰 Standard Spin — 100 coins</li>
                <li>⚡ Mega Spin — 500 coins (5× legendary odds)</li>
            </ul>

            <?php if (isAdmin()): ?>
            <div class="redeem-divider"></div>
            <a href="<?= $base ?>/admin/codes.php" class="btn btn-outline btn-full">⚙️ Manage Codes</a>
            <?php endif; ?>
        </div>

    </div>

</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>

<style>
.redeem-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}
.redeem-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 32px;
}
.redeem-success {
    text-align: center;
    margin-bottom: 28px;
}
.rs-icon   { font-size: 4rem; margin-bottom: 12px; }
.rs-reward { font-size: 1.2rem; font-weight: 700; color: var(--success); margin: 8px 0; }
.rs-desc   { color: var(--text-muted); font-size: 14px; margin-bottom: 16px; }
.rs-actions { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 16px; }

.redeem-form { margin-bottom: 20px; }
.redeem-input-row {
    display: flex;
    gap: 12px;
}
.redeem-input {
    flex: 1;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.redeem-hint {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 8px;
}
.redeem-balance {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    font-size: 14px;
    color: var(--text-muted);
}

.redeem-info-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    position: sticky;
    top: 80px;
}
.redeem-info-panel h3 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--text-muted);
    margin-bottom: 12px;
}
.redeem-info-panel p {
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.6;
}
.redeem-info-list {
    list-style: none;
    padding: 0;
}
.redeem-info-list li {
    font-size: 13px;
    color: var(--text-muted);
    padding: 5px 0;
    border-bottom: 1px solid var(--border);
}
.redeem-info-list li:last-child { border-bottom: none; }
.redeem-divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 20px 0;
}

@media (max-width: 768px) {
    .redeem-layout { grid-template-columns: 1fr; }
    .redeem-info-panel { position: static; }
    .redeem-input-row { flex-direction: column; }
}
</style>
