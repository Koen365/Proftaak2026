<?php
namespace Classes;

class Admin {
    private $conn;
    private $table_name = "users"; // Admins are just users with role='admin'

    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if user is admin
    function isAdmin($user_id) {
        $query = "SELECT role FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            return $row['role'] === 'admin';
        }
        return false;
    }

    // Get all users (for admin panel)
    function getAllUsers() {
        $query = "SELECT id, username, email, role, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get user by ID (for admin panel)
    function getUserById($user_id) {
        $query = "SELECT id, username, email, role, created_at FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Update user role (make admin or remove admin)
    function updateUserRole($user_id, $role) {
        $query = "UPDATE " . $this->table_name . " SET role = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $role = htmlspecialchars(strip_tags($role));

        // Bind values
        $stmt->bindParam(1, $role);
        $stmt->bindParam(2, $user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete user (admin function)
    function deleteUser($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get stats for admin dashboard
    function getStats() {
        $stats = array();

        // Total users
        $query = "SELECT COUNT(*) as total FROM users";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['total_users'] = $row['total'];

        // Total admins
        $query = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['total_admins'] = $row['total'];

        // Total scores
        $query = "SELECT COUNT(*) as total FROM scores";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['total_scores'] = $row['total'];

        // Total news
        $query = "SELECT COUNT(*) as total FROM news";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['total_news'] = $row['total'];

        return $stats;
    }
}
?>