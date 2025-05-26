<?php
// TicketNoteController - Ticket Notes Management API

class TicketNoteController
{
    private $ticketNoteModel;
    private $ticketModel;

    public function __construct()
    {
        $this->ticketNoteModel = new TicketNote();
        $this->ticketModel = new Ticket();
    }

    // Get all notes for a specific ticket
    public function index($ticketId)
    {
        // Require authentication
        Auth::requireAuth();

        $current_user_id = Auth::getUserId();
        $current_user_role = Auth::getUserRole();

        // Check if ticket exists
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            Response::notFound('Ticket not found');
        }

        // Role-based access control
        if ($current_user_role === 'user' && $ticket['user_id'] != $current_user_id) {
            Response::forbidden('You can only view notes for your own tickets');
        }

        // Get ticket notes
        $notes = $this->ticketNoteModel->getByTicketId($ticketId);

        Response::success('Ticket notes retrieved successfully', $notes);
    }

    // Get specific note by ID
    public function show($ticketId, $noteId)
    {
        // Require authentication
        Auth::requireAuth();

        $current_user_id = Auth::getUserId();
        $current_user_role = Auth::getUserRole();

        // Check if ticket exists
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            Response::notFound('Ticket not found');
        }

        // Role-based access control for ticket
        if ($current_user_role === 'user' && $ticket['user_id'] != $current_user_id) {
            Response::forbidden('You can only view notes for your own tickets');
        }

        // Find note
        $note = $this->ticketNoteModel->findById($noteId);
        if (!$note) {
            Response::notFound('Note not found');
        }

        // Verify note belongs to the ticket
        if ($note['ticket_id'] != $ticketId) {
            Response::notFound('Note not found for this ticket');
        }

        Response::success('Note retrieved successfully', $note);
    }

    // Add new note to ticket
    public function store($ticketId)
    {
        // Require authentication
        Auth::requireAuth();

        $current_user_id = Auth::getUserId();
        $current_user_role = Auth::getUserRole();

        // Check if ticket exists
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            Response::notFound('Ticket not found');
        }

        // Role-based access control
        if ($current_user_role === 'user' && $ticket['user_id'] != $current_user_id) {
            Response::forbidden('You can only add notes to your own tickets');
        }

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (!isset($data['note']) || empty(trim($data['note']))) {
            Response::error('Note content is required', 400);
        }

        // Prepare note data
        $noteData = [
            'ticket_id' => $ticketId,
            'user_id' => $current_user_id,
            'note' => trim($data['note'])
        ];

        // Create note
        $noteId = $this->ticketNoteModel->create($noteData);
        if ($noteId) {
            // Get the created note
            $note = $this->ticketNoteModel->findById($noteId);
            Response::created('Note added successfully', $note);
        } else {
            Response::error('Failed to add note', 500);
        }
    }

    // Update note (only note creator can update)
    public function update($ticketId, $noteId)
    {
        // Require authentication
        Auth::requireAuth();

        $current_user_id = Auth::getUserId();
        $current_user_role = Auth::getUserRole();

        // Check if ticket exists
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            Response::notFound('Ticket not found');
        }

        // Find note
        $note = $this->ticketNoteModel->findById($noteId);
        if (!$note) {
            Response::notFound('Note not found');
        }

        // Verify note belongs to the ticket
        if ($note['ticket_id'] != $ticketId) {
            Response::notFound('Note not found for this ticket');
        }

        // Only note creator or admin can update
        if ($current_user_role !== 'admin' && $note['user_id'] != $current_user_id) {
            Response::forbidden('You can only update your own notes');
        }

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate note content
        if (!isset($data['note']) || empty(trim($data['note']))) {
            Response::error('Note content is required', 400);
        }

        // Update note
        $updateData = [
            'note' => trim($data['note'])
        ];

        if ($this->ticketNoteModel->update($noteId, $updateData)) {
            // Get updated note
            $updatedNote = $this->ticketNoteModel->findById($noteId);
            Response::success('Note updated successfully', $updatedNote);
        } else {
            Response::error('Failed to update note', 500);
        }
    }

    // Delete note (only note creator or admin can delete)
    public function destroy($ticketId, $noteId)
    {
        // Require authentication
        Auth::requireAuth();

        $current_user_id = Auth::getUserId();
        $current_user_role = Auth::getUserRole();

        // Check if ticket exists
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            Response::notFound('Ticket not found');
        }

        // Find note
        $note = $this->ticketNoteModel->findById($noteId);
        if (!$note) {
            Response::notFound('Note not found');
        }

        // Verify note belongs to the ticket
        if ($note['ticket_id'] != $ticketId) {
            Response::notFound('Note not found for this ticket');
        }

        // Only note creator or admin can delete
        if ($current_user_role !== 'admin' && $note['user_id'] != $current_user_id) {
            Response::forbidden('You can only delete your own notes');
        }

        // Delete note
        if ($this->ticketNoteModel->delete($noteId)) {
            Response::success('Note deleted successfully');
        } else {
            Response::error('Failed to delete note', 500);
        }
    }

    // Get notes by specific user (admin/agent only)
    public function getByUser($userId)
    {
        // Require admin or agent authentication
        Auth::requireAuth();

        $current_user_role = Auth::getUserRole();
        if (!in_array($current_user_role, ['admin', 'agent'])) {
            Response::forbidden('Access denied. Admin or agent role required');
        }

        // Get notes by user
        $notes = $this->ticketNoteModel->getByUserId($userId);

        Response::success("Notes by user retrieved successfully", $notes);
    }
}
