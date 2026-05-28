<?php
namespace App\Controllers;
class BaseController {
    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    protected function getRequestData() {
        return json_decode(file_get_contents('php://input'), true);
    }
}
