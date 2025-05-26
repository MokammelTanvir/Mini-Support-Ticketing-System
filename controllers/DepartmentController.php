<?php
// DepartmentController - Department Management API (Admin Only)

class DepartmentController
{
    private $departmentModel;

    public function __construct()
    {
        $this->departmentModel = new Department();
    }

    // Get all departments (accessible to all authenticated users)
    public function index()
    {
        // Require authentication
        Auth::requireAuth();

        // Get all departments
        $departments = $this->departmentModel->getAll();

        Response::success('Departments retrieved successfully', $departments);
    }

    // Get department by ID (accessible to all authenticated users)
    public function show($id)
    {
        // Require authentication
        Auth::requireAuth();

        // Find department
        $department = $this->departmentModel->findById($id);

        if (!$department) {
            Response::notFound('Department not found');
        }

        Response::success('Department retrieved successfully', $department);
    }

    // Create new department (admin only)
    public function store()
    {
        // Require admin authentication
        Auth::requireAdmin();

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (!isset($data['name']) || empty(trim($data['name']))) {
            Response::error('Department name is required', 400);
        }

        $name = trim($data['name']);

        // Validate name length
        if (strlen($name) < 2) {
            Response::error('Department name must be at least 2 characters long', 400);
        }

        if (strlen($name) > 100) {
            Response::error('Department name cannot exceed 100 characters', 400);
        }

        // Check if department name already exists
        if ($this->departmentModel->nameExists($name)) {
            Response::error('Department name already exists', 409);
        }

        // Create department data
        $departmentData = [
            'name' => $name
        ];

        // Create department
        if ($this->departmentModel->create($departmentData)) {
            // Get the created department
            $department = $this->departmentModel->findByName($name);

            Response::created('Department created successfully', $department);
        } else {
            Response::error('Failed to create department', 500);
        }
    }

    // Update department (admin only)
    public function update($id)
    {
        // Require admin authentication
        Auth::requireAdmin();

        // Check if department exists
        $department = $this->departmentModel->findById($id);
        if (!$department) {
            Response::notFound('Department not found');
        }

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (!isset($data['name']) || empty(trim($data['name']))) {
            Response::error('Department name is required', 400);
        }

        $name = trim($data['name']);

        // Validate name length
        if (strlen($name) < 2) {
            Response::error('Department name must be at least 2 characters long', 400);
        }

        if (strlen($name) > 100) {
            Response::error('Department name cannot exceed 100 characters', 400);
        }

        // Check if department name already exists (exclude current department)
        if ($this->departmentModel->nameExists($name, $id)) {
            Response::error('Department name already exists', 409);
        }

        // Update department data
        $updateData = [
            'name' => $name
        ];

        // Update department
        if ($this->departmentModel->update($id, $updateData)) {
            // Get updated department data
            $updatedDepartment = $this->departmentModel->findById($id);

            Response::success('Department updated successfully', $updatedDepartment);
        } else {
            Response::error('Failed to update department', 500);
        }
    }

    // Delete department (admin only)
    public function destroy($id)
    {
        // Require admin authentication
        Auth::requireAdmin();

        // Check if department exists
        $department = $this->departmentModel->findById($id);
        if (!$department) {
            Response::notFound('Department not found');
        }

        // Check if department has any tickets
        // For safety, we'll get departments with ticket count
        $departmentsWithCount = $this->departmentModel->getWithTicketCount();
        $targetDepartment = null;

        foreach ($departmentsWithCount as $dept) {
            if ($dept['id'] == $id) {
                $targetDepartment = $dept;
                break;
            }
        }

        if ($targetDepartment && $targetDepartment['ticket_count'] > 0) {
            Response::error('Cannot delete department with existing tickets. Please reassign or resolve all tickets first.', 400);
        }

        // Delete department
        if ($this->departmentModel->delete($id)) {
            Response::success('Department deleted successfully');
        } else {
            Response::error('Failed to delete department', 500);
        }
    }

    // Get departments with ticket counts (admin only)
    public function getWithTicketCount()
    {
        // Require admin authentication
        Auth::requireAdmin();

        // Get departments with ticket count
        $departments = $this->departmentModel->getWithTicketCount();

        Response::success('Departments with ticket counts retrieved successfully', $departments);
    }
}
