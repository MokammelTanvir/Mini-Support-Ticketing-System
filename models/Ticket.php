<?php
// Ticket Model

class Ticket
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create new ticket
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO tickets (title, description, user_id, department_id, status) 
            VALUES (:title, :description, :user_id, :department_id, :status)
        ");

        return $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':user_id' => $data['user_id'],
            ':department_id' => $data['department_id'],
            ':status' => $data['status'] ?? 'open'
        ]);
    }

    // Get all tickets with user and department info
    public function getAll($limit = null, $offset = null)
    {
        $sql = "
            SELECT 
                t.*,
                u.name as user_name,
                u.email as user_email,
                d.name as department_name,
                a.name as assigned_agent_name
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN users a ON t.assigned_agent_id = a.id
            ORDER BY t.created_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT :limit";
            if ($offset) {
                $sql .= " OFFSET :offset";
            }
        }

        $stmt = $this->db->prepare($sql);

        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($offset) {
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Find ticket by ID
    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                u.name as user_name,
                u.email as user_email,
                d.name as department_name,
                a.name as assigned_agent_name
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN users a ON t.assigned_agent_id = a.id
            WHERE t.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Get tickets by user
    public function getByUser($user_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                d.name as department_name,
                a.name as assigned_agent_name
            FROM tickets t
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN users a ON t.assigned_agent_id = a.id
            WHERE t.user_id = :user_id
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    // Get tickets by status
    public function getByStatus($status)
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                u.name as user_name,
                u.email as user_email,
                d.name as department_name,
                a.name as assigned_agent_name
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN departments d ON t.department_id = d.id
            LEFT JOIN users a ON t.assigned_agent_id = a.id
            WHERE t.status = :status
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([':status' => $status]);
        return $stmt->fetchAll();
    }

    // Update ticket
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['title'])) {
            $fields[] = "title = :title";
            $params[':title'] = $data['title'];
        }

        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params[':description'] = $data['description'];
        }

        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }

        if (isset($data['assigned_agent_id'])) {
            $fields[] = "assigned_agent_id = :assigned_agent_id";
            $params[':assigned_agent_id'] = $data['assigned_agent_id'];
        }

        if (isset($data['department_id'])) {
            $fields[] = "department_id = :department_id";
            $params[':department_id'] = $data['department_id'];
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        $sql = "UPDATE tickets SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    // Assign ticket to agent
    public function assignToAgent($ticket_id, $agent_id)
    {
        return $this->update($ticket_id, [
            'assigned_agent_id' => $agent_id,
            'status' => 'in_progress'
        ]);
    }

    // Change ticket status
    public function changeStatus($ticket_id, $status)
    {
        return $this->update($ticket_id, ['status' => $status]);
    }

    // Delete ticket
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM tickets WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Get ticket count by status
    public function getCountByStatus()
    {
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count 
            FROM tickets 
            GROUP BY status
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
