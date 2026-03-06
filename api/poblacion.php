<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $categoria = strtolower($_GET['categoria'] ?? '');
        $tablas = [
            'mujer' => 'Mujer', 'indigena' => 'indigena', 'narp' => 'narp',
            'campesino' => 'campesino', 'lgbtiq' => 'lgbtiq', 'discapacidad' => 'discapacidad'
        ];

        if (!isset($tablas[$categoria])) {
            echo json_encode(['success' => false, 'message' => 'Categoría no válida']);
            exit;
        }

        $tabla = $tablas[$categoria];
        $sql = "SELECT a.* FROM aprendices a 
                JOIN \"$tabla\" p ON a.documento = p.documento 
                ORDER BY a.apellido, a.nombre";
        
        $stmt = $conn->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $documento = $data['documento'] ?? '';
        $poblacion = strtolower($data['poblacion'] ?? '');

        if (empty($documento) || empty($poblacion)) {
            echo json_encode(['success' => false, 'message' => 'Documento y población requeridos']);
            exit;
        }

        $tablas = [
            'mujer' => 'Mujer', 'indigena' => 'indigena', 'narp' => 'narp',
            'campesino' => 'campesino', 'lgbtiq' => 'lgbtiq', 'discapacidad' => 'discapacidad'
        ];

        if (!isset($tablas[$poblacion])) {
            echo json_encode(['success' => false, 'message' => 'Categoría no válida']);
            exit;
        }

        $tabla = $tablas[$poblacion];
        $isPg = strpos($db->getDbPath(), 'PostgreSQL') !== false;

        // Insertar con soporte para ambos motores
        $sql = $isPg ? 
            "INSERT INTO \"$tabla\" (documento) VALUES (:doc) ON CONFLICT DO NOTHING" : 
            "INSERT OR IGNORE INTO `$tabla` (documento) VALUES (:doc)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':doc' => $documento]);

        echo json_encode(['success' => true, 'message' => 'Aprendiz vinculado correctamente']);
    } 
    elseif ($method === 'DELETE') {
        $documento = $_GET['documento'] ?? '';
        $poblacion = strtolower($_GET['poblacion'] ?? '');

        $tablas = [
            'mujer' => 'Mujer', 'indigena' => 'indigena', 'narp' => 'narp',
            'campesino' => 'campesino', 'lgbtiq' => 'lgbtiq', 'discapacidad' => 'discapacidad'
        ];

        if (!isset($tablas[$poblacion])) {
            echo json_encode(['success' => false, 'message' => 'Categoría no válida']);
            exit;
        }

        $tabla = $tablas[$poblacion];
        $stmt = $conn->prepare("DELETE FROM \"$tabla\" WHERE documento = :doc");
        $stmt->execute([':doc' => $documento]);

        echo json_encode(['success' => true, 'message' => 'Vínculo eliminado']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
