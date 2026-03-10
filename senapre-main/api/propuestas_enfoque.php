<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $tipo = $_GET['tipo'] ?? '';
        if (empty($tipo)) {
            throw new Exception('Tipo de población requerido');
        }
        
        $sql = "SELECT * FROM propuestas_enfoque WHERE tipo_poblacion = :tipo ORDER BY fecha_creacion DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':tipo' => $tipo]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['tipo_poblacion']) || !isset($data['propuesta'])) {
            throw new Exception('Datos incompletos');
        }
        
        $sql = "INSERT INTO propuestas_enfoque (tipo_poblacion, propuesta) VALUES (:tipo, :propuesta)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':tipo' => $data['tipo_poblacion'],
            ':propuesta' => $data['propuesta']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Propuesta añadida']);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            throw new Exception('ID requerido');
        }
        
        $sql = "DELETE FROM propuestas_enfoque WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        echo json_encode(['success' => true, 'message' => 'Propuesta eliminada']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
