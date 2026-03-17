<?php
error_reporting(0);
@ini_set('display_errors', 0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/config/Database.php';
$conn = Database::getInstance()->getConnection();

function getEstadoColor($estado) {
    $est = strtoupper(trim($estado));
    if ($est === 'LECTIVA' || $est === 'EN FORMACION' || $est === 'PRODUCTIVA') return '#39A900'; // Verde SENA
    if ($est === 'CANCELADO' || $est === 'RETIRO VOLUNTARIO' || $est === 'RETIRO') return '#dc2626'; // Rojo
    if ($est === 'APLAZADO' || $est === 'TRASLADO') return '#f59e0b'; // Naranja
    return '#64748b'; // Gris
}

function safeQuery($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res['total'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
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
        $tablasPermitidas = ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
        if (in_array(strtolower($tabla_poblacion), $tablasPermitidas)) {
            $tableName = strtolower($tabla_poblacion);
            $where = " WHERE documento IN (SELECT documento FROM $tableName)";
            $whereA = " WHERE a.documento IN (SELECT documento FROM $tableName)";
        }
    }

    // 1. Totales Simples con Filtrado de Ámbito
    $totalAprendices = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM aprendices $where");
    $stmt->execute($params);
    $totalAprendices = $stmt->fetch()['total'];

    // 1.1 Desglose de Aprendices por Estado (para el resumen)
    $stmt = $conn->prepare("SELECT estado, COUNT(*) as cantidad FROM aprendices $where GROUP BY estado");
    $stmt->execute($params);
    $aprendicesDetalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalFichas = 0;
    if (!empty($ficha)) {
        $totalFichas = 1;
    } else {
        $totalFichas = safeQuery($conn, "SELECT COUNT(*) as total FROM fichas");
    }

    // 2. Usuarios y Estados
    $usuariosActivos = 0;
    $usuariosInactivos = 0;
    $totalInstructores = 0;
    $totalUsuarios = 0;

    if (empty($ficha)) {
        $usuariosActivos = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'");
        $usuariosInactivos = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios WHERE estado = 'inactivo'");
        $totalInstructores = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'instructor'");
        $totalUsuarios = safeQuery($conn, "SELECT COUNT(*) as total FROM usuarios");
    } else {
        // En contexto de ficha, simplificamos o usamos datos de aprendices
        $usuariosActivos = $totalAprendices; 
        $totalUsuarios = $totalAprendices;
    }

    // 3. Voceros
    $vocerosPrincipales = 0;
    $vocerosSuplentes = 0;
    $vocerosEnfoque = 0;

    try {
        // Voceros de fichas (principales y suplentes)
        $stmtV = $conn->query("SELECT 
            SUM(CASE WHEN vocero_principal IS NOT NULL AND vocero_principal != '' THEN 1 ELSE 0 END) as principales,
            SUM(CASE WHEN vocero_suplente IS NOT NULL AND vocero_suplente != '' THEN 1 ELSE 0 END) as suplentes
            FROM fichas");
        $rowV = $stmtV->fetch(PDO::FETCH_ASSOC);
        $vocerosPrincipales = $rowV['principales'] ?? 0;
        $vocerosSuplentes = $rowV['suplentes'] ?? 0;

        // Voceros de enfoque diferencial (tabla aparte)
        $vocerosEnfoque = safeQuery($conn, "SELECT COUNT(*) as total FROM voceros_enfoque");
    } catch (Exception $e) {}

    $totalProgramas = safeQuery($conn, "SELECT COUNT(*) as total FROM programas_formacion");
    
    // 4. Aprendices por Estado (ya definido arriba)
    
    // 5. Fichas por Programa (Top 8)
    $fichasPorPrograma = [];
    try {
        $stmtF = $conn->query("SELECT nombre_programa, COUNT(*) as cantidad FROM fichas GROUP BY nombre_programa ORDER BY cantidad DESC LIMIT 8");
        $fichasPorPrograma = $stmtF->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    // 6. Tendencia de Asistencias (Últimos 7 días)
    $asistenciasRecientes = [];
    try {
        $stmtA = $conn->query("SELECT fecha, COUNT(*) as cantidad FROM asistencias GROUP BY fecha ORDER BY fecha DESC LIMIT 7");
        $asistenciasRecientes = array_reverse($stmtA->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e) {}

    echo json_encode([
        'success' => true,
        'data' => [
            'resumen' => array_merge([
                'aprendices' => $totalAprendices,
                'aprendices_detalle' => $aprendicesDetalle,
                'fichas' => $totalFichas,
                'instructores' => $totalInstructores,
                'usuarios' => $totalUsuarios,
                'usuarios_activos' => $usuariosActivos,
                'usuarios_inactivos' => $usuariosInactivos,
                'voceros_principales' => $vocerosPrincipales,
                'voceros_suplentes' => $vocerosSuplentes,
                'voceros_enfoque' => $vocerosEnfoque,
                'programas' => $totalProgramas,
                'total_vulnerables' => $totalVulnerables
            ], $detalleVulnerables),
            'aprendices_estado' => $aprendicesDetalle,
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
