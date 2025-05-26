<?php
// Ticket Controller

class TicketController
{
    private $ticketModel;
    private $userModel;
    private $departmentModel;

    public function __construct()
    {
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->departmentModel = new Department();
    }

    // Get all tickets (with role-based filtering)
    public function index()
    {
        try {
            // Verify authentication
            $user = Auth::getUser();
            if (!$user) {
                Response::error('Authentication required', 401);
                return;
            }

            // Parse query parameters
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;

            // Role-based access control
            if ($user['role'] === 'admin' || $user['role'] === 'agent') {
                // Admins and agents can see all tickets
                if ($status) {
                    $tickets = $this->ticketModel->getByStatus($status);
                } else {
                    $tickets = $this->ticketModel->getAll($limit, $offset);
                }
            } else {
                // Regular users (including 'user' role) can only see their own tickets
                $tickets = $this->ticketModel->getByUser($user['id']);
            }

            // Filter by department if specified
            if ($department_id && ($user['role'] === 'admin' || $user['role'] === 'agent')) {
                $tickets = array_filter($tickets, function ($ticket) use ($department_id) {
                    return $ticket['department_id'] == $department_id;
                });
                $tickets = array_values($tickets); // Re-index array
            }

            Response::success('Tickets retrieved successfully', $tickets);
        } catch (Exception $e) {
            Response::error('Failed to retrieve tickets: ' . $e->getMessage(), 500);
        }
    }

    // Get single ticket by ID
    public function show($id)
    {
        try {
            // Verify authentication
            $user = Auth::getUser();
            if (!$user) {
                Response::error('Authentication required', 401);
                return;
            }

            $ticket = $this->ticketModel->findById($id);
            if (!$ticket) {
                Response::error('Ticket not found', 404);
                return;
            }

            // Role-based access control
            if ($user['role'] !== 'admin' && $user['role'] !== 'agent') {
                // Regular users (including 'user' role) can only view their own tickets
                if ($ticket['user_id'] != $user['id']) {
                    Response::error('Access denied', 403);
                    return;
                }
            }

            Response::success('Ticket retrieved successfully', $ticket);
        } catch (Exception $e) {
            Response::error('Failed to retrieve ticket: ' . $e->getMessage(), 500);
        }
    }

