<?php
namespace Classes;

class Unlockable {
    private $conn;
    private $table_name = "unlockables";

    public $id;
    public $name;
    public $type;
    public $rarity;
    public $description;
    public $unlocked_by_default;
    public $image_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all unlockables
    function readAll() {
        $query = "SELECT id, name, type, rarity, description, unlocked_by_default, image_url FROM " . $this->table_name . " ORDER BY type, rarity";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get unlockables by type
    function readByType($type) {
        $query = "SELECT id, name, type, rarity, description, unlocked_by_default, image_url FROM " . $this->table_name . " WHERE type = ? ORDER BY rarity";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $type);
        $stmt->execute();
        return $stmt;
    }

    // Get unlockable by ID
    function readById() {
        $query = "SELECT id, name, type, rarity, description, unlocked_by_default, image_url FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->name = $row['name'];
            $this->type = $row['type'];
            $this->rarity = $row['rarity'];
            $this->description = $row['description'];
            $this->unlocked_by_default = $row['unlocked_by_default'];
            $this->image_url = $row['image_url'];
            return true;
        }
        return false;
    }

    // Check if a user has unlocked a specific unlockable
    function isUnlockedByUser($user_id) {
        $query = "SELECT COUNT(*) FROM user_unlockables WHERE user_id = ? AND unlockable_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $this->id);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count > 0;
    }

    // Unlock an unlockable for a user
    function unlockForUser($user_id) {
        // First check if already unlocked to avoid duplicates
        if ($this->isUnlockedByUser($user_id)) {
            return false; // Already unlocked
        }
        $query = "INSERT INTO user_unlockables (user_id, unlockable_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get all unlockables for a user
    function getUserUnlockables($user_id) {
        $query = "SELECT u.id, u.name, u.type, u.rarity, u.description, u.unlocked_by_default, u.image_url, uu.unlocked_at 
                  FROM " . $this->table_name . " u
                  JOIN user_unlockables uu ON u.id = uu.unlockable_id
                  WHERE uu.user_id = ?
                  ORDER BY u.type, u.rarity";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Get unlockables that a user has NOT unlocked
    function getLockedUnlockables($user_id) {
        $query = "SELECT u.id, u.name, u.type, u.rarity, u.description, u.unlocked_by_default, u.image_url 
                  FROM " . $this->table_name . " u
                  WHERE u.id NOT IN (
                      SELECT unlockable_id FROM user_unlockables WHERE user_id = ?
                  )
                  ORDER BY u.type, u.rarity";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }
}
?>