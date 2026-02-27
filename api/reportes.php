<?php
/**
 * Reportes API - Versión Robusta
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/config/Database.php';

function safeQuery($conn, $sql, $default = 0) {
    try {
        $stmt = $conn->query($sql);
        if ($stmt) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['total'] : $default;
        }
    } catch (Exception $e) {
        // Ignorar error de tabla faltante y devolver default
    }
    return $default;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // 1. Totales Simples (Safe Mode)
    $totalAprendices = safeQuery($conn, "SELECT COUNT(*) as total FROM aprendices");
    $totalFichas = safeQuery($conn, "SELECT COUNT(*) as total FROM fichas");
    $totalInstructores = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'instructor'");
    $totalUsuarios = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios");
    $totalProgramas = safeQuery($conn, "SELECT COUNT(*) as total FROM programas_formacion");
    
    // 4. Aprendices por Estado
    $aprendicesPorEstado = [];
    try {
        $stmt = $conn->query("SELECT estado, COUNT(*) as cantidad FROM aprendices GROUP BY estado");
        if ($stmt) $aprendicesPorEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    
    // 5. Fichas por Programa
    $fichasPorPrograma = [];
    try {
        $stmt = $conn->query("SELECT nombre_programa, COUNT(*) as cantidad FROM fichas GROUP BY nombre_programa ORDER BY cantidad DESC LIMIT 8");
        if ($stmt) $fichasPorPrograma = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    
    // 6. Asistencias Recientes
    $asistenciasRecientes = [];
    try {
        // Verificar si existe la tabla primero evitando error fatal
        $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='asistencias'");
        if ($stmt->fetch()) {
             $stmt = $conn->query("SELECT fecha, COUNT(*) as cantidad FROM asistencias WHERE fecha >= date('now', '-7 days') GROUP BY fecha ORDER BY fecha");
             if ($stmt) $asistenciasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}
    
    echo json_encode([
        'success' => true,
        'data' => [
            'resumen' => [
                'aprendices' => $totalAprendices,
                'fichas' => $totalFichas,
                'instructores' => $totalInstructores,
                'usuarios' => $totalUsuarios,
                'programas' => $totalProgramas
            ],
            'aprendices_estado' => $aprendicesPorEstado,
            'fichas_programa' => $fichasPorPrograma,
            'asistencias_trend' => $asistenciasRecientes
        ]
    ]);
    
} catch (Exception $e) {
    // Si falla la conexión misma
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage(),
        'data' => [
            'resumen' => ['aprendices'=>0, 'fichas'=>0, 'instructores'=>0, 'usuarios'=>0, 'programas'=>0],
            'aprendices_estado' => [],
            'fichas_programa' => [],
            'asistencias_trend' => []
        ]
    ]);
}
?>
