<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $sql = "SELECT v.tipo_poblacion, v.documento, a.nombre, a.apellido, a.numero_ficha
                FROM voceros_enfoque v
                LEFT JOIN aprendices a ON v.documento = a.documento";
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    if ($method === 'POST' || $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['tipo_poblacion']) || !isset($data['documento'])) {
            throw new Exception('Datos incompletos');
        }

        $documento = $data['documento'] ?: null;

        if ($documento) {
            // VALIDACIÓN: No puede ser representante de jornada
            $stmtCheck = $conn->prepare("SELECT jornada FROM representantes_jornada WHERE documento = :doc");
            $stmtCheck->execute([':doc' => $documento]);
            $resCheck = $stmtCheck->fetch();
            if ($resCheck) {
                throw new Exception("El aprendiz ya es representante de la jornada " . $resCheck['jornada'] . ". No puede ser vocero de enfoque.");
            }
        }
        
        $sql = "UPDATE voceros_enfoque SET documento = :doc WHERE tipo_poblacion = :tipo";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':doc' => $documento,
            ':tipo' => $data['tipo_poblacion']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Vocero actualizado']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
