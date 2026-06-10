<?php
namespace Classes;

class Leaderboard {
    private $conn;
    private $table_name = "leaderboard_cache";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTopScores($limit = 10, $map_id = null, $type = 'score') {
        $score = new Score($this->conn);
        return $score->getLeaderboard($limit, $map_id, $type);
    }

    // Get user's rank
    public function getUserRank($user_id, $map_id = null, $type = 'score') {
        $score = new Score($this->conn);
        // We'll get the leaderboard and find the user's position
        // For simplicity, we'll get a large leaderboard and count
        $leaderboard = $score->getLeaderboard(1000, $map_id, $type); // Get top 1000
        $rank = 1;
        $found = false;
        while ($row = $leaderboard->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['user_id'] == $user_id) {
                $found = true;
                break;
            }
            $rank++;
        }
        if (!$found) {
            // User not in top 1000, we can compute their rank by counting how many are better
            // For now, return null or a large number
            return null;
        }
        return $rank;
    }

    // Get user's score for a specific map
    public function getUserScore($user_id, $map_id) {
        $score = new Score($this->conn);
        return $score->getBestScore($user_id, $map_id);
    }
}
?>