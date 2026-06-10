<?php
namespace Classes;

/**
 * UnlockManager
 * ─────────────────────────────────────────────────────────────────────────────
 * Handles all unlock logic:
 *   • Score/wave-based unlocks from playing Tower Defense
 *   • Weighted-random loot box spins
 *   • Coin rewards for playing
 *   • Querying what a user still needs to unlock next
 */
class UnlockManager
{
    private \PDO $db;

    // Coins awarded per score point and per wave survived
    const COINS_PER_100_SCORE = 1;
    const COINS_PER_WAVE      = 5;
    const LOOT_SPIN_COST      = 100;    // Standard spin
    const LOOT_SPIN_COST_MEGA = 500;    // Mega spin (better odds multiplier)

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Called after a game session ends (from upload_score.php)
    // Returns array of newly unlocked items
    // ─────────────────────────────────────────────────────────────────────────
    public function checkGameUnlocks(int $userId, int $score, int $wavesSurvived): array
    {
        // Update user lifetime totals
        $this->db->prepare("
            UPDATE users
            SET total_score = total_score + ?,
                total_waves  = GREATEST(total_waves, ?)
            WHERE id = ?
        ")->execute([$score, $wavesSurvived, $userId]);

        // Award coins
        $coins = (int)floor($score / 100) * self::COINS_PER_100_SCORE
               + $wavesSurvived * self::COINS_PER_WAVE;
        if ($coins > 0) {
            $this->db->prepare("UPDATE users SET coins = coins + ? WHERE id = ?")
                     ->execute([$coins, $userId]);
        }

        // Fetch user totals
        $row = $this->db->prepare("SELECT total_score, total_waves FROM users WHERE id = ?");
        $row->execute([$userId]);
        $totals = $row->fetch(\PDO::FETCH_ASSOC);
        $totalScore = (int)($totals['total_score'] ?? 0);
        $totalWaves = (int)($totals['total_waves'] ?? 0);

        // Find all score/wave-unlockable items the user doesn't own yet
        $candidates = $this->db->prepare("
            SELECT u.*
            FROM unlockables u
            WHERE u.unlock_type IN ('score','waves','both')
              AND u.unlocked_by_default = 0
              AND u.id NOT IN (
                  SELECT unlockable_id FROM user_unlockables WHERE user_id = ?
              )
        ");
        $candidates->execute([$userId]);
        $items = $candidates->fetchAll(\PDO::FETCH_ASSOC);

        $newUnlocks = [];
        foreach ($items as $item) {
            $earned = false;
            if ($item['unlock_type'] === 'score' && $totalScore >= (int)$item['unlock_score']) {
                $earned = true;
            } elseif ($item['unlock_type'] === 'waves' && $totalWaves >= (int)$item['unlock_waves']) {
                $earned = true;
            } elseif ($item['unlock_type'] === 'both'
                   && $totalScore >= (int)$item['unlock_score']
                   && $totalWaves >= (int)$item['unlock_waves']) {
                $earned = true;
            }

            if ($earned) {
                $this->grantUnlock($userId, (int)$item['id']);
                $newUnlocks[] = $item;
            }
        }

        return [
            'new_unlocks' => $newUnlocks,
            'coins_earned' => $coins,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Loot box spin — weighted random draw from the loot pool
    // Returns the unlockable that was rolled (may already be owned → duplicate)
    // ─────────────────────────────────────────────────────────────────────────
    public function spin(int $userId, string $type = 'standard'): array
    {
        $cost = $type === 'mega' ? self::LOOT_SPIN_COST_MEGA : self::LOOT_SPIN_COST;

        // Check balance
        $bal = $this->db->prepare("SELECT coins FROM users WHERE id = ?");
        $bal->execute([$userId]);
        $coins = (int)($bal->fetchColumn() ?? 0);

        if ($coins < $cost) {
            return ['success' => false, 'message' => 'Not enough coins. You need ' . $cost . ' coins.'];
        }

        // Deduct coins
        $this->db->prepare("UPDATE users SET coins = coins - ? WHERE id = ?")
                 ->execute([$cost, $userId]);

        // Build weighted pool
        $pool = $this->db->query("SELECT id, name, type, rarity, description, loot_weight FROM unlockables WHERE loot_pool = 1 AND loot_weight > 0")
                         ->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($pool)) {
            // Refund and error
            $this->db->prepare("UPDATE users SET coins = coins + ? WHERE id = ?")
                     ->execute([$cost, $userId]);
            return ['success' => false, 'message' => 'Loot pool is empty.'];
        }

        // Mega spin: legendary weight × 5
        if ($type === 'mega') {
            foreach ($pool as &$item) {
                if ($item['rarity'] === 'legendary') $item['loot_weight'] *= 5;
            }
            unset($item);
        }

        // Weighted random selection
        $totalWeight = array_sum(array_column($pool, 'loot_weight'));
        $roll = mt_rand(1, $totalWeight);
        $cumulative = 0;
        $won = null;
        foreach ($pool as $item) {
            $cumulative += $item['loot_weight'];
            if ($roll <= $cumulative) {
                $won = $item;
                break;
            }
        }

        if (!$won) $won = $pool[array_key_last($pool)];

        // Record spin
        $this->db->prepare("INSERT INTO loot_spins (user_id, unlockable_id, spin_cost) VALUES (?,?,?)")
                 ->execute([$userId, $won['id'], $cost]);

        // Grant if not already owned
        $alreadyOwned = $this->userOwns($userId, (int)$won['id']);
        if (!$alreadyOwned) {
            $this->grantUnlock($userId, (int)$won['id']);
        }

        return [
            'success'      => true,
            'item'         => $won,
            'already_owned'=> $alreadyOwned,
            'is_new'       => !$alreadyOwned,
            'coins_spent'  => $cost,
            'spin_type'    => $type,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Grant an unlock to a user (safe — ignores duplicates)
    // ─────────────────────────────────────────────────────────────────────────
    public function grantUnlock(int $userId, int $unlockableId): bool
    {
        try {
            $this->db->prepare("INSERT IGNORE INTO user_unlockables (user_id, unlockable_id) VALUES (?,?)")
                     ->execute([$userId, $unlockableId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Check if user owns an item
    // ─────────────────────────────────────────────────────────────────────────
    public function userOwns(int $userId, int $unlockableId): bool
    {
        $s = $this->db->prepare("SELECT 1 FROM user_unlockables WHERE user_id=? AND unlockable_id=?");
        $s->execute([$userId, $unlockableId]);
        return (bool)$s->fetchColumn();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Get user coin balance
    // ─────────────────────────────────────────────────────────────────────────
    public function getCoins(int $userId): int
    {
        $s = $this->db->prepare("SELECT coins FROM users WHERE id=?");
        $s->execute([$userId]);
        return (int)($s->fetchColumn() ?? 0);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Get all score/wave unlock thresholds with progress for a user
    // ─────────────────────────────────────────────────────────────────────────
    public function getProgressUnlocks(int $userId): array
    {
        $userRow = $this->db->prepare("SELECT total_score, total_waves, coins FROM users WHERE id=?");
        $userRow->execute([$userId]);
        $user = $userRow->fetch(\PDO::FETCH_ASSOC);
        $totalScore = (int)($user['total_score'] ?? 0);
        $totalWaves = (int)($user['total_waves'] ?? 0);

        $items = $this->db->query("
            SELECT u.*, 
                   CASE WHEN uu.id IS NOT NULL THEN 1 ELSE 0 END as owned
            FROM unlockables u
            LEFT JOIN user_unlockables uu ON u.id = uu.unlockable_id AND uu.user_id = $userId
            WHERE u.unlock_type IN ('score','waves','both')
            ORDER BY u.unlock_score ASC, u.unlock_waves ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            if ($item['unlock_type'] === 'score') {
                $item['progress_pct'] = min(100, (int)floor($totalScore / max(1, $item['unlock_score']) * 100));
                $item['progress_label'] = number_format($totalScore) . ' / ' . number_format($item['unlock_score']) . ' score';
            } elseif ($item['unlock_type'] === 'waves') {
                $item['progress_pct'] = min(100, (int)floor($totalWaves / max(1, $item['unlock_waves']) * 100));
                $item['progress_label'] = $totalWaves . ' / ' . $item['unlock_waves'] . ' waves';
            } else {
                $item['progress_pct'] = 0;
                $item['progress_label'] = '';
            }
        }
        unset($item);

        return [
            'items'       => $items,
            'total_score' => $totalScore,
            'total_waves' => $totalWaves,
            'coins'       => (int)($user['coins'] ?? 0),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Get spin history for a user
    // ─────────────────────────────────────────────────────────────────────────
    public function getSpinHistory(int $userId, int $limit = 20): array
    {
        $limit = (int) $limit;
        $s = $this->db->prepare("
            SELECT ls.spun_at, ls.spin_cost, u.name, u.type, u.rarity
            FROM loot_spins ls
            JOIN unlockables u ON ls.unlockable_id = u.id
            WHERE ls.user_id = ?
            ORDER BY ls.spun_at DESC
            LIMIT $limit
        ");
        $s->execute([$userId]);
        return $s->fetchAll(\PDO::FETCH_ASSOC);
    }
}
