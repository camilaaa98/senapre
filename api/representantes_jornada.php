<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $sql = "SELECT r.jornada, r.documento, a.nombre, a.apellido, a.numero_ficha
                FROM representantes_jornada r
                LEFT JOIN aprendices a ON r.documento = a.documento";
        $stmt = $conn->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    if ($method === 'POST' || $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['jornada']) || !isset($data['documento'])) {
            throw new Exception('Datos incompletos');
        }

        $documento = $data['documento'] ?: null;

        if ($documento) {
            // VALIDACIÓN: No puede ser vocero de enfoque diferencial
            $stmtCheck = $conn->prepare("SELECT tipo_poblacion FROM voceros_enfoque WHERE documento = :doc");
            $stmtCheck->execute([':doc' => $documento]);
            $resCheck = $stmtCheck->fetch();
            if ($resCheck) {
                throw new Exception("El aprendiz ya es vocero del enfoque diferencial " . $resCheck['tipo_poblacion'] . ". No puede ser representante de jornada.");
            }
        }
        
        $sql = "UPDATE representantes_jornada SET documento = :doc WHERE jornada = :jor";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':doc' => $documento,
            ':jor' => $data['jornada']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Representante actualizado']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
