<?php
namespace Classes;

class Map {
    private $conn;
    private $table_name = "maps";

    public $id;
    public $name;
    public $description;
    public $grid_width;
    public $grid_height;
    public $background_image;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all maps
    function readAll() {
        $query = "SELECT id, name, description, grid_width, grid_height, background_image, created_at FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get map by ID
    function readById() {
        $query = "SELECT id, name, description, grid_width, grid_height, background_image, created_at FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->grid_width = $row['grid_width'];
            $this->grid_height = $row['grid_height'];
            $this->background_image = $row['background_image'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Create a new map (for admin use)
    function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, description, grid_width, grid_height, background_image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->grid_width = htmlspecialchars(strip_tags($this->grid_width));
        $this->grid_height = htmlspecialchars(strip_tags($this->grid_height));
        $this->background_image = htmlspecialchars(strip_tags($this->background_image));

        // Bind values
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->grid_width);
        $stmt->bindParam(4, $this->grid_height);
        $stmt->bindParam(5, $this->background_image);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update map
    function update() {
        $query = "UPDATE " . $this->table_name . " SET name = ?, description = ?, grid_width = ?, grid_height = ?, background_image = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->grid_width = htmlspecialchars(strip_tags($this->grid_width));
        $this->grid_height = htmlspecialchars(strip_tags($this->grid_height));
        $this->background_image = htmlspecialchars(strip_tags($this->background_image));

        // Bind values
        $stmt->bindParam(1, $this->name);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->grid_width);
        $stmt->bindParam(4, $this->grid_height);
        $stmt->bindParam(5, $this->background_image);
        $stmt->bindParam(6, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete map
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