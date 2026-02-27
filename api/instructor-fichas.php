<?php
/**
 * Instructor Fichas API - Get assigned fichas
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
// Silenciar cualquier warning que corrompa el JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // require_once __DIR__ . '/session_start.php'; // Removed: File does not exist
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $id_usuario = isset($_GET['id_usuario']) ? $_GET['id_usuario'] : '';
    
    // FALLBACK: Si no viene en GET, usar sesión
    if (empty($id_usuario) && isset($_SESSION['user_id'])) {
        $id_usuario = $_SESSION['user_id'];
    }

    if (empty($id_usuario)) {
        throw new Exception('ID de usuario requerido (No recibido en GET ni en SESSION)');
    }
    
    // Obtener día de la semana actual (1=Lunes, 7=Domingo)
    $diaSemana = date('N');
    
    // Obtener TODAS las fichas asignadas al instructor (sin filtrar por día)
    // Se incluye el horario si existe para HOY, pero se muestran todas las fichas
    $sql = "SELECT DISTINCT f.*, 
            (SELECT COUNT(*) FROM aprendices WHERE numero_ficha = f.numero_ficha) as total_aprendices,
            h.hora_inicio, h.hora_fin, h.jornada,
            COALESCE(
                CASE WHEN h.dia_semana = :dia_semana THEN 1 ELSE 0 END,
                0
            ) as tiene_clase_hoy
            FROM fichas f
            INNER JOIN asignacion_instructores ai ON f.numero_ficha = ai.numero_ficha
            LEFT JOIN horarios_formacion h ON f.numero_ficha = h.numero_ficha 
                AND h.id_instructor = :id_usuario 
            WHERE ai.id_usuario = :id_usuario
            ORDER BY f.numero_ficha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_usuario' => $id_usuario,
        ':dia_semana' => $diaSemana
    ]);
    $fichas = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $fichas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
