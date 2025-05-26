<?php
// TicketAttachment Model

class TicketAttachment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create new attachment
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO ticket_attachments (ticket_id, user_id, original_name, stored_name, file_type, file_size, file_path) 
            VALUES (:ticket_id, :user_id, :original_name, :stored_name, :file_type, :file_size, :file_path)
        ");

        if ($stmt->execute([
            ':ticket_id' => $data['ticket_id'],
            ':user_id' => $data['user_id'],
            ':original_name' => $data['original_name'],
            ':stored_name' => $data['stored_name'],
            ':file_type' => $data['file_type'],
            ':file_size' => $data['file_size'],
            ':file_path' => $data['file_path']
        ])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    // Get all attachments for a ticket
    public function getByTicket($ticket_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                ta.*,
                u.name as user_name,
                u.role as user_role
            FROM ticket_attachments ta
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE ta.ticket_id = :ticket_id
            ORDER BY ta.created_at ASC
        ");
        $stmt->execute([':ticket_id' => $ticket_id]);
        return $stmt->fetchAll();
    }

    // Find attachment by ID
    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                ta.*,
                u.name as user_name,
                u.role as user_role
            FROM ticket_attachments ta
            LEFT JOIN users u ON ta.user_id = u.id
            WHERE ta.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Delete attachment
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM ticket_attachments WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Get attachments by user
    public function getByUser($user_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                ta.*,
                t.title as ticket_title,
                t.id as ticket_id
            FROM ticket_attachments ta
            LEFT JOIN tickets t ON ta.ticket_id = t.id
            WHERE ta.user_id = :user_id
            ORDER BY ta.created_at DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    // Count attachments for a ticket
    public function countByTicket($ticket_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM ticket_attachments WHERE ticket_id = :ticket_id");
        $stmt->execute([':ticket_id' => $ticket_id]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    // Get attachment file info by stored name
    public function getByStoredName($stored_name)
    {
        $stmt = $this->db->prepare("
            SELECT 
                ta.*,
                t.id as ticket_id,
                t.user_id as ticket_owner_id
            FROM ticket_attachments ta
            LEFT JOIN tickets t ON ta.ticket_id = t.id
            WHERE ta.stored_name = :stored_name
        ");
        $stmt->execute([':stored_name' => $stored_name]);
        return $stmt->fetch();
    }

    // Get storage statistics
    public function getStorageStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_files,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_size
            FROM ticket_attachments
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}
