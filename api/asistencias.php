<?php
/**
 * Asistencias API - CRUD Completo
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de tolerancia para retardos (debe coincidir con el frontend)
define('MINUTOS_TOLERANCIA', 30);

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    $isPg = strpos($database->getDbPath(), 'PostgreSQL') !== false;
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Consultar asistencias
    if ($method === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        if ($action === 'aprendices_pendientes') {
            $ficha = isset($_GET['ficha']) ? $_GET['ficha'] : '';
            $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
            $idUsuario = isset($_GET['id_usuario']) ? $_GET['id_usuario'] : '';
            
            if (empty($ficha)) {
                throw new Exception('Número de ficha requerido');
            }
            
            // Obtener aprendices de la ficha que NO tienen asistencia registrada ese día
            $dateCast = $isPg ? "a.fecha::date" : "DATE(a.fecha)";
            $sql = "SELECT a.documento, a.nombre, a.apellido, a.correo, a.celular, a.estado,
                           b.id_biometria,
                           CASE WHEN b.id_biometria IS NOT NULL THEN 1 ELSE 0 END as tiene_biometria
                    FROM aprendices a
                    LEFT JOIN biometria_aprendices b ON a.documento = b.documento
                    WHERE a.numero_ficha = :ficha
                    AND a.estado = 'EN FORMACION'
                    AND a.documento NOT IN (
                        SELECT documento_aprendiz
                        FROM asistencias
                        WHERE numero_ficha = :ficha
                        AND " . ($isPg ? "fecha::date" : "DATE(fecha)") . " = :fecha
                    )
                    ORDER BY a.apellido, a.nombre";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':ficha' => $ficha,
                ':fecha' => $fecha
            ]);
            $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $aprendices,
                'total' => count($aprendices),
                'fecha' => $fecha
            ]);
            exit;
        }
        
        // Consulta normal de asistencias
        $ficha = isset($_GET['ficha']) ? $_GET['ficha'] : '';
        $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
        $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
        $documento = isset($_GET['documento']) ? $_GET['documento'] : '';
        
        $params = [];
        $where = ['1=1'];
        
        if (!empty($ficha)) {
            $where[] = "a.numero_ficha = :ficha";
            $params[':ficha'] = $ficha;
        }
        
        if (!empty($fecha)) {
            $where[] = ($isPg ? "a.fecha::date" : "DATE(a.fecha)") . " = :fecha";
            $params[':fecha'] = $fecha;
        }
        
        if (!empty($fechaInicio)) {
            $where[] = "a.fecha >= :fechaInicio";
            $params[':fechaInicio'] = $fechaInicio;
        }
        
        if (!empty($fechaFin)) {
            $where[] = "a.fecha <= :fechaFin";
            $params[':fechaFin'] = $fechaFin;
        }
        
        if (!empty($documento)) {
            $where[] = "a.documento_aprendiz = :documento";
            $params[':documento'] = $documento;
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $whereClause = implode(' AND ', $where);
        
        // Contar Total
        $countSql = "SELECT COUNT(*) as total FROM asistencias a WHERE $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $select = "a.*, ap.nombre, ap.apellido, f.nombre_programa";
        $joins = "LEFT JOIN aprendices ap ON a.documento_aprendiz = ap.documento
                  LEFT JOIN fichas f ON a.numero_ficha = f.numero_ficha";

        if ($limit === -1) {
            $sql = "SELECT $select FROM asistencias a $joins WHERE $whereClause ORDER BY a.fecha DESC, a.creado_en DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "SELECT $select FROM asistencias a $joins WHERE $whereClause ORDER BY a.fecha DESC, a.creado_en DESC LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $asistencias,
            'pagination' => [
                'total' => (int)$totalItems,
                'page' => $page,
                'pages' => ($limit > 0) ? ceil($totalItems / $limit) : 1,
                'limit' => $limit
            ]
        ]);
        exit;
    }
    
    // POST - Registrar asistencia masiva
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['registros']) || !is_array($data['registros'])) {
            throw new Exception('Se requiere un array de registros');
        }
        
        $registros = $data['registros'];
        if (count($registros) === 0) {
            throw new Exception('No hay registros para guardar');
        }
        
        $user = isset($data['id_usuario']) ? $data['id_usuario'] : null;
        $conn->beginTransaction();
        
        try {
            $ficha = $registros[0]['numero_ficha'];
            $fecha = $registros[0]['fecha'];
            
            $fechaActual = date('Y-m-d');
            $esHoy = ($fecha === $fechaActual);
            $limiteRetardo = null;
            
            if ($esHoy) {
                $diaSemana = date('N'); 
                $sqlHorario = "SELECT hora_inicio FROM horarios_formacion WHERE numero_ficha = :ficha AND dia_semana = :dia";
                $stmtHorario = $conn->prepare($sqlHorario);
                $stmtHorario->execute([':ficha' => $ficha, ':dia' => $diaSemana]);
                $horario = $stmtHorario->fetch(PDO::FETCH_ASSOC);
                
                if ($horario) {
                    $horaInicio = $horario['hora_inicio'];
                    $limiteRetardo = date('H:i:s', strtotime("$horaInicio + " . MINUTOS_TOLERANCIA . " minutes"));
                }
            }
            
            $checkSql = "SELECT id_asistencia, estado, observaciones FROM asistencias 
                         WHERE documento_aprendiz = :documento AND numero_ficha = :ficha 
                         AND " . ($isPg ? "fecha::date" : "DATE(fecha)") . " = :fecha";
            $checkStmt = $conn->prepare($checkSql);
            
            $insertSql = "INSERT INTO asistencias 
                         (documento_aprendiz, numero_ficha, fecha, estado, observaciones, id_instructor)
                         VALUES (:documento, :ficha, :fecha, :estado, :observaciones, :id_instructor)";
            $insertStmt = $conn->prepare($insertSql);
            
            foreach ($registros as $reg) {
                $checkStmt->execute([
                    ':documento' => $reg['documento_aprendiz'],
                    ':ficha' => $reg['numero_ficha'],
                    ':fecha' => $fecha
                ]);
                $existente = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existente) {
                    if ($existente['estado'] === 'Ausente' && $reg['estado'] !== 'Ausente') {
                        $estadoFinal = $reg['estado'];
                        if ($estadoFinal === 'Presente' && $esHoy && $limiteRetardo && date('H:i:s') > $limiteRetardo) {
                            $estadoFinal = 'Retardo';
                        }
                        $conn->prepare("UPDATE asistencias SET estado = :e, observaciones = :o WHERE id_asistencia = :id")
                             ->execute([':e' => $estadoFinal, ':o' => $reg['observaciones'], ':id' => $existente['id_asistencia']]);
                    }
                } else {
                    $estadoFinal = $reg['estado'];
                    if ($estadoFinal === 'Presente' && $esHoy && $limiteRetardo && date('H:i:s') > $limiteRetardo) {
                        $estadoFinal = 'Retardo';
                    }
                    $insertStmt->execute([
                        ':documento' => $reg['documento_aprendiz'],
                        ':ficha' => $reg['numero_ficha'],
                        ':fecha' => $fecha,
                        ':estado' => $estadoFinal,
                        ':observaciones' => $reg['observaciones'] ?? null,
                        ':id_instructor' => $user
                    ]);
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => count($registros) . ' registros procesados']);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        exit;
    }

    // PUT/DELETE (Keep standard logical implementation)
    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $conn->prepare("UPDATE asistencias SET estado = :e, observaciones = :o WHERE id_asistencia = :id")
             ->execute([':e' => $data['estado'], ':o' => $data['observaciones'], ':id' => $data['id_asistencia']]);
        echo json_encode(['success' => true, 'message' => 'Actualizado']);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        $conn->prepare("DELETE FROM asistencias WHERE id_asistencia = :id")->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Eliminado']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
