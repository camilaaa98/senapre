<?php
error_reporting(0);
@ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
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
        $custom_filter = isset($_GET['custom_filter']) ? $_GET['custom_filter'] : '';
        
        // Construir query con filtros
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            // Búsqueda mejorada: incluye concatenación de nombre y apellido
            $where[] = "(a.nombre LIKE :search OR a.apellido LIKE :search OR a.documento LIKE :search OR a.correo LIKE :search OR (a.nombre || ' ' || a.apellido) LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($ficha)) {
            // Limpiar ficha y asegurar comparación robusta
            $fichaBusqueda = trim($ficha);
            $where[] = "(a.numero_ficha = :ficha OR a.numero_ficha LIKE :ficha_like)";
            $params[':ficha'] = $fichaBusqueda;
            $params[':ficha_like'] = "%$fichaBusqueda%";
        }
        
        if (!empty($estado)) {
            $where[] = "a.estado = :estado";
            $params[':estado'] = $estado;
        } elseif (empty($tabla_poblacion) && empty($ficha) && empty($poblacion) && empty($search)) {
            // EXCLUIR estados de inactividad total SOLO si no hay filtros activos
            // Si el usuario busca algo específico, queremos que lo encuentre aunque sea cancelado.
            $where[] = "a.estado NOT IN ('RETIRADO', 'CANCELADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO', 'RETIRO', 'CANCELADA') AND a.estado IN ('LECTIVA', 'EN FORMACION')";
        }

        if (!empty($tabla_poblacion)) {
            // Validar que la tabla sea una de las permitidas para evitar inyecciones
            $tablasPermitidas = ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
            if (in_array(strtolower($tabla_poblacion), $tablasPermitidas)) {
                $alias = strtolower($tabla_poblacion === 'indigena' ? 'i' : (
                          $tabla_poblacion === 'mujer' ? 'm' : (
                          $tabla_poblacion === 'narp' ? 'n' : (
                          $tabla_poblacion === 'campesino' ? 'c' : (
                          $tabla_poblacion === 'lgbtiq' ? 'l' : 'd')))));
                $where[] = "$alias.documento IS NOT NULL";
            }
        }

        if (!empty($custom_filter)) {
            // Aplicar filtro personalizado directamente (para consultas LIKE complejas)
            $where[] = $custom_filter;
        }

        if (!empty($poblacion)) {
            // Filtro por población unificado y flexible (Insensible a mayúsculas/minúsculas)
            $pob_search = trim($poblacion);
            $pob_lower = strtolower($pob_search);
            
            // Mapeo exhaustivo de categorías a columnas booleanas y variaciones de texto
            $mapeoBool = [
                'mujer' => 'mujer',
                'indigen' => 'indigena', // Captura indigena e indígena
                'narp' => 'narp',
                'afro' => 'narp',
                'campesino' => 'campesino',
                'lgbt' => 'lgbtiq',
                'discapacidad' => 'discapacidad'
            ];
            
            $colBool = null;
            // Buscar si alguna de nuestras llaves está contenida en la búsqueda o viceversa
            foreach($mapeoBool as $key => $col) {
                if (strpos($pob_lower, $key) !== false || strpos($key, $pob_lower) !== false) {
                    $colBool = $col;
                    break;
                }
            }
            
            if ($colBool) {
                // Compatibilidad universal (SQLite/PostgreSQL)
                $where[] = "(UPPER(a.tipo_poblacion) LIKE UPPER(:pob_exact) OR UPPER(a.tipo_poblacion) LIKE UPPER(:pob_wild) OR a.$colBool = 1)";
            } else {
                $where[] = "(UPPER(a.tipo_poblacion) LIKE UPPER(:pob_exact) OR UPPER(a.tipo_poblacion) LIKE UPPER(:pob_wild))";
            }
            $params[':pob_exact'] = "$pob_search";
            $params[':pob_wild'] = "%$pob_search%";
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Obtener JOINs base para filtros que los requieran
        $joinsFiltros = "LEFT JOIN Mujer m ON a.documento = m.documento
                        LEFT JOIN indigena i ON a.documento = i.documento
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
                  LEFT JOIN indigena i ON a.documento = i.documento
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
            ':estado' => $data['estado'] ?? 'LECTIVA',
            ':poblacion' => $data['tipo_poblacion'] ?? '',
            ':lider' => $data['id_instructor_lider'] ?? null
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Aprendiz creado exitosamente. La vinculación a poblaciones se gestiona desde el módulo de Liderazgo.',
            'id' => $data['documento']
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
        
        // Actualización parcial de estado o datos
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
            ':tipo' => $data['tipo_identificacion'] ?? 'CC',
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':correo' => $data['correo'],
            ':celular' => $data['celular'] ?? null,
            ':ficha' => $data['numero_ficha'],
            ':estado' => $data['estado'] ?? 'LECTIVA',
            ':poblacion' => $data['tipo_poblacion'] ?? '',
            ':lider' => $data['id_instructor_lider'] ?? null,
            ':doc' => $documento
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Aprendiz actualizado exitosamente']);
        exit;
    }
    
    // DELETE - Eliminar aprendiz
    if ($method === 'DELETE') {
        $documento = isset($_GET['documento']) ? $_GET['documento'] : '';
        
        if (empty($documento)) {
            throw new Exception('Documento requerido');
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
