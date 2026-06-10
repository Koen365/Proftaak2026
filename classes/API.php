<?php
namespace Classes;

/**
 * API helper class — thin wrapper for common queries used by API endpoints.
 * All heavy logic lives in the dedicated classes (User, Score, UnlockManager…).
 */
class API
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /** Return all towers */
    public function getTowers(): array
    {
        return $this->db->query("SELECT * FROM towers ORDER BY cost ASC")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Return all enemies */
    public function getEnemies(): array
    {
        return $this->db->query("SELECT * FROM enemies ORDER BY health ASC")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Return all maps */
    public function getMaps(): array
    {
        return $this->db->query("SELECT * FROM maps ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Return unlockables owned by a user (+ default ones).
     */
    public function getUserUnlockables(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.type, u.rarity, u.description, uu.unlocked_at
            FROM unlockables u
            JOIN user_unlockables uu ON u.id = uu.unlockable_id
            WHERE uu.user_id = ?
            ORDER BY u.type, u.rarity
        ");
        $stmt->execute([$userId]);
        $owned = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Include defaults not yet in user_unlockables
        $defaults = $this->db->query("SELECT id,name,type,rarity,description FROM unlockables WHERE unlocked_by_default=1")->fetchAll(\PDO::FETCH_ASSOC);
        $ownedIds = array_column($owned, 'id');
        foreach ($defaults as $d) {
            if (!in_array($d['id'], $ownedIds)) {
                $d['unlocked_at'] = null;
                $owned[] = $d;
            }
        }
        return $owned;
    }

    /**
     * Leaderboard: top scores with optional map/type filter.
     */
    public function getLeaderboard(int $limit = 50, ?int $mapId = null, string $type = 'score'): array
    {
        $where = $mapId ? "WHERE s.map_id = $mapId" : '';
        $order = match($type) {
            'waves' => 's.waves_survived DESC, s.score DESC',
            'time'  => 's.time_survived ASC, s.score DESC',
            default => 's.score DESC, s.waves_survived DESC',
        };
        $sql = "
            SELECT s.id, s.user_id, s.score, s.waves_survived, s.time_survived, s.created_at,
                   u.username, m.name AS map_name
            FROM scores s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN maps  m ON s.map_id  = m.id
            $where
            ORDER BY $order
            LIMIT $limit
        ";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
