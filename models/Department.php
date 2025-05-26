<?php
// Department Model

class Department
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create new department
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO departments (name) VALUES (:name)
        ");

        return $stmt->execute([':name' => $data['name']]);
    }

    // Get all departments
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM departments ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Find department by ID
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Find department by name
    public function findByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE name = :name");
        $stmt->execute([':name' => $name]);
        return $stmt->fetch();
    }

    // Update department
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE departments 
            SET name = :name, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name']
        ]);
    }

    // Delete department
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM departments WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Check if department name exists
    public function nameExists($name, $excludeId = null)
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM departments WHERE name = :name AND id != :id");
            $stmt->execute([':name' => $name, ':id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM departments WHERE name = :name");
            $stmt->execute([':name' => $name]);
        }

        return $stmt->fetch() !== false;
    }

    // Get department with ticket count
    public function getWithTicketCount()
    {
        $stmt = $this->db->prepare("
            SELECT d.*, COUNT(t.id) as ticket_count 
            FROM departments d 
            LEFT JOIN tickets t ON d.id = t.department_id 
            GROUP BY d.id 
            ORDER BY d.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
