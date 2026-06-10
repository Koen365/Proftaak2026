<?php
namespace Classes;

class Tower {
    private $conn;
    private $table_name = "towers";

    public $id;
    public $name;
    public $description;
    public $damage;
    public $attack_speed;
    public $range;
    public $cost;
    public $image_url;
    public $unlocked_by_default;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all towers
    function readAll() {
        $query = "SELECT id, name, description, damage, attack_speed, range, cost, image_url, unlocked_by_default FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get tower by ID
    function readById() {
        $query = "SELECT id, name, description, damage, attack_speed, range, cost, image_url, unlocked_by_default FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->damage = $row['damage'];
            $this->attack_speed = $row['attack_speed'];
            $this->range = $row['range'];
            $this->cost = $row['cost'];
            $this->image_url = $row['image_url'];
            $this->unlocked_by_default = $row['unlocked_by_default'];
            return true;
        }
        return false;
    }

    // Create a new tower (for admin use)
    function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, description, damage, attack_speed, range, cost, image_url, unlocked_by_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->damage = htmlspecialchars(strip_tags($this->damage));
        $this->attack_speed = htmlspecialchars(strip_tags($this->attack_speed));
        $this->range = htmlspecialchars(strip_tags($this->range));
        $this->cost = htmlspecialchars(strip_tags($this->cost));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->unlocked_by_default = htmlspecialchars(strip_tags($this->unlocked_by_default));

        // Bind values
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->damage);
        $stmt->bindParam(4, $this->attack_speed);
        $stmt->bindParam(5, $this->range);
        $stmt->bindParam(6, $this->cost);
        $stmt->bindParam(7, $this->image_url);
        $stmt->bindParam(8, $this->unlocked_by_default);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update tower
    function update() {
        $query = "UPDATE " . $this->table_name . " SET name = ?, description = ?, damage = ?, attack_speed = ?, range = ?, cost = ?, image_url = ?, unlocked_by_default = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->damage = htmlspecialchars(strip_tags($this->damage));
        $this->attack_speed = htmlspecialchars(strip_tags($this->attack_speed));
        $this->range = htmlspecialchars(strip_tags($this->range));
        $this->cost = htmlspecialchars(strip_tags($this->cost));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->unlocked_by_default = htmlspecialchars(strip_tags($this->unlocked_by_default));

        // Bind values
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->damage);
        $stmt->bindParam(4, $this->attack_speed);
        $stmt->bindParam(5, $this->range);
        $stmt->bindParam(6, $this->cost);
        $stmt->bindParam(7, $this->image_url);
        $stmt->bindParam(8, $this->unlocked_by_default);
        $stmt->bindParam(9, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete tower
    function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>