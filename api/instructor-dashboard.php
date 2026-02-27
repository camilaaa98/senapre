<?php
/**
 * Instructor Dashboard API
 * Fetches metrics and calendar data for the logged-in instructor
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Optimización: Modo WAL para lectura sin bloqueos y timeout
    try {
        $conn->exec('PRAGMA journal_mode = WAL;');
        $conn->exec('PRAGMA busy_timeout = 5000;');
    } catch (Exception $e) {
        // Continuar si falla optimización
    }

    // Validar que se reciba el ID del instructor
    if (!isset($_GET['id_usuario'])) {
        throw new Exception('ID de usuario requerido');
    }

    $idUsuario = $_GET['id_usuario'];

    // 1. Total Fichas Asignadas (Activas)
    $stmtFichas = $conn->prepare("SELECT COUNT(DISTINCT numero_ficha) as total FROM asignacion_instructores WHERE id_usuario = :id");
    $stmtFichas->execute([':id' => $idUsuario]);
    $totalFichas = $stmtFichas->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Total Aprendices (En formación activa en las fichas asignadas)
    $sqlAprendices = "SELECT COUNT(DISTINCT a.documento) as total
                      FROM aprendices a
                      WHERE a.numero_ficha IN (
                          SELECT DISTINCT numero_ficha 
                          FROM asignacion_instructores 
                          WHERE id_usuario = :id
                      )
                      AND (a.estado = 'EN FORMACION' OR a.estado = 'En Formación')";
    $stmtAprendices = $conn->prepare($sqlAprendices);
    $stmtAprendices->execute([':id' => $idUsuario]);
    $totalAprendices = $stmtAprendices->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Promedio Asistencia (General del instructor)
    $asistenciaData = ['total_registros' => 0, 'total_presentes' => 0];
    try {
        // Verificar si la tabla tiene registros primero para no hacer cálculos en vacío
        $sqlAsistencia = "SELECT 
                            COUNT(*) as total_registros,
                            SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) as total_presentes
                          FROM asistencias
                          WHERE id_instructor = :id";
        $stmtAsistencia = $conn->prepare($sqlAsistencia);
        $stmtAsistencia->execute([':id' => $idUsuario]);
        $res = $stmtAsistencia->fetch(PDO::FETCH_ASSOC);
        if ($res) $asistenciaData = $res;
    } catch (Exception $e) {
        // Si falla (ej. columna id_instructor no existe), ignoramos silenciosamente para no romper todo el dashboard
    }

    $promedioAsistencia = 0;
    if ($asistenciaData['total_registros'] > 0) {
        $promedioAsistencia = round(($asistenciaData['total_presentes'] / $asistenciaData['total_registros']) * 100, 1);
    }

    // 4. Alertas (Aprendices con baja asistencia < 75%)
    $totalAlertas = 0;
    try {
        $sqlAlertas = "SELECT COUNT(*) as count FROM (
            SELECT documento_aprendiz,
                   COUNT(*) as total_registros,
                   SUM(CASE WHEN estado = 'Presente' THEN 1 ELSE 0 END) as total_presentes
            FROM asistencias
            WHERE numero_ficha IN (SELECT DISTINCT numero_ficha FROM asignacion_instructores WHERE id_usuario = :id)
            GROUP BY documento_aprendiz
            HAVING (CAST(total_presentes AS FLOAT) / CAST(total_registros AS FLOAT)) < 0.75
            AND total_registros >= 5
        )";
        $stmtAlertas = $conn->prepare($sqlAlertas);
        $stmtAlertas->execute([':id' => $idUsuario]);
        $resAlertas = $stmtAlertas->fetch(PDO::FETCH_ASSOC);
        $totalAlertas = $resAlertas ? $resAlertas['count'] : 0;
    } catch (Exception $e) {
        // Ignorar error en alertas si asistencias falla
    }

    // 5. Datos para el Calendario
    $calendarEvents = [];
    try {
        $sqlCalendario = "SELECT a.numero_ficha,
                                 a.dias_formacion as fecha,
                                 a.hora_inicio,
                                 a.hora_fin,
                                 f.nombre_programa
                          FROM asignacion_instructores a
                          LEFT JOIN fichas f ON a.numero_ficha = f.numero_ficha
                          WHERE a.id_usuario = :id
                          AND a.dias_formacion IS NOT NULL
                          ORDER BY a.dias_formacion, a.hora_inicio
                          LIMIT 100";
        $stmtCalendario = $conn->prepare($sqlCalendario);
        $stmtCalendario->execute([':id' => $idUsuario]);
        $asignaciones = $stmtCalendario->fetchAll(PDO::FETCH_ASSOC);

        foreach ($asignaciones as $asig) {
            $fechaStr = $asig['fecha'];
            if (strtotime($fechaStr)) {
                $calendarEvents[] = [
                    'title' => $asig['numero_ficha'] . ' - ' . ($asig['nombre_programa'] ?? 'Clase'),
                    'start' => $fechaStr . 'T' . ($asig['hora_inicio'] ?: '08:00'),
                    'end' => $fechaStr . 'T' . ($asig['hora_fin'] ?: '12:00'),
                    'description' => 'Formación Programada',
                    'color' => '#39A900'
                ];
            }
        }
    } catch (Exception $e) {
        // Si falla calendario, enviamos array vacío
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'metrics' => [
                'fichas' => (int)$totalFichas,
                'aprendices' => (int)$totalAprendices,
                'asistencia' => $promedioAsistencia . '%',
                'alertas' => (int)$totalAlertas
            ],
            'calendar' => $calendarEvents
        ]
    ]);

} catch (Exception $e) {
    // Siempre devolver JSON válido incluso en error global
    http_response_code(200); 
    echo json_encode([
        'success' => false,
        'message' => 'Error del sistema: ' . $e->getMessage()
    ]);
}
?>
