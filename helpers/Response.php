<?php
// Response Helper Class

class Response
{

    public static function json($data, $status_code = 200)
    {
        http_response_code($status_code);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }

    public static function success($message = 'Success', $data = null, $status_code = 200)
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response, $status_code);
    }

    public static function error($message = 'Error occurred', $status_code = 400, $errors = null)
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        self::json($response, $status_code);
    }

    public static function validation($errors, $message = 'Validation failed')
    {
        self::error($message, 422, $errors);
    }

    public static function unauthorized($message = 'Unauthorized access')
    {
        self::error($message, 401);
    }

    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, 403);
    }

    public static function notFound($message = 'Resource not found')
    {
        self::error($message, 404);
    }

    public static function created($message = 'Created successfully', $data = null)
    {
        self::success($message, $data, 201);
    }
}
