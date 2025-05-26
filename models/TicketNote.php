<?php
// TicketNote Model

class TicketNote
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create new ticket note
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO ticket_notes (ticket_id, user_id, note) 
            VALUES (:ticket_id, :user_id, :note)
        ");

        if ($stmt->execute([
            ':ticket_id' => $data['ticket_id'],
            ':user_id' => $data['user_id'],
            ':note' => $data['note']
        ])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    // Get all notes for a ticket
    public function getByTicket($ticket_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                tn.*,
                u.name as user_name,
                u.role as user_role
            FROM ticket_notes tn
            LEFT JOIN users u ON tn.user_id = u.id
            WHERE tn.ticket_id = :ticket_id
            ORDER BY tn.created_at ASC
        ");
        $stmt->execute([':ticket_id' => $ticket_id]);
        return $stmt->fetchAll();
    }

    // Get all notes for a ticket (alias for consistency)
    public function getByTicketId($ticket_id)
    {
        return $this->getByTicket($ticket_id);
    }

    // Find note by ID
    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                tn.*,
                u.name as user_name,
                u.role as user_role
            FROM ticket_notes tn
            LEFT JOIN users u ON tn.user_id = u.id
            WHERE tn.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Update note
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE ticket_notes 
            SET note = :note
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':note' => $data['note']
        ]);
    }

    // Delete note
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM ticket_notes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Get notes by user
    public function getByUser($user_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                tn.*,
                t.title as ticket_title,
                t.id as ticket_id
            FROM ticket_notes tn
            LEFT JOIN tickets t ON tn.ticket_id = t.id
            WHERE tn.user_id = :user_id
            ORDER BY tn.created_at DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    // Get notes by user (alias for consistency)
    public function getByUserId($user_id)
    {
        return $this->getByUser($user_id);
    }

    // Get recent notes (for dashboard)
    public function getRecent($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT 
                tn.*,
                u.name as user_name,
                t.title as ticket_title,
                t.id as ticket_id
            FROM ticket_notes tn
            LEFT JOIN users u ON tn.user_id = u.id
            LEFT JOIN tickets t ON tn.ticket_id = t.id
            ORDER BY tn.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Count notes for a ticket
    public function countByTicket($ticket_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM ticket_notes WHERE ticket_id = :ticket_id");
        $stmt->execute([':ticket_id' => $ticket_id]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