    // Create new ticket
    public function store()
    {
        try {
            // Verify authentication
            $user = Auth::getUser();
            if (!$user) {
                Response::error('Authentication required', 401);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            // Validation
            if (!isset($input['title']) || empty(trim($input['title']))) {
                Response::error('Title is required', 400);
                return;
            }

            if (!isset($input['description']) || empty(trim($input['description']))) {
                Response::error('Description is required', 400);
                return;
            }

            if (!isset($input['department_id']) || !is_numeric($input['department_id'])) {
                Response::error('Valid department ID is required', 400);
                return;
            }

            // Verify department exists
            $department = $this->departmentModel->findById($input['department_id']);
            if (!$department) {
                Response::error('Department not found', 404);
                return;
            }

            // Prepare ticket data
            $ticketData = [
                'title' => trim($input['title']),
                'description' => trim($input['description']),
                'user_id' => $user['id'],
                'department_id' => $input['department_id'],
                'status' => 'open'
            ];

            if ($this->ticketModel->create($ticketData)) {
                // Get the created ticket (since create doesn't return the ticket)
                $tickets = $this->ticketModel->getByUser($user['id']);
                $newTicket = $tickets[0]; // Most recent ticket

                Response::success('Ticket created successfully', $newTicket, 201);
            } else {
                Response::error('Failed to create ticket', 500);
            }
        } catch (Exception $e) {
            Response::error('Failed to create ticket: ' . $e->getMessage(), 500);
        }
    }

    // Update ticket
    public function update($id)
    {
        try {
            // Verify authentication
            $user = Auth::getUser();
            if (!$user) {
                Response::error('Authentication required', 401);
                return;
            }

            $ticket = $this->ticketModel->findById($id);
            if (!$ticket) {
                Response::error('Ticket not found', 404);
                return;
            }

            // Role-based access control
            $canUpdateAll = ($user['role'] === 'admin' || $user['role'] === 'agent');
            $isOwner = ($ticket['user_id'] == $user['id']);

            if (!$canUpdateAll && !$isOwner) {
                Response::error('Access denied', 403);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $updateData = [];

            // Regular users can only update title and description of their own open tickets
            if (!$canUpdateAll) {
                if ($ticket['status'] !== 'open') {
                    Response::error('Can only update open tickets', 400);
                    return;
                }

                if (isset($input['title']) && !empty(trim($input['title']))) {
                    $updateData['title'] = trim($input['title']);
                }

                if (isset($input['description']) && !empty(trim($input['description']))) {
                    $updateData['description'] = trim($input['description']);
                }
            } else {
                // Admins and agents can update everything
                if (isset($input['title']) && !empty(trim($input['title']))) {
                    $updateData['title'] = trim($input['title']);
                }

                if (isset($input['description']) && !empty(trim($input['description']))) {
                    $updateData['description'] = trim($input['description']);
                }

                if (isset($input['status']) && in_array($input['status'], ['open', 'in_progress', 'resolved', 'closed'])) {
                    $updateData['status'] = $input['status'];
                }

                if (isset($input['assigned_agent_id'])) {
                    if ($input['assigned_agent_id'] === null) {
                        $updateData['assigned_agent_id'] = null;
                    } else {
                        // Verify agent exists and is an agent/admin
                        $agent = $this->userModel->findById($input['assigned_agent_id']);
                        if (!$agent || ($agent['role'] !== 'agent' && $agent['role'] !== 'admin')) {
                            Response::error('Invalid agent ID', 400);
                            return;
                        }
                        $updateData['assigned_agent_id'] = $input['assigned_agent_id'];
                    }
                }

                if (isset($input['department_id']) && is_numeric($input['department_id'])) {
                    $department = $this->departmentModel->findById($input['department_id']);
                    if (!$department) {
                        Response::error('Department not found', 404);
                        return;
                    }
                    $updateData['department_id'] = $input['department_id'];
                }
            }

            if (empty($updateData)) {
                Response::error('No valid fields to update', 400);
                return;
            }

            if ($this->ticketModel->update($id, $updateData)) {
                $updatedTicket = $this->ticketModel->findById($id);
                Response::success('Ticket updated successfully', $updatedTicket);
            } else {
                Response::error('Failed to update ticket', 500);
            }
        } catch (Exception $e) {
            Response::error('Failed to update ticket: ' . $e->getMessage(), 500);
        }
    }

    // Delete ticket (admin only)
    public function destroy($id)
    {
        try {
            // Verify authentication and admin role
            $user = Auth::getUser();
            if (!$user || $user['role'] !== 'admin') {
                Response::error('Admin access required', 403);
                return;
            }

            $ticket = $this->ticketModel->findById($id);
            if (!$ticket) {
                Response::error('Ticket not found', 404);
                return;
            }

            if ($this->ticketModel->delete($id)) {
                Response::success('Ticket deleted successfully');
            } else {
                Response::error('Failed to delete ticket', 500);
            }
        } catch (Exception $e) {
            Response::error('Failed to delete ticket: ' . $e->getMessage(), 500);
        }
    }

    // Assign ticket to agent (admin/agent only)
    public function assign($id)
    {
        try {
            // Verify authentication and role
            $user = Auth::getUser();
            if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'agent')) {
                Response::error('Admin or agent access required', 403);
                return;
            }

            $ticket = $this->ticketModel->findById($id);
            if (!$ticket) {
                Response::error('Ticket not found', 404);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['agent_id']) || !is_numeric($input['agent_id'])) {
                Response::error('Valid agent ID is required', 400);
                return;
            }

            // Verify agent exists and is an agent/admin
            $agent = $this->userModel->findById($input['agent_id']);
            if (!$agent || ($agent['role'] !== 'agent' && $agent['role'] !== 'admin')) {
                Response::error('Invalid agent ID', 400);
                return;
            }

            if ($this->ticketModel->assignToAgent($id, $input['agent_id'])) {
                $updatedTicket = $this->ticketModel->findById($id);
                Response::success('Ticket assigned successfully', $updatedTicket);
            } else {
                Response::error('Failed to assign ticket', 500);
            }
        } catch (Exception $e) {
            Response::error('Failed to assign ticket: ' . $e->getMessage(), 500);
        }
    }

    // Change ticket status (admin/agent only)
    public function changeStatus($id)
    {
        try {
            // Verify authentication and role
            $user = Auth::getUser();
            if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'agent')) {
                Response::error('Admin or agent access required', 403);
                return;
            }

            $ticket = $this->ticketModel->findById($id);
            if (!$ticket) {
                Response::error('Ticket not found', 404);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['status']) || !in_array($input['status'], ['open', 'in_progress', 'resolved', 'closed'])) {
                Response::error('Valid status is required (open, in_progress, resolved, closed)', 400);
                return;
            }

            if ($this->ticketModel->changeStatus($id, $input['status'])) {
                $updatedTicket = $this->ticketModel->findById($id);
                Response::success('Ticket status updated successfully', $updatedTicket);
            } else {
                Response::error('Failed to update ticket status', 500);
            }
        } catch (Exception $e) {
            Response::error('Failed to update ticket status: ' . $e->getMessage(), 500);
        }
    }

    // Get ticket statistics (admin/agent only)
    public function getStats()
    {
        try {
            // Verify authentication and role
            $user = Auth::getUser();
            if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'agent')) {
                Response::error('Admin or agent access required', 403);
                return;
            }

            $statusCounts = $this->ticketModel->getCountByStatus();

            $stats = [
                'total_tickets' => 0,
                'by_status' => []
            ];

            foreach ($statusCounts as $statusCount) {
                $stats['by_status'][$statusCount['status']] = (int)$statusCount['count'];
                $stats['total_tickets'] += (int)$statusCount['count'];
            }

            Response::success('Ticket statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            Response::error('Failed to retrieve ticket statistics: ' . $e->getMessage(), 500);
        }
    }

    // Get tickets assigned to current user (agent only)
    public function getMyAssigned()
    {
        try {
            // Verify authentication and role
            $user = Auth::getUser();
            if (!$user || $user['role'] !== 'agent') {
                Response::error('Agent access required', 403);
                return;
            }

            $tickets = $this->ticketModel->getAll();
            $myTickets = array_filter($tickets, function ($ticket) use ($user) {
                return $ticket['assigned_agent_id'] == $user['id'];
            });

            $myTickets = array_values($myTickets); // Re-index array

            Response::success('Assigned tickets retrieved successfully', $myTickets);
        } catch (Exception $e) {
            Response::error('Failed to retrieve assigned tickets: ' . $e->getMessage(), 500);
        }
    }
}
