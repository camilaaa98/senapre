<?php
/**
 * Programas API - Complete CRUD
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
    
    // GET - Listar programas con paginación y filtros
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $nivel = isset($_GET['nivel']) ? $_GET['nivel'] : '';
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        
        // Construir query con filtros
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "nombre_programa LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($nivel)) {
            $where[] = "nivel_formacion = :nivel";
            $params[':nivel'] = $nivel;
        }
        
        if (!empty($estado)) {
            $where[] = "estado = :estado";
            $params[':estado'] = $estado;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM programas_formacion $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Obtener datos paginados o todos
        if ($limit === -1) {
            $sql = "SELECT * FROM programas_formacion 
                    $whereClause
                    ORDER BY nombre_programa";
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
        } else {
            $sql = "SELECT * FROM programas_formacion 
                    $whereClause
                    ORDER BY nombre_programa 
                    LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        $programas = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $programas,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
        exit;
    }
    
    // POST - Crear programa
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO programas_formacion (nombre_programa, nivel_formacion, duracion_meses, estado) 
                VALUES (:nombre, :nivel, :duracion, :estado)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre_programa'],
            ':nivel' => $data['nivel_formacion'] ?? 'Técnico',
            ':duracion' => $data['duracion_meses'] ?? 12,
            ':estado' => $data['estado'] ?? 'Activo'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Programa creado exitosamente'
        ]);
        exit;
    }
    
    // DELETE - Eliminar programa
    if ($method === 'DELETE') {
        $nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
        
        if (empty($nombre)) {
            throw new Exception('Nombre de programa requerido');
        }
        
        $sql = "DELETE FROM programas_formacion WHERE nombre_programa = :nombre";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':nombre' => $nombre]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Programa eliminado exitosamente'
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
