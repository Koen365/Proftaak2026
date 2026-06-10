<?php
namespace Classes;

class Minigame {
    private $conn;
    private $table_name = "minigames";

    public $id;
    public $name;
    public $description;
    public $type;
    public $unlocked_by_default;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all minigames
    function readAll() {
        $query = "SELECT id, name, description, type, unlocked_by_default FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get minigame by ID
    function readById() {
        $query = "SELECT id, name, description, type, unlocked_by_default FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->type = $row['type'];
            $this->unlocked_by_default = $row['unlocked_by_default'];
            return true;
        }
        return false;
    }

    // Get minigames by type
    function readByType($type) {
        $query = "SELECT id, name, description, type, unlocked_by_default FROM " . $this->table_name . " WHERE type = ? ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $type);
        $stmt->execute();
        return $stmt;
    }

    // Create a new minigame (for admin use)
    function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, description, type, unlocked_by_default) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->unlocked_by_default = htmlspecialchars(strip_tags($this->unlocked_by_default));

        // Bind values
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->type);
        $stmt->bindParam(4, $this->unlocked_by_default);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update minigame
    function update() {
        $query = "UPDATE " . $this->table_name . " SET name = ?, description = ?, type = ?, unlocked_by_default = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->unlocked_by_default = htmlspecialchars(strip_tags($this->unlocked_by_default));

        // Bind values
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->type);
        $stmt->bindParam(4, $this->unlocked_by_default);
        $stmt->bindParam(5, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete minigame
    function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get user's progress for a minigame
    function getUserProgress($user_id) {
        $query = "SELECT ump.best_score, ump.last_played FROM user_minigame_progress ump 
                  WHERE ump.user_id = ? AND ump.minigame_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $this->id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Update user's progress for a minigame
    function updateUserProgress($user_id, $score) {
        // First check if progress exists
        $progress = $this->getUserProgress($user_id);
        if ($progress) {
            // Update if new score is better
            if ($score > $progress['best_score']) {
                $query = "UPDATE user_minigame_progress SET best_score = ?, last_played = CURRENT_TIMESTAMP 
                          WHERE user_id = ? AND minigame_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $score);
                $stmt->bindParam(2, $user_id);
                $stmt->bindParam(3, $this->id);
                return $stmt->execute();
            } else {
                // Just update last played time
                $query = "UPDATE user_minigame_progress SET last_played = CURRENT_TIMESTAMP 
                          WHERE user_id = ? AND minigame_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $user_id);
                $stmt->bindParam(2, $this->id);
                return $stmt->execute();
            }
        } else {
            // Insert new progress
            $query = "INSERT INTO user_minigame_progress (user_id, minigame_id, best_score) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $this->id);
            $stmt->bindParam(3, $score);
            return $stmt->execute();
        }
    }
}
?>