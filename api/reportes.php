<?php
er('Content-Type: application/json');
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
    $ficha = $_GET['ficha'] ?? '';
    $tabla_poblacion = $_GET['tabla_poblacion'] ?? '';
    
    $where = "";
    $whereA = ""; // Alias 'a' para aprendices
    $params = [];
    
    if (!empty($ficha)) {
        $where = " WHERE numero_ficha = :ficha";
        $whereA = " WHERE a.numero_ficha = :ficha";
        $params[':ficha'] = trim($ficha);
    } elseif (!empty($tabla_poblacion)) {
        $tablasPermitidas = ['mujer', 'indigena', 'indígena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
        if (in_array(strtolower($tabla_poblacion), $tablasPermitidas)) {
            $tableName = strtolower($tabla_poblacion === 'indígena' ? 'indígena' : $tabla_poblacion);
            $where = " WHERE documento IN (SELECT documento FROM `$tableName`)";
            $whereA = " WHERE a.documento IN (SELECT documento FROM `$tableName`)";
        }
    }

    // 1. Totales Simples con Filtrado de Ámbito
    $totalAprendices = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM aprendices $where");
    $stmt->execute($params);
    $totalAprendices = $stmt->fetch()['total'];

    $totalFichas = 0;
    if (!empty($ficha)) {
        $totalFichas = 1; // Solo su ficha
    } else {
        $totalFichas = safeQuery($conn, "SELECT COUNT(*) as total FROM fichas");
    }

    // Para voceros, usuarios e instructores se limitan a los relevantes de la ficha si es posible, 
    // pero por ahora mantendremos coherencia con el total de aprendices.
    $totalInstructores = 0;
    $totalUsuarios = 0;
    $totalProgramas = 0;

    if (!empty($ficha)) {
        // Si hay ficha, solo contamos lo relacionado a esa ficha
        $totalUsuarios = $totalAprendices; // Solo sus aprendices son "usuarios" visibles
        $totalInstructores = safeQuery($conn, "SELECT COUNT(DISTINCT id_usuario) as total FROM instructores"); // Instructores son generales o por ficha si tuviéramos tabla relación
        $totalProgramas = 1; // El programa de su ficha
    } else {
        $totalInstructores = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'instructor'");
        $totalUsuarios = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios");
        $totalProgramas = safeQuery($conn, "SELECT COUNT(*) as total FROM programas_formacion");
    }
    
    // 4. Aprendices por Estado
    $aprendicesPorEstado = [];
    $stmt = $conn->prepare("SELECT estado, COUNT(*) as cantidad FROM aprendices $where GROUP BY estado");
    $stmt->execute($params);
    $aprendicesPorEstado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Fichas por Programa (Ocultar si es vocero de una sola ficha)
    $fichasPorPrograma = [];
    if (empty($ficha)) {
        try {
            $stmt = $conn->query("SELECT nombre_programa, COUNT(*) as cantidad FROM fichas GROUP BY nombre_programa ORDER BY cantidad DESC LIMIT 8");
            if ($stmt) $fichasPorPrograma = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
    
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
