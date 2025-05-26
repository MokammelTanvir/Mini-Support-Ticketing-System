<?php
// User Model

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create new user
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password_hash, role) 
            VALUES (:name, :email, :password_hash, :role)
        ");

        return $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role' => $data['role'] ?? 'agent'
        ]);
    }

    // Find user by email
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    // Find user by ID
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Get all users (admin only)
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Verify password
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    // Check if email exists
    public function emailExists($email)
    {
        $user = $this->findByEmail($email);
        return $user !== false;
    }

    // Update user
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }

        if (isset($data['role'])) {
            $fields[] = "role = :role";
            $params[':role'] = $data['role'];
        }

        if (isset($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    // Delete user
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Get user by role
    public function getByRole($role)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users WHERE role = :role ORDER BY name");
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }
}
