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
    $database = new Database();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Consultar asistencias
    if ($method === 'GET') {
        // NUEVO: Endpoint para obtener aprendices pendientes (sin asistencia del día)
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        if ($action === 'aprendices_pendientes') {
            $ficha = isset($_GET['ficha']) ? $_GET['ficha'] : '';
            $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
            $idUsuario = isset($_GET['id_usuario']) ? $_GET['id_usuario'] : '';
            
            if (empty($ficha)) {
                throw new Exception('Número de ficha requerido');
            }
            
            // Obtener aprendices de la ficha que NO tienen asistencia registrada ese día
            $sql = "SELECT a.documento, a.nombre, a.apellido, a.correo, a.celular, a.estado,
                           b.id_biometria,
                           CASE WHEN b.id_biometria IS NOT NULL THEN 1 ELSE 0 END as tiene_biometria
                    FROM aprendices a
                    LEFT JOIN biometria_aprendices b ON a.documento = b.documento
                    WHERE a.numero_ficha = :ficha
                    AND a.estado = 'EN FORMACION'
                    AND a.documento NOT IN (
                        SELECT documento
                        FROM asistencias
                        WHERE numero_ficha = :ficha
                        AND DATE(fecha) = :fecha
                        AND id_usuario = :id_usuario
                    )
                    ORDER BY a.apellido, a.nombre";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':ficha' => $ficha,
                ':fecha' => $fecha,
                ':id_usuario' => $idUsuario
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
        
        // Consulta normal de asistencias (código existente)
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
            $where[] = "DATE(a.fecha) = :fecha";
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
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $select = "a.*, ap.nombre, ap.apellido, f.nombre_programa";
        $joins = "LEFT JOIN aprendices ap ON a.documento_aprendiz = ap.documento
                  LEFT JOIN fichas f ON a.numero_ficha = f.numero_ficha";

        if ($limit === -1) {
            $sql = "SELECT $select FROM asistencias a $joins WHERE $whereClause ORDER BY a.fecha DESC, a.creado_en DESC, ap.apellido, ap.nombre";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "SELECT $select FROM asistencias a $joins WHERE $whereClause ORDER BY a.fecha DESC, a.creado_en DESC, ap.apellido, ap.nombre LIMIT :limit OFFSET :offset";
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
        
        // Obtener user_id del primer registro (asumiendo mismo instructor para todos)
        $user = isset($data['id_usuario']) ? $data['id_usuario'] : null;
        
        // Iniciar transacción
        $conn->beginTransaction();
        
        try {
            // === NUEVA LÓGICA: UPSERT (Registro Único) ===
            // No eliminamos registros existentes. Solo insertamos/actualizamos según sea necesario.
            
            $ficha = $registros[0]['numero_ficha'];
            $fecha = $registros[0]['fecha'];
            
            // Obtener configuración de horario para cálculo de retardo
            $fechaActual = date('Y-m-d');
            $esHoy = ($fecha === $fechaActual);
            $horaInicio = null;
            $limiteRetardo = null;
            
            if ($esHoy) {
                $diaSemana = date('N'); // 1=Lunes, 7=Domingo
                
                $sqlHorario = "SELECT hora_inicio FROM horarios_formacion 
                               WHERE numero_ficha = :ficha AND dia_semana = :dia";
                $stmtHorario = $conn->prepare($sqlHorario);
                $stmtHorario->execute([':ficha' => $ficha, ':dia' => $diaSemana]);
                $horario = $stmtHorario->fetch(PDO::FETCH_ASSOC);
                
                if ($horario) {
                    $horaInicio = $horario['hora_inicio'];
                    // Tolerancia: MINUTOS_TOLERANCIA después de la hora de inicio
                    $limiteRetardo = date('H:i:s', strtotime("$horaInicio + " . MINUTOS_TOLERANCIA . " minutes"));
                }
            }
            
            // Preparar consulta para verificar existencia
            $checkSql = "SELECT id_asistencia, estado, creado_en 
                         FROM asistencias 
                         WHERE documento_aprendiz = :documento 
                         AND numero_ficha = :ficha 
                         AND DATE(fecha) = :fecha";
            $checkStmt = $conn->prepare($checkSql);
            
            // Preparar consulta de inserción
            $insertSql = "INSERT INTO asistencias 
                         (documento_aprendiz, numero_ficha, fecha, estado, observaciones, id_instructor, creado_en)
                         VALUES (:documento, :ficha, :fecha, :estado, :observaciones, :id_instructor, :creado_en)";
            $insertStmt = $conn->prepare($insertSql);
            
            // Preparar consulta de actualización (solo para observaciones)
            $updateSql = "UPDATE asistencias 
                         SET observaciones = :observaciones
                         WHERE id_asistencia = :id";
            $updateStmt = $conn->prepare($updateSql);
            
            $registrosNuevos = 0;
            $registrosActualizados = 0;
            $registrosIgnorados = 0;

            foreach ($registros as $reg) {
                // Verificar si ya existe registro para este aprendiz en esta fecha
                $checkStmt->execute([
                    ':documento' => $reg['documento_aprendiz'],
                    ':ficha' => $reg['numero_ficha'],
                    ':fecha' => $fecha
                ]);
                $existente = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existente) {
                    // YA EXISTE REGISTRO
                    $estadoActual = $existente['estado'];
                    $estadoNuevo = $reg['estado'];

                    // LÓGICA DE ACTUALIZACIÓN INTELIGENTE
                    // Si estaba Ausente y ahora marca CUALQUIER OTRA COSA (Presente, Retardo, etc.) -> ACTUALIZAR
                    if ($estadoActual === 'Ausente' && $estadoNuevo !== 'Ausente') {
                        $horaRegistro = date('H:i:s');
                        $estadoFinal = $estadoNuevo;
                        
                        // Si marca Presente, recalcular Retardo según hora
                        if ($estadoFinal === 'Presente' && $esHoy && $limiteRetardo && $horaRegistro > $limiteRetardo) {
                            $estadoFinal = 'Retardo';
                        }

                        $updateStatusSql = "UPDATE asistencias 
                                            SET estado = :estado, 
                                                observaciones = :observaciones,
                                                creado_en = :creado_en 
                                            WHERE id_asistencia = :id";
                        $updateStatusStmt = $conn->prepare($updateStatusSql);
                        $updateStatusStmt->execute([
                            ':estado' => $estadoFinal,
                            ':observaciones' => $reg['observaciones'],
                            ':creado_en' => date('Y-m-d H:i:s'), // Actualizar hora de llegada
                            ':id' => $existente['id_asistencia']
                        ]);
                        $registrosActualizados++;
                    } 
                    // Si ya estaba Presente/Retardo, VALIDAR si solo cambiamos observaciones
                    else {
                        if (!empty($reg['observaciones']) && $reg['observaciones'] !== $existente['observaciones']) {
                            $updateStmt->execute([
                                ':observaciones' => $reg['observaciones'],
                                ':id' => $existente['id_asistencia']
                            ]);
                            $registrosActualizados++;
                        } else {
                            $registrosIgnorados++;
                        }
                    }
                } else {
                    // NO EXISTE - Insertar nuevo registro
                    $horaRegistro = date('H:i:s');
                    $estadoFinal = $reg['estado'];
                    
                    // Calcular estado basado en hora de llegada INDIVIDUAL
                    if ($estadoFinal === 'Presente' && $esHoy && $limiteRetardo) {
                        // Comparar hora actual contra límite de retardo
                        if ($horaRegistro > $limiteRetardo) {
                            $estadoFinal = 'Retardo';
                        }
                    }
                    
                    $insertStmt->execute([
                        ':documento' => $reg['documento_aprendiz'],
                        ':ficha' => $reg['numero_ficha'],
                        ':fecha' => $fecha,
                        ':estado' => $estadoFinal,
                        ':observaciones' => $reg['observaciones'] ?? null,
                        ':id_instructor' => $user,
                        ':creado_en' => date('Y-m-d H:i:s') // Hora exacta de registro
                    ]);
                    $registrosNuevos++;
                }
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => count($registros) . ' registros guardados exitosamente'
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
        exit;
    }
    
    // PUT - Actualizar asistencia individual
    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "UPDATE asistencias 
                SET estado = :estado, observaciones = :observaciones
                WHERE id_asistencia = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':estado' => $data['estado'],
            ':observaciones' => $data['observaciones'] ?? null,
            ':id' => $data['id_asistencia']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Asistencia actualizada'
        ]);
        exit;
    }
    
    // DELETE - Eliminar registro de asistencia
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($id)) {
            throw new Exception('ID requerido');
        }
        
        $sql = "DELETE FROM asistencias WHERE id_asistencia = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Asistencia eliminada'
        ]);
        exit;
    }
    
} catch (Exception $e) {
    // Si la tabla no existe o tiene esquema incorrecto, intentar crearla/reconstruirla
    if (strpos($e->getMessage(), 'no such table: asistencias') !== false || 
        strpos($e->getMessage(), 'no such column: numero_ficha') !== false) {
        try {
            // Si el error es de columna faltante, necesitamos reconstruir la tabla
            if (strpos($e->getMessage(), 'no such column') !== false) {
                // Respaldar tabla existente
                $conn->exec("DROP TABLE IF EXISTS asistencias_backup");
                $conn->exec("ALTER TABLE asistencias RENAME TO asistencias_backup");
            }
            
            // Crear tabla con el esquema correcto
            $conn->exec("CREATE TABLE IF NOT EXISTS asistencias (
                id_asistencia INTEGER PRIMARY KEY AUTOINCREMENT,
                documento_aprendiz TEXT NOT NULL,
                numero_ficha TEXT NOT NULL,
                fecha DATE NOT NULL,
                estado TEXT NOT NULL DEFAULT 'Presente',
                observaciones TEXT,
                id_instructor INTEGER,
                creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Intentar migrar datos si había tabla de respaldo
            try {
                $conn->exec("INSERT INTO asistencias 
                    (id_asistencia, documento_aprendiz, numero_ficha, fecha, estado, observaciones, id_instructor, creado_en)
                    SELECT 
                        a.id_asistencia, 
                        a.documento_aprendiz,
                        COALESCE(f.numero_ficha, '0000000'),
                        a.fecha,
                        a.estado,
                        a.observaciones,
                        a.id_instructor,
                        a.creado_en
                    FROM asistencias_backup a
                    LEFT JOIN fichas f ON a.id_ficha = f.id_ficha");
            } catch (Exception $migrateError) {
                // Si falla la migración, continuar sin datos antiguos
            }
            
            echo json_encode([
                'success' => true,
                'data' => [],
                'message' => 'Tabla de asistencias actualizada. Por favor intente guardar nuevamente.'
            ]);
        } catch (Exception $createError) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error actualizando tabla: ' . $createError->getMessage()
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
