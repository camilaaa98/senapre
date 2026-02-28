<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $documento = $data['documento'] ?? '';
        $poblacion = strtolower($data['poblacion'] ?? '');

        if (empty($documento) || empty($poblacion)) {
            echo json_encode(['success' => false, 'message' => 'Documento y población requeridos']);
            exit;
        }

        // Mapeo de tablas de población
        $tablas = [
            'mujer' => 'Mujer',
            'indigena' => 'indígena',
            'indígena' => 'indígena',
            'narp' => 'narp',
            'campesino' => 'campesino',
            'lgbtiq' => 'lgbtiq',
            'lgbtiq+' => 'lgbtiq',
            'discapacidad' => 'discapacidad'
        ];

        if (!isset($tablas[$poblacion])) {
            echo json_encode(['success' => false, 'message' => 'Categoría de población no válida']);
            exit;
        }

        $tabla = $tablas[$poblacion];

        // Verificar si el aprendiz existe
        $stmt = $conn->prepare("SELECT documento FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $documento]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Aprendiz no encontrado']);
            exit;
        }

        // Verificar si ya está en esa población
        $stmt = $conn->prepare("SELECT documento FROM $tabla WHERE documento = :doc");
        $stmt->execute([':doc' => $documento]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'El aprendiz ya pertenece a esta categoría']);
            exit;
        }

        // Insertar
        $stmt = $conn->prepare("INSERT INTO $tabla (documento) VALUES (:doc)");
        $stmt->execute([':doc' => $documento]);

        echo json_encode(['success' => true, 'message' => 'Aprendiz anexado correctamente']);
    } 
    elseif ($method === 'DELETE') {
        $documento = $_GET['documento'] ?? '';
        $poblacion = strtolower($_GET['poblacion'] ?? '');

        $tablas = [
            'mujer' => 'Mujer', 'indigena' => 'indígena', 'indígena' => 'indígena',
            'narp' => 'narp', 'campesino' => 'campesino', 'lgbtiq' => 'lgbtiq',
            'discapacidad' => 'discapacidad'
        ];

        if (!isset($tablas[$poblacion])) {
            echo json_encode(['success' => false, 'message' => 'Categoría no válida']);
            exit;
        }

        $tabla = $tablas[$poblacion];
        $stmt = $conn->prepare("DELETE FROM $tabla WHERE documento = :doc");
        $stmt->execute([':doc' => $documento]);

        echo json_encode(['success' => true, 'message' => 'Removido de la categoría']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
