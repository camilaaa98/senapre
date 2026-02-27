<?php
/**
 * Aprendices API - Complete CRUD with Pagination and Filters
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Listar aprendices con paginación y filtros
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $ficha = isset($_GET['ficha']) ? $_GET['ficha'] : '';
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $poblacion = isset($_GET['poblacion']) ? $_GET['poblacion'] : '';
        $tabla_poblacion = isset($_GET['tabla_poblacion']) ? $_GET['tabla_poblacion'] : '';
        
        // Construir query con filtros
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            // Búsqueda mejorada: incluye concatenación de nombre y apellido
            $where[] = "(a.nombre LIKE :search OR a.apellido LIKE :search OR a.documento LIKE :search OR a.correo LIKE :search OR (a.nombre || ' ' || a.apellido) LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($ficha)) {
            $where[] = "a.numero_ficha = :ficha";
            $params[':ficha'] = $ficha;
        }
        
        if (!empty($estado)) {
            $where[] = "a.estado = :estado";
            $params[':estado'] = $estado;
        } elseif (empty($tabla_poblacion)) {
            // Por defecto, excluir estados inactivos SOLO si no estamos viendo una población específica
            $where[] = "a.estado NOT IN ('RETIRADO', 'CANCELADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO')";
        }

        if (!empty($tabla_poblacion)) {
            // Validar que la tabla sea una de las permitidas para evitar inyecciones
            $tablasPermitidas = ['mujer', 'indígena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
            if (in_array(strtolower($tabla_poblacion), $tablasPermitidas)) {
                $alias = strtolower($tabla_poblacion === 'indígena' ? 'i' : (
                          $tabla_poblacion === 'mujer' ? 'm' : (
                          $tabla_poblacion === 'narp' ? 'n' : (
                          $tabla_poblacion === 'campesino' ? 'c' : (
                          $tabla_poblacion === 'lgbtiq' ? 'l' : 'd')))));
                $where[] = "$alias.documento IS NOT NULL";
            }
        }

        if (!empty($poblacion)) {
            // Filtro por población (maneja multi-selección almacenada como texto)
            $where[] = "a.tipo_poblacion LIKE :poblacion";
            $params[':poblacion'] = "%$poblacion%";
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Obtener JOINs base para filtros que los requieran
        $joinsFiltros = "LEFT JOIN Mujer m ON a.documento = m.documento
                        LEFT JOIN indígena i ON a.documento = i.documento
                        LEFT JOIN narp n ON a.documento = n.documento
                        LEFT JOIN campesino c ON a.documento = c.documento
                        LEFT JOIN lgbtiq l ON a.documento = l.documento
                        LEFT JOIN discapacidad d ON a.documento = d.documento";

        // Contar total (incluyendo JOINs si hay filtros de población)
        $countSql = "SELECT COUNT(*) as total FROM aprendices a $joinsFiltros $whereSql";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Verificar si existe la tabla de biometría una sola vez
        $tieneTablaBio = false;
        try {
            $checkTable = $conn->query("SELECT 1 FROM biometria_aprendices LIMIT 1");
            $tieneTablaBio = true;
        } catch (Exception $e) {}

        // Obtener datos (paginados o todos) con JOIN a las 6 tablas de población, fichas y programas
        $select = "a.*, 
                  p.nivel_formacion as nivel,
                  CASE WHEN m.documento IS NOT NULL THEN 1 ELSE 0 END as mujer,
                  CASE WHEN i.documento IS NOT NULL THEN 1 ELSE 0 END as indigena,
                  CASE WHEN n.documento IS NOT NULL THEN 1 ELSE 0 END as narp,
                  CASE WHEN c.documento IS NOT NULL THEN 1 ELSE 0 END as campesino,
                  CASE WHEN l.documento IS NOT NULL THEN 1 ELSE 0 END as lgbtiq,
                  CASE WHEN d.documento IS NOT NULL THEN 1 ELSE 0 END as discapacidad,
                  f.tipoFormacion,
                  f.nombre_programa";
        
        $joins = "LEFT JOIN Mujer m ON a.documento = m.documento
                  LEFT JOIN indígena i ON a.documento = i.documento
                  LEFT JOIN narp n ON a.documento = n.documento
                  LEFT JOIN campesino c ON a.documento = c.documento
                  LEFT JOIN lgbtiq l ON a.documento = l.documento
                  LEFT JOIN discapacidad d ON a.documento = d.documento
                  LEFT JOIN fichas f ON a.numero_ficha = f.numero_ficha
                  LEFT JOIN programas_formacion p ON f.nombre_programa = p.nombre_programa";

        // Verificar si existe la tabla de biometría antes de intentar el JOIN
        try {
            $checkTable = $conn->query("SELECT 1 FROM biometria_aprendices LIMIT 1");
            // If query succeeds, the table exists
            $joins .= " LEFT JOIN (SELECT DISTINCT documento FROM biometria_aprendices) bio ON a.documento = bio.documento";
            $select .= ", CASE WHEN bio.documento IS NOT NULL THEN 1 ELSE 0 END as tiene_biometria";
        } catch (Exception $e) {
            // La tabla no existe o no es accesible, continuar sin biometría
            $select .= ", 0 as tiene_biometria";
        }

        if ($limit === -1) {
            $sql = "SELECT $select FROM aprendices a $joins $whereSql ORDER BY a.apellido, a.nombre";
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
        } else {
            $sql = "SELECT $select FROM aprendices a $joins $whereSql ORDER BY a.apellido, a.nombre LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        $aprendices = $stmt->fetchAll();

        
        echo json_encode([
            'success' => true,
            'data' => $aprendices,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ($limit === -1) ? 1 : ceil($total / $limit)
            ]
        ]);
        exit;
    }
    
    // POST - Crear aprendiz
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO aprendices (tipo_identificacion, documento, nombre, apellido, correo, celular, numero_ficha, estado, tipo_poblacion, id_instructor_lider) 
                VALUES (:tipo, :doc, :nombre, :apellido, :correo, :celular, :ficha, :estado, :poblacion, :lider)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':tipo' => $data['tipo_identificacion'],
            ':doc' => $data['documento'],
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':correo' => $data['correo'],
            ':celular' => $data['celular'] ?? null,
            ':ficha' => $data['numero_ficha'],
            ':estado' => $data['estado'] ?? 'EN FORMACION',
            ':poblacion' => $data['tipo_poblacion'] ?? '',
            ':lider' => $data['id_instructor_lider'] ?? null
        ]);

        // ... (resto del código de sincronización poblacional omitido por brevedad pero debe mantenerse)
        // (Nota: El asistente asume que el resto del bloque se mantiene igual)


        // Sincronizar con las 6 tablas de población
        // Mapa: ValorCheckbox => NombreTablaBD
        $mapPoblacion = [
            'Mujer' => 'Mujer', 
            'Indígena' => 'indígena', 
            'NARP' => 'narp', 
            'Campesino' => 'campesino', 
            'LGBTIQ+' => 'lgbtiq', 
            'Discapacidad' => 'discapacidad'
        ];
        
        $poblacionesSeleccionadas = isset($data['tipo_poblacion']) ? explode(',', $data['tipo_poblacion']) : [];
        $poblacionesSeleccionadas = array_map('trim', $poblacionesSeleccionadas);

        foreach ($mapPoblacion as $valorCheckbox => $tableName) {
            if (in_array($valorCheckbox, $poblacionesSeleccionadas)) {
                $conn->prepare("INSERT OR IGNORE INTO `$tableName` (documento) VALUES (:doc)")
                     ->execute([':doc' => $data['documento']]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Aprendiz creado exitosamente',
            'id' => $conn->lastInsertId()
        ]);
        exit;
    }
    
    // PUT - Actualizar aprendiz
    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $documento = isset($_GET['documento']) ? $_GET['documento'] : ($data['documento'] ?? null);
        
        if (empty($documento)) {
            throw new Exception('Documento requerido');
        }
        
        // Actualización parcial de estado
        if (count($data) === 1 && isset($data['estado'])) {
            $sql = "UPDATE aprendices SET estado = :estado WHERE documento = :doc";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':estado' => $data['estado'], ':doc' => $documento]);
            
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            exit;
        }
        
        // Actualización completa
        $sql = "UPDATE aprendices SET 
                tipo_identificacion = :tipo,
                nombre = :nombre,
                apellido = :apellido,
                correo = :correo,
                celular = :celular,
                numero_ficha = :ficha,
                estado = :estado,
                tipo_poblacion = :poblacion,
                id_instructor_lider = :lider
                WHERE documento = :doc";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':tipo' => $data['tipo_identificacion'],
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':correo' => $data['correo'],
            ':celular' => $data['celular'] ?? null,
            ':ficha' => $data['numero_ficha'],
            ':estado' => $data['estado'],
            ':poblacion' => $data['tipo_poblacion'] ?? '',
            ':lider' => $data['id_instructor_lider'] ?? null,
            ':doc' => $documento
        ]);

        // Sincronizar con las 6 tablas de población
        // Mapa: ValorCheckbox => NombreTablaBD
        $mapPoblacion = [
            'Mujer' => 'Mujer', 
            'Indígena' => 'indígena', 
            'NARP' => 'narp', 
            'Campesino' => 'campesino', 
            'LGBTIQ+' => 'lgbtiq', 
            'Discapacidad' => 'discapacidad'
        ];

        $poblacionesSeleccionadas = isset($data['tipo_poblacion']) ? explode(',', $data['tipo_poblacion']) : [];
        $poblacionesSeleccionadas = array_map('trim', $poblacionesSeleccionadas);

        foreach ($mapPoblacion as $valorCheckbox => $tableName) {
            // Eliminar relación previa
            $conn->prepare("DELETE FROM `$tableName` WHERE documento = :doc")->execute([':doc' => $documento]);
            
            // Insertar si está presente en el string enviado
            if (in_array($valorCheckbox, $poblacionesSeleccionadas)) {
                $conn->prepare("INSERT INTO `$tableName` (documento) VALUES (:doc)")->execute([':doc' => $documento]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Aprendiz actualizado']);
        exit;
    }
    
    // DELETE - Eliminar aprendiz o quitar de población
    if ($method === 'DELETE') {
        $documento = isset($_GET['documento']) ? $_GET['documento'] : '';
        $poblacion = isset($_GET['poblacion']) ? $_GET['poblacion'] : '';
        
        if (empty($documento)) {
            throw new Exception('Documento requerido');
        }

        if (!empty($poblacion)) {
            // Solo quitar de la tabla de población específica
            $validTables = [
                'mujer' => 'Mujer', 
                'indigena' => 'indígena', 
                'narp' => 'narp', 
                'campesino' => 'campesino', 
                'lgbtiq' => 'lgbtiq', 
                'discapacidad' => 'discapacidad'
            ];
            $tableName = $validTables[strtolower($poblacion)] ?? null;

            if (!$tableName) {
                throw new Exception('Categoría de población no válida');
            }

            $sql = "DELETE FROM `$tableName` WHERE documento = :doc";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':doc' => $documento]);

            echo json_encode([
                'success' => true,
                'message' => 'Aprendiz quitado de la categoría ' . $tableName
            ]);
            exit;
        }
        
        // Eliminación total del sistema
        $sql = "DELETE FROM aprendices WHERE documento = :doc";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':doc' => $documento]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Aprendiz eliminado exitosamente'
        ]);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
