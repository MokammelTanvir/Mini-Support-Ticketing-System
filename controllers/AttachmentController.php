<?php
// AttachmentController - File Upload Management for Tickets

class AttachmentController
{
    private $attachmentModel;
    private $ticketModel;
    private $uploadPath;
    private $maxFileSize;
    private $allowedTypes;

    public function __construct()
    {
        $this->attachmentModel = new TicketAttachment();
        $this->ticketModel = new Ticket();
        $this->uploadPath = __DIR__ . '/../uploads/';
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip-compressed'
        ];

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    // Upload file(s) to a ticket
    public function upload($ticketId)
    {
        try {
            // Require authentication
            Auth::requireAuth();

            $current_user_id = Auth::getUserId();
            $current_user_role = Auth::getUserRole();

            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                Response::notFound('Ticket not found');
                return;
            }

            // Role-based access control
            if ($current_user_role === 'user' && $ticket['user_id'] != $current_user_id) {
                Response::forbidden('You can only upload files to your own tickets');
                return;
            }

            // Rate limiting - 5 uploads per hour per user
            RateLimit::checkLimit($current_user_id, 'file_upload', 5, 3600);

            // Check if files were uploaded
            if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
                Response::error('No files uploaded', 400);
                return;
            }

            $uploadedFiles = [];
            $errors = [];

            // Handle multiple files
            $fileCount = count($_FILES['files']['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i]
                ];

                $result = $this->processFile($file, $ticketId, $current_user_id);

                if ($result['success']) {
                    $uploadedFiles[] = $result['attachment'];
                } else {
                    $errors[] = $result['error'];
                }
            }

            if (!empty($uploadedFiles)) {
                $response = [
                    'uploaded_files' => $uploadedFiles,
                    'upload_count' => count($uploadedFiles)
                ];

                if (!empty($errors)) {
                    $response['errors'] = $errors;
                }

                Response::success('Files uploaded successfully', $response);
            } else {
                Response::error('No files were uploaded successfully', 400, ['errors' => $errors]);
            }
        } catch (Exception $e) {
            Response::error('Failed to upload files: ' . $e->getMessage(), 500);
        }
    }

    // Process individual file upload
    private function processFile($file, $ticketId, $userId)
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error: ' . $this->getUploadError($file['error'])];
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'File "' . $file['name'] . '" exceeds maximum size of 10MB'];
        }

        // Validate file type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'error' => 'File type "' . $mimeType . '" is not allowed'];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storedName = uniqid('ticket_' . $ticketId . '_') . '.' . $extension;
        $filePath = $this->uploadPath . $storedName;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Failed to save file "' . $file['name'] . '"'];
        }

        // Save to database
        $attachmentData = [
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'file_type' => $mimeType,
            'file_size' => $file['size'],
            'file_path' => $filePath
        ];

        $attachmentId = $this->attachmentModel->create($attachmentData);

        if ($attachmentId) {
            $attachment = $this->attachmentModel->findById($attachmentId);
            return ['success' => true, 'attachment' => $attachment];
        } else {
            // Clean up file if database insert failed
            unlink($filePath);
            return ['success' => false, 'error' => 'Failed to save file info for "' . $file['name'] . '"'];
        }
    }

    // Get all attachments for a ticket
    public function index($ticketId)
    {
        try {
            // Require authentication
            Auth::requireAuth();

            $current_user_id = Auth::getUserId();
            $current_user_role = Auth::getUserRole();

            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                Response::notFound('Ticket not found');
                return;
            }

            // Role-based access control
            if ($current_user_role === 'user' && $ticket['user_id'] != $current_user_id) {
                Response::forbidden('You can only view attachments for your own tickets');
                return;
            }

            // Get attachments
            $attachments = $this->attachmentModel->getByTicket($ticketId);

            Response::success('Ticket attachments retrieved successfully', $attachments);
        } catch (Exception $e) {
            Response::error('Failed to retrieve attachments: ' . $e->getMessage(), 500);
        }
    }

    // Download/view attachment
    public function download($ticketId, $attachmentId)
    {
        try {
            // Require authentication
            Auth::requireAuth();

            $current_user_id = Auth::getUserId();
            $current_user_role = Auth::getUserRole();

            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                Response::notFound('Ticket not found');
                return;
            }

            // Role-based access control
            if ($current_user_role === 'user' && $ticket['user_id'] != $current_user_id) {
                Response::forbidden('You can only download attachments from your own tickets');
                return;
            }

            // Find attachment
            $attachment = $this->attachmentModel->findById($attachmentId);
            if (!$attachment || $attachment['ticket_id'] != $ticketId) {
                Response::notFound('Attachment not found');
                return;
            }

            // Check if file exists
            if (!file_exists($attachment['file_path'])) {
                Response::error('File not found on server', 404);
                return;
            }

            // Set headers for file download
            header('Content-Type: ' . $attachment['file_type']);
            header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
            header('Content-Length: ' . $attachment['file_size']);
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

            // Output file content
            readfile($attachment['file_path']);
            exit;
        } catch (Exception $e) {
            Response::error('Failed to download file: ' . $e->getMessage(), 500);
        }
    }

    // Delete attachment
    public function destroy($ticketId, $attachmentId)
    {
        try {
            // Require authentication
            Auth::requireAuth();

            $current_user_id = Auth::getUserId();
            $current_user_role = Auth::getUserRole();

            // Check if ticket exists
            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                Response::notFound('Ticket not found');
                return;
            }

            // Find attachment
            $attachment = $this->attachmentModel->findById($attachmentId);
            if (!$attachment || $attachment['ticket_id'] != $ticketId) {
                Response::notFound('Attachment not found');
                return;
            }

            // Access control - only file uploader, ticket owner, or admin can delete
            $canDelete = ($current_user_role === 'admin') ||
                ($attachment['user_id'] == $current_user_id) ||
                ($ticket['user_id'] == $current_user_id);

            if (!$canDelete) {
                Response::forbidden('You can only delete your own attachments');
                return;
            }

            // Delete file from filesystem
            if (file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }

            // Delete from database
            if ($this->attachmentModel->delete($attachmentId)) {
                Response::success('Attachment deleted successfully');
            } else {
                Response::error('Failed to delete attachment from database', 500);
            }
        } catch (Exception $e) {
            Response::error('Failed to delete attachment: ' . $e->getMessage(), 500);
        }
    }

    // Get storage statistics (admin only)
    public function getStorageStats()
    {
        try {
            // Require admin authentication
            Auth::requireAdmin();

            $stats = $this->attachmentModel->getStorageStats();

            // Format file sizes
            $stats['total_size_formatted'] = $this->formatBytes($stats['total_size']);
            $stats['avg_size_formatted'] = $this->formatBytes($stats['avg_size']);

            Response::success('Storage statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            Response::error('Failed to retrieve storage statistics: ' . $e->getMessage(), 500);
        }
    }

    // Helper method to get upload error message
    private function getUploadError($error)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return isset($errors[$error]) ? $errors[$error] : 'Unknown upload error';
    }

    // Helper method to format file sizes
    private function formatBytes($size, $precision = 2)
    {
        if ($size == 0) return '0 B';

        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
