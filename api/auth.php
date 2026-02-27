<?php
/**
 * Auth API Endpoint
 * Routes authentication requests to AuthController
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/controllers/AuthController.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new AuthController($db);

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        echo $auth->login($data);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint not found']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Internal Server Error: ' . $e->getMessage()]);
}
?>
