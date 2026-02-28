<?php
/**
 * Fichas API - Complete CRUD con Instructor Líder
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Listar Tipos de Formación y Estados
    if ($method === 'GET' && isset($_GET['action'])) {
        if ($_GET['action'] === 'listTypes') {
            $stmt = $conn->query("SELECT * FROM tipoFormacion ORDER BY id");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit;
        }
        if ($_GET['action'] === 'listStates') {
            // Asumiendo tabla estados ya creada
            $stmt = $conn->query("SELECT * FROM estados ORDER BY id");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit;
        }
    }

    // GET - Listar fichas con paginación y filtros
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $programa = isset($_GET['programa']) ? $_GET['programa'] : '';
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $jornada = isset($_GET['jornada']) ? $_GET['jornada'] : '';
        
        // Construir query con filtros
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "f.numero_ficha LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($programa)) {
            $where[] = "f.nombre_programa = :programa";
            $params[':programa'] = $programa;
        }
        
        if (!empty($estado)) {
            $where[] = "f.estado = :estado";
            $params[':estado'] = $estado;
        }
        
        if (!empty($jornada)) {
            $where[] = "f.jornada = :jornada";
            $params[':jornada'] = $jornada;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM fichas f $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Obtener datos paginados con JOIN para instructor líder
        // Obtener datos paginados con JOIN para instructor líder y voceros
        $sqlBase = "SELECT f.*, 
                       COALESCE(p.nombre_programa, f.nombre_programa) as nombre_programa,
                       u.nombre || ' ' || u.apellido as nombre_instructor,
                       vp.nombre || ' ' || vp.apellido as nombre_vocero_principal,
                       vp.documento as id_vocero_principal,
                       vs.nombre || ' ' || vs.apellido as nombre_vocero_suplente,
                       vs.documento as id_vocero_suplente
                FROM fichas f 
                LEFT JOIN programas_formacion p ON f.nombre_programa = p.nombre_programa 
                LEFT JOIN usuarios u ON f.instructor_lider = u.id_usuario
                LEFT JOIN aprendices vp ON f.vocero_principal = vp.documento
                LEFT JOIN aprendices vs ON f.vocero_suplente = vs.documento
                $whereClause
                ORDER BY f.numero_ficha DESC";

        if ($limit === -1) {
            $sql = $sqlBase;
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
        } else {
            $sql = $sqlBase . " LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        $fichas = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $fichas,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
        exit;
    }
    
    // POST - Crear ficha
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Verificar columnas y AUTO-MIGRACIÓN
        try {
    if (!getenv('DATABASE_URL')) { $checkColumn = $conn->query("PRAGMA table_info(fichas)"); }
            $columns = $checkColumn->fetchAll(PDO::FETCH_ASSOC);
            $existingCols = array_column($columns, 'name');

            if (!in_array('instructor_lider', $existingCols)) {
                $conn->exec("ALTER TABLE fichas ADD COLUMN instructor_lider TEXT");
            }
            if (!in_array('vocero_principal', $existingCols)) {
                $conn->exec("ALTER TABLE fichas ADD COLUMN vocero_principal TEXT");
            }
            if (!in_array('vocero_suplente', $existingCols)) {
                $conn->exec("ALTER TABLE fichas ADD COLUMN vocero_suplente TEXT");
            }
        } catch (Exception $e) {
            // Ignorar si ya existen o error menor
        }
        
        $sql = "INSERT INTO fichas (numero_ficha, nombre_programa, jornada, estado, instructor_lider, tipoFormacion) 
                VALUES (:numero, :programa, :jornada, :estado, :instructor, :tipo)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':numero' => $data['numero_ficha'],
            ':programa' => $data['nombre_programa'],
            ':jornada' => $data['jornada'] ?? 'Diurna',
            ':estado' => $data['estado'] ?? 'Activa',
            ':instructor' => $data['instructor_lider'] ?? null,
            ':tipo' => $data['tipoFormacion'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Ficha creada exitosamente'
        ]);
        exit;
    }
    
    // PUT - Actualizar ficha
    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['numero_ficha'])) {
            throw new Exception('Número de ficha requerido');
        }
        
        // Construcción dinámica de la consulta UPDATE
        $fields = [];
        $params = [':numero' => $data['numero_ficha']];

        if (isset($data['nombre_programa'])) {
            $fields[] = "nombre_programa = :programa";
            $params[':programa'] = $data['nombre_programa'];
        }
        if (isset($data['jornada'])) {
            $fields[] = "jornada = :jornada";
            $params[':jornada'] = $data['jornada'];
        }
        if (isset($data['estado'])) {
            $fields[] = "estado = :estado";
            $params[':estado'] = $data['estado'];
        }

        // Validación de Voceros Únicos
        $nuevoPrincipal = array_key_exists('vocero_principal', $data) ? $data['vocero_principal'] : null;
        $nuevoSuplente = array_key_exists('vocero_suplente', $data) ? $data['vocero_suplente'] : null;

        if ($nuevoPrincipal !== null || $nuevoSuplente !== null) {
            // Obtener valores actuales para comparar si solo se envía uno
            $stmtCurrent = $conn->prepare("SELECT vocero_principal, vocero_suplente FROM fichas WHERE numero_ficha = :numero");
            $stmtCurrent->execute([':numero' => $data['numero_ficha']]);
            $current = $stmtCurrent->fetch();

            $vP = array_key_exists('vocero_principal', $data) ? $nuevoPrincipal : $current['vocero_principal'];
            $vS = array_key_exists('vocero_suplente', $data) ? $nuevoSuplente : $current['vocero_suplente'];

            if (!empty($vP) && !empty($vS) && $vP === $vS) {
                throw new Exception('El vocero principal y el suplente no pueden ser la misma persona.');
            }
        }

        if (array_key_exists('instructor_lider', $data)) {
            $fields[] = "instructor_lider = :instructor";
            $params[':instructor'] = $data['instructor_lider'];
        }
        if (array_key_exists('vocero_principal', $data)) {
            $fields[] = "vocero_principal = :voc1";
            $params[':voc1'] = $data['vocero_principal'];
        }
        if (array_key_exists('vocero_suplente', $data)) {
            $fields[] = "vocero_suplente = :voc2";
            $params[':voc2'] = $data['vocero_suplente'];
        }
        if (array_key_exists('tipoFormacion', $data)) {
            $fields[] = "tipoFormacion = :tipo";
            $params[':tipo'] = $data['tipoFormacion'];
        }

        if (empty($fields)) {
            throw new Exception('No se enviaron campos para actualizar');
        }

        $sql = "UPDATE fichas SET " . implode(', ', $fields) . " WHERE numero_ficha = :numero";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Actualización en cascada a aprendices si cambió el estado
        if (isset($data['estado'])) {
            $sqlCascade = "UPDATE aprendices 
                           SET estado = :nuevo_estado 
                           WHERE numero_ficha = :numero 
                           AND estado NOT IN ('RETIRO', 'CANCELADO', 'FINALIZADO', 'TRASLADO', 'APLAZADO')";
            $stmtCascade = $conn->prepare($sqlCascade);
            $stmtCascade->execute([
                ':nuevo_estado' => $data['estado'], 
                ':numero' => $data['numero_ficha']
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Ficha actualizada exitosamente'
        ]);
        exit;
    }
    
    // DELETE - Eliminar ficha
    if ($method === 'DELETE') {
        // ... (Keep existing DELETE logic)
        $numero = isset($_GET['numero']) ? $_GET['numero'] : '';
        if (empty($numero)) throw new Exception('Número de ficha requerido');
        $conn->prepare("DELETE FROM fichas WHERE numero_ficha = :numero")->execute([':numero' => $numero]);
        echo json_encode(['success' => true, 'message' => 'Ficha eliminada exitosamente']);
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
