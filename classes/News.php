<?php
namespace Classes;

class News {
    private $conn;
    private $table_name = "news";

    public $id;
    public $title;
    public $content;
    public $author_id;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function ensureConnection() {
        if (!$this->conn) {
            throw new \RuntimeException('News database connection is not available.');
        }
    }

    // Create news article
    function create() {
        $query = "INSERT INTO " . $this->table_name . " (title, content, author_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->author_id = htmlspecialchars(strip_tags($this->author_id));

        // Bind values
        $stmt->bindParam(1, $this->title);
        $stmt->bindParam(2, $this->content);
        $stmt->bindParam(3, $this->author_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get all news articles
    function readAll() {
        $this->ensureConnection();
        $query = "SELECT n.id, n.title, n.content, n.author_id, n.created_at, n.updated_at, u.username as author_name 
                  FROM " . $this->table_name . " n
                  JOIN users u ON n.author_id = u.id
                  ORDER BY n.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get news by ID
    function readById() {
        $query = "SELECT n.id, n.title, n.content, n.author_id, n.created_at, n.updated_at, u.username as author_name 
                  FROM " . $this->table_name . " n
                  JOIN users u ON n.author_id = u.id
                  WHERE n.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->title = $row['title'];
            $this->content = $row['content'];
            $this->author_id = $row['author_id'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update news article
    function update() {
        $query = "UPDATE " . $this->table_name . " SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));

        // Bind values
        $stmt->bindParam(1, $this->title);
        $stmt->bindParam(2, $this->content);
        $stmt->bindParam(3, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete news article
    function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get news by author ID
    function readByAuthor($author_id) {
        $query = "SELECT n.id, n.title, n.content, n.created_at, n.updated_at 
                  FROM " . $this->table_name . " n
                  WHERE n.author_id = ?
                  ORDER BY n.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $author_id);
        $stmt->execute();
        return $stmt;
    }
}
?>