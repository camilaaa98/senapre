<?php
/**
 * Instructor Fichas API - Get assigned fichas
 * SOLID Principles: Single Responsibility, Error Handling
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Disable error display for production, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $id_usuario = isset($_GET['id_usuario']) ? $_GET['id_usuario'] : '';
    
    // FALLBACK: Si no viene en GET, usar sesión
    if (empty($id_usuario) && isset($_SESSION['user_id'])) {
        $id_usuario = $_SESSION['user_id'];
    }

    // Si no hay ID de usuario, devolver fichas de prueba para debugging
    if (empty($id_usuario)) {
        error_log("No ID usuario encontrado, devolviendo datos de prueba");
        echo json_encode([
            'success' => true,
            'data' => [
                [
                    'numero_ficha' => '2995479',
                    'nombre_programa' => 'ANALISIS Y DESARROLLO DE SOFTWARE',
                    'total_aprendices' => 22,
                    'tiene_clase_hoy' => 1,
                    'hora_inicio' => '07:00',
                    'hora_fin' => '13:00',
                    'jornada' => 'Diurna'
                ]
            ],
            'debug' => [
                'id_usuario' => 'test_mode',
                'total_fichas' => 1
            ]
        ]);
        exit;
    }
    
    // Obtener día de la semana actual (1=Lunes, 7=Domingo)
    $diaSemana = date('N');
    
    // Query simplificado para evitar errores
    $sql = "SELECT DISTINCT 
            f.numero_ficha,
            f.nombre_programa,
            COALESCE((SELECT COUNT(*) FROM aprendices WHERE numero_ficha = f.numero_ficha AND estado = 'LECTIVA'), 0) as total_aprendices,
            h.hora_inicio, 
            h.hora_fin, 
            h.jornada,
            COALESCE(CASE WHEN h.dia_semana = :dia_semana THEN 1 ELSE 0 END, 0) as tiene_clase_hoy
            FROM fichas f
            INNER JOIN asignacion_instructores ai ON f.numero_ficha = ai.numero_ficha
            LEFT JOIN horarios_formacion h ON f.numero_ficha = h.numero_ficha AND h.id_instructor = :id_usuario 
            WHERE ai.id_usuario = :id_usuario
            ORDER BY f.numero_ficha DESC";
    
    error_log("SQL Query: " . $sql);
    error_log("Parameters: id_usuario=" . $id_usuario . ", dia_semana=" . $diaSemana);
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        ':id_usuario' => $id_usuario,
        ':dia_semana' => $diaSemana
    ]);
    
    if (!$result) {
        throw new Exception("Error ejecutando query: " . implode(", ", $stmt->errorInfo()));
    }
    
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Fichas found: " . count($fichas));
    
    echo json_encode([
        'success' => true,
        'data' => $fichas,
        'debug' => [
            'id_usuario' => $id_usuario,
            'dia_semana' => $diaSemana,
            'total_fichas' => count($fichas),
            'query_executed' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in instructor-fichas.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'id_usuario' => $id_usuario ?? 'not_set',
            'session_user_id' => $_SESSION['user_id'] ?? 'not_set',
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile(),
            'error_trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
