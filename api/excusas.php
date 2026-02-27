<?php
/**
 * API de Excusas
 * Gestiona excusas de asistencia con validación temporal (3 días)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // ==================== VERIFICAR SI PUEDE SUBIR EXCUSA ====================
    if ($action === 'puede_subir' && $method === 'GET') {
        $documento = isset($_GET['documento']) ? $_GET['documento'] : '';
        $fechaFalta = isset($_GET['fecha_falta']) ? $_GET['fecha_falta'] : '';
        
        if (empty($documento) || empty($fechaFalta)) {
            throw new Exception('Parámetros incompletos');
        }
        
        // Calcular días transcurridos
        $fechaFaltaObj = new DateTime($fechaFalta);
        $fechaActual = new DateTime();
        $diferencia = $fechaActual->diff($fechaFaltaObj);
        $diasTranscurridos = $diferencia->days;
        
        // Validar que la fecha de falta no sea futura
        if ($fechaFaltaObj > $fechaActual) {
            echo json_encode([
                'success' => false,
                'puede_subir' => false,
                'mensaje' => 'La fecha de falta no puede ser futura',
                'dias_transcurridos' => 0
            ]);
            exit;
        }
        
        // Validar ventana de 3 días
        $puedeSubir = $diasTranscurridos <= 3;
        
        echo json_encode([
            'success' => true,
            'puede_subir' => $puedeSubir,
            'dias_transcurridos' => $diasTranscurridos,
            'dias_restantes' => max(0, 3 - $diasTranscurridos),
            'mensaje' => $puedeSubir ? 
                "Puede subir excusa ({$diasTranscurridos} día(s) transcurrido(s))" : 
                "Plazo vencido (han pasado {$diasTranscurridos} días)"
        ]);
        exit;
    }
    
    // ==================== SUBIR EXCUSA ====================
    if ($action === 'subir' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['documento']) || empty($data['numero_ficha']) || 
            empty($data['fecha_falta']) || empty($data['motivo']) || empty($data['tipo_excusa'])) {
            throw new Exception('Parámetros incompletos');
        }
        
        $documento = $data['documento'];
        $numeroFicha = $data['numero_ficha'];
        $fechaFalta = $data['fecha_falta'];
        $motivo = $data['motivo'];
        $tipoExcusa = $data['tipo_excusa']; // INASISTENCIA o LLEGADA_TARDE
        $archivoAdjunto = isset($data['archivo_adjunto']) ? $data['archivo_adjunto'] : null;
        
        // Validar tipo de excusa
        if (!in_array($tipoExcusa, ['INASISTENCIA', 'LLEGADA_TARDE'])) {
            throw new Exception('Tipo de excusa inválido. Debe ser INASISTENCIA o LLEGADA_TARDE');
        }
        
        // Validar ventana de 3 días
        $fechaFaltaObj = new DateTime($fechaFalta);
        $fechaActual = new DateTime();
        $diferencia = $fechaActual->diff($fechaFaltaObj);
        $diasTranscurridos = $diferencia->days;
        
        if ($diasTranscurridos > 3) {
            throw new Exception("Plazo vencido. Han pasado {$diasTranscurridos} días (máximo: 3 días)");
        }
        
        // VERIFICAR SI YA EXISTE UNA EXCUSA DEL MISMO TIPO PARA ESTE APRENDIZ, FICHA Y FECHA
        $sqlCheck = "SELECT id_excusa, estado FROM excusas_asistencia 
                     WHERE documento = :documento 
                     AND numero_ficha = :ficha 
                     AND fecha_falta = :fecha
                     AND tipo_excusa = :tipo";
        
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([
            ':documento' => $documento,
            ':ficha' => $numeroFicha,
            ':fecha' => $fechaFalta,
            ':tipo' => $tipoExcusa
        ]);
        $excusaExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($excusaExistente) {
            // Ya existe una excusa
            $estadoActual = $excusaExistente['estado'];
            
            if ($estadoActual === 'APROBADA') {
                throw new Exception('Ya existe una excusa APROBADA para esta fecha. No se puede modificar.');
            }
            
            if ($estadoActual === 'RECHAZADA') {
                throw new Exception('Ya existe una excusa RECHAZADA para esta fecha. Contacte al administrador si necesita apelar.');
            }
            
            // Si está PENDIENTE, actualizamos
            // Guardar archivo si existe
            $rutaArchivo = null;
            if ($archivoAdjunto) {
                $directorioExcusas = __DIR__ . '/../uploads/excusas';
                if (!file_exists($directorioExcusas)) {
                    mkdir($directorioExcusas, 0777, true);
                }
                
                $extension = 'pdf';
                if (strpos($archivoAdjunto, 'data:image/jpeg') === 0) $extension = 'jpg';
                if (strpos($archivoAdjunto, 'data:image/png') === 0) $extension = 'png';
                
                $nombreArchivo = $documento . '_' . $fechaFalta . '_' . time() . '.' . $extension;
                $rutaCompleta = $directorioExcusas . '/' . $nombreArchivo;
                
                $archivoData = explode(',', $archivoAdjunto)[1];
                file_put_contents($rutaCompleta, base64_decode($archivoData));
                
                $rutaArchivo = 'uploads/excusas/' . $nombreArchivo;
            }
            
            // Actualizar excusa existente
            $sqlUpdate = "UPDATE excusas_asistencia SET 
                          motivo = :motivo,
                          archivo_adjunto = COALESCE(:archivo, archivo_adjunto),
                          fecha_registro = CURRENT_TIMESTAMP
                          WHERE id_excusa = :id";
            
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':motivo' => $motivo,
                ':archivo' => $rutaArchivo,
                ':id' => $excusaExistente['id_excusa']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Excusa actualizada exitosamente. Será evaluada por un administrador.'
            ]);
            exit;
        }
        
        // Si no existe, insertar nueva excusa
        // Guardar archivo si existe
        $rutaArchivo = null;
        if ($archivoAdjunto) {
            // Crear directorio si no existe
            $directorioExcusas = __DIR__ . '/../uploads/excusas';
            if (!file_exists($directorioExcusas)) {
                mkdir($directorioExcusas, 0777, true);
            }
            
            // Generar nombre único
            $extension = 'pdf'; // Por defecto PDF
            if (strpos($archivoAdjunto, 'data:image/jpeg') === 0) $extension = 'jpg';
            if (strpos($archivoAdjunto, 'data:image/png') === 0) $extension = 'png';
            
            $nombreArchivo = $documento . '_' . $fechaFalta . '_' . time() . '.' . $extension;
            $rutaCompleta = $directorioExcusas . '/' . $nombreArchivo;
            
            // Decodificar y guardar
            $archivoData = explode(',', $archivoAdjunto)[1];
            file_put_contents($rutaCompleta, base64_decode($archivoData));
            
            $rutaArchivo = 'uploads/excusas/' . $nombreArchivo;
        }
        
        // Insertar excusa
        $sql = "INSERT INTO excusas_asistencia 
                (documento, numero_ficha, fecha_falta, motivo, tipo_excusa, archivo_adjunto) 
                VALUES (:documento, :ficha, :fecha, :motivo, :tipo, :archivo)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':documento' => $documento,
            ':ficha' => $numeroFicha,
            ':fecha' => $fechaFalta,
            ':motivo' => $motivo,
            ':tipo' => $tipoExcusa,
            ':archivo' => $rutaArchivo
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Excusa subida exitosamente. Será evaluada por un administrador.'
        ]);
        exit;
    }
    
    // ==================== LISTAR EXCUSAS ====================
    if ($action === 'listar' && $method === 'GET') {
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $documento = isset($_GET['documento']) ? $_GET['documento'] : '';
        $estado = isset($_GET['estado']) ? $_GET['estado'] : 'PENDIENTE';
        $documento = isset($_GET['documento']) ? $_GET['documento'] : '';
        $ficha = isset($_GET['ficha']) ? $_GET['ficha'] : '';
        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
        $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $params = [':estado' => $estado];
        $where = ["e.estado = :estado"];
        
        if (!empty($documento)) {
            $where[] = "e.documento = :documento";
            $params[':documento'] = $documento;
        }
        if (!empty($ficha)) {
            $where[] = "e.numero_ficha = :ficha";
            $params[':ficha'] = $ficha;
        }
        if (!empty($fechaInicio)) {
            $where[] = "e.fecha_falta >= :fecha_inicio";
            $params[':fecha_inicio'] = $fechaInicio;
        }
        if (!empty($fechaFin)) {
            $where[] = "e.fecha_falta <= :fecha_fin";
            $params[':fecha_fin'] = $fechaFin;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar Total
        $countSql = "SELECT COUNT(*) as total FROM excusas_asistencia e WHERE $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $select = "e.*, a.nombre as nombre_aprendiz, a.apellido, a.correo,
                   u.nombre || ' ' || u.apellido as nombre_evaluador"; // Re-added evaluador
        $joins = "LEFT JOIN aprendices a ON e.documento = a.documento
                  LEFT JOIN usuarios u ON e.evaluado_por = u.id_usuario"; // Re-added evaluador join

        if ($limit === -1) {
            $sql = "SELECT $select FROM excusas_asistencia e $joins WHERE $whereClause ORDER BY e.fecha_registro DESC"; // Changed to fecha_registro
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "SELECT $select FROM excusas_asistencia e $joins WHERE $whereClause ORDER BY e.fecha_registro DESC LIMIT :limit OFFSET :offset"; // Changed to fecha_registro
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $excusas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $excusas,
            'pagination' => [
                'total' => (int)$totalItems,
                'page' => $page,
                'pages' => ($limit > 0) ? ceil($totalItems / $limit) : 1,
                'limit' => $limit
            ]
        ]);
        exit;
    }
    
    // ==================== EVALUAR EXCUSA ====================
    if ($action === 'evaluar' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id_excusa']) || empty($data['estado']) || 
            empty($data['observaciones']) || empty($data['evaluado_por'])) {
            throw new Exception('Parámetros incompletos');
        }
        
        $idExcusa = $data['id_excusa'];
        $estado = $data['estado']; // APROBADA o RECHAZADA
        $observaciones = $data['observaciones'];
        $evaluadoPor = $data['evaluado_por'];
        
        if (!in_array($estado, ['APROBADA', 'RECHAZADA'])) {
            throw new Exception('Estado inválido. Debe ser APROBADA o RECHAZADA');
        }
        
        $sql = "UPDATE excusas_asistencia SET 
                estado = :estado,
                evaluado_por = :evaluador,
                fecha_evaluacion = CURRENT_TIMESTAMP,
                observaciones_admin = :observaciones
                WHERE id_excusa = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':estado' => $estado,
            ':evaluador' => $evaluadoPor,
            ':observaciones' => $observaciones,
            ':id' => $idExcusa
        ]);
        
        // ==================== ACTUALIZAR ESTADO DE ASISTENCIA ====================
        // Obtener información de la excusa para actualizar la asistencia
        $sqlExcusa = "SELECT documento, numero_ficha, fecha_falta, tipo_excusa FROM excusas_asistencia WHERE id_excusa = :id";
        $stmtExcusa = $conn->prepare($sqlExcusa);
        $stmtExcusa->execute([':id' => $idExcusa]);
        $excusa = $stmtExcusa->fetch(PDO::FETCH_ASSOC);
        
        if ($excusa) {
            if ($estado === 'APROBADA') {
                // Si se aprueba la excusa, cambiar estado de asistencia a "Excusa"
                $sqlUpdateAsistencia = "UPDATE asistencias SET 
                                        estado = 'Excusa'
                                        WHERE documento_aprendiz = :documento 
                                        AND numero_ficha = :ficha 
                                        AND fecha = :fecha";
                
                $stmtUpdateAsistencia = $conn->prepare($sqlUpdateAsistencia);
                $stmtUpdateAsistencia->execute([
                    ':documento' => $excusa['documento'],
                    ':ficha' => $excusa['numero_ficha'],
                    ':fecha' => $excusa['fecha_falta']
                ]);
            } else if ($estado === 'RECHAZADA') {
                // Si se rechaza la excusa, mantener el estado original
                // Para INASISTENCIA: mantener "Ausente"
                // Para LLEGADA_TARDE: mantener "Retardo"
                $estadoOriginal = ($excusa['tipo_excusa'] === 'INASISTENCIA') ? 'Ausente' : 'Retardo';
                
                $sqlUpdateAsistencia = "UPDATE asistencias SET 
                                        estado = :estado_original
                                        WHERE documento_aprendiz = :documento 
                                        AND numero_ficha = :ficha 
                                        AND fecha = :fecha";
                
                $stmtUpdateAsistencia = $conn->prepare($sqlUpdateAsistencia);
                $stmtUpdateAsistencia->execute([
                    ':estado_original' => $estadoOriginal,
                    ':documento' => $excusa['documento'],
                    ':ficha' => $excusa['numero_ficha'],
                    ':fecha' => $excusa['fecha_falta']
                ]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Excusa {$estado} exitosamente"
        ]);
        exit;
    }
    
    // ==================== OBTENER ESTADÍSTICAS ====================
    if ($action === 'estadisticas' && $method === 'GET') {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'APROBADA' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN estado = 'RECHAZADA' THEN 1 ELSE 0 END) as rechazadas
                FROM excusas_asistencia";
        
        $stmt = $conn->query($sql);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        exit;
    }
    
    // Acción no reconocida
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Acción no reconocida'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
