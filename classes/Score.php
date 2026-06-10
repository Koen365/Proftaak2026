<?php
namespace Classes;

class Score {
    private $conn;
    private $table_name = "scores";

    public $id;
    public $user_id;
    public $map_id;
    public $score;
    public $waves_survived;
    public $time_survived;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new score
    function create() {
        $query = "INSERT INTO " . $this->table_name . " (user_id, map_id, score, waves_survived, time_survived) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->map_id = htmlspecialchars(strip_tags($this->map_id));
        $this->score = htmlspecialchars(strip_tags($this->score));
        $this->waves_survived = htmlspecialchars(strip_tags($this->waves_survived));
        $this->time_survived = htmlspecialchars(strip_tags($this->time_survived));

        // Bind values
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->map_id);
        $stmt->bindParam(3, $this->score);
        $stmt->bindParam(4, $this->waves_survived);
        $stmt->bindParam(5, $this->time_survived);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get scores by user ID
    function readByUserId($user_id) {
        $query = "SELECT s.id, s.user_id, s.map_id, s.score, s.waves_survived, s.time_survived, s.created_at, m.name as map_name 
                  FROM " . $this->table_name . " s
                  LEFT JOIN maps m ON s.map_id = m.id
                  WHERE s.user_id = ?
                  ORDER BY s.score DESC, s.waves_survived DESC, s.time_survived DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Get leaderboard (top scores) with optional filters
    function getLeaderboard($limit = 10, $map_id = null, $type = 'score') {
        $limit = (int) $limit;
        $query = "SELECT s.id, s.user_id, s.score, s.waves_survived, s.time_survived, s.created_at, u.username, m.name as map_name 
                  FROM " . $this->table_name . " s
                  LEFT JOIN users u ON s.user_id = u.id
                  LEFT JOIN maps  m ON s.map_id  = m.id";
        if ($map_id !== null) {
            $query .= " WHERE s.map_id = ?";
        }
        if ($type === 'waves') {
            $query .= " ORDER BY s.waves_survived DESC, s.score DESC, s.time_survived ASC";
        } elseif ($type === 'time') {
            $query .= " ORDER BY s.time_survived ASC, s.score DESC, s.waves_survived DESC";
        } else {
            $query .= " ORDER BY s.score DESC, s.waves_survived DESC, s.time_survived ASC";
        }
        $query .= " LIMIT $limit";
        $stmt = $this->conn->prepare($query);
        if ($map_id !== null) {
            $stmt->bindParam(1, $map_id, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt;
    }

    // Get best score for a user on a specific map
    function getBestScore($user_id, $map_id) {
        $query = "SELECT MAX(score) as best_score, MAX(waves_survived) as best_waves, MIN(time_survived) as best_time 
                  FROM " . $this->table_name . " 
                  WHERE user_id = ? AND map_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $map_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
?>