<?php
/**
 * API de Biometría - Versión Optimizada para PostgreSQL/SQLite
 * Gestiona operaciones CRUD para embeddings faciales
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $method = $_SERVER['REQUEST_METHOD'];
    $data = null;

    if ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (empty($action) && isset($data['action'])) {
            $action = $data['action'];
        }
    }
    
    // ==================== REGISTRAR/ACTUALIZAR BIOMETRÍA ====================
    if ($action === 'registrar' && $method === 'POST') {
        if (empty($data['tipo']) || empty($data['id']) || empty($data['embedding'])) {
            throw new Exception('Parámetros incompletos');
        }
        
        $tipo = $data['tipo'];
        $id = $data['id'];
        $embeddingBlob = base64_decode($data['embedding']);
        
        if ($embeddingBlob === false) throw new Exception('Embedding inválido');
        
        $tabla = match($tipo) {
            'usuario' => 'biometria_usuarios',
            'lider' => 'biometria_lideres',
            default => 'biometria_aprendices'
        };
        $campoId = match($tipo) {
            'usuario' => 'id_usuario',
            default => 'documento'
        };
        
        $sqlCheck = "SELECT id_biometria FROM $tabla WHERE $campoId = :id";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([':id' => $id]);
        
        if ($stmtCheck->fetch()) {
            $sql = "UPDATE $tabla SET embedding_facial = :embedding, ultima_actualizacion = CURRENT_TIMESTAMP WHERE $campoId = :id";
        } else {
            $sql = "INSERT INTO $tabla ($campoId, embedding_facial) VALUES (:id, :embedding)";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->bindValue(':embedding', $embeddingBlob, PDO::PARAM_LOB);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Biometría guardada exitosamente']);
        exit;
    }
    
    // ==================== OBTENER EMBEDDING ====================
    if ($action === 'obtener' && $method === 'GET') {
        $tipo = $_GET['tipo'] ?? '';
        $id = $_GET['id'] ?? '';
        
        if (empty($tipo) || empty($id)) throw new Exception('Faltan parámetros');
        
        $tabla = match($tipo) {
            'usuario' => 'biometria_usuarios',
            'lider' => 'biometria_lideres',
            default => 'biometria_aprendices'
        };
        $campoId = match($tipo) {
            'usuario' => 'id_usuario',
            default => 'documento'
        };
        
        $sql = "SELECT embedding_facial FROM $tabla WHERE $campoId = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'No registrado']);
            exit;
        }
        
        $blob = $row['embedding_facial'];
        if (is_resource($blob)) $blob = stream_get_contents($blob);
        
        echo json_encode([
            'success' => true,
            'data' => ['embedding' => base64_encode($blob)]
        ]);
        exit;
    }

    // ==================== CONSULTAR ESTADO ====================
    if ($action === 'estado' && $method === 'GET') {
        $tipo = $_GET['tipo'] ?? '';
        $id = $_GET['id'] ?? '';
        $tabla = match($tipo) {
            'usuario' => 'biometria_usuarios',
            'lider' => 'biometria_lideres',
            default => 'biometria_aprendices'
        };
        $campoId = match($tipo) {
            'usuario' => 'id_usuario',
            default => 'documento'
        };
        
        $sql = "SELECT COUNT(*) as count FROM $tabla WHERE $campoId = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'tiene_biometria' => ($stmt->fetch()['count'] > 0)]);
        exit;
    }

    // ==================== VERIFICAR IDENTIDAD (1:1) ====================
    if ($action === 'verificar' && $method === 'POST') {
        $id = $data['id'];
        $tipo = $data['tipo'];
        $embeddingInput = $data['embedding'];
        
        $tabla = match($tipo) {
            'usuario' => 'biometria_usuarios',
            'lider' => 'biometria_lideres',
            default => 'biometria_aprendices'
        };
        $campoId = match($tipo) {
            'usuario' => 'id_usuario',
            default => 'documento'
        };
        
        $sql = "SELECT embedding_facial FROM $tabla WHERE $campoId = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            echo json_encode(['success' => false, 'match' => false]);
            exit;
        }
        
        $blob = $row['embedding_facial'];
        if (is_resource($blob)) $blob = stream_get_contents($blob);
        
        $embeddingStored = array_values(unpack('f*', $blob));
        $similitud = calcularSimilitudCoseno($embeddingInput, $embeddingStored);
        
        echo json_encode([
            'success' => true,
            'match' => ($similitud >= 0.70),
            'similitud' => $similitud
        ]);
        exit;
    }

    // ==================== IDENTIFICAR EN GRUPO (1:N) ====================
    if ($action === 'identificar_grupo' && $method === 'POST') {
        $embeddingInput = $data['embedding'];
        $fichaActual = $data['ficha'] ?? null;
        
        $normInput = 0;
        foreach ($embeddingInput as $v) $normInput += $v * $v;
        $normInput = sqrt($normInput);
        if ($normInput == 0) throw new Exception('Embedding inválido');

        // Buscar en Aprendices
        $sql = "SELECT b.documento, b.embedding_facial, a.nombre, a.apellido, a.numero_ficha 
                FROM biometria_aprendices b
                INNER JOIN aprendices a ON b.documento = a.documento";
        if ($fichaActual) {
            $sql .= " WHERE a.numero_ficha = :ficha";
        }
        
        $stmt = $conn->prepare($sql);
        if ($fichaActual) $stmt->bindValue(':ficha', $fichaActual);
        $stmt->execute();
        
        while ($row = $stmt->fetch()) {
            $blob = $row['embedding_facial'];
            if (is_resource($blob)) $blob = stream_get_contents($blob);
            
            $embeddingStored = array_values(unpack('f*', $blob));
            if (count($embeddingStored) !== count($embeddingInput)) continue;
            
            $similitud = calcularSimilitudCoseno($embeddingInput, $embeddingStored);
            
            if ($similitud >= 0.85) {
                echo json_encode([
                    'success' => true,
                    'match' => true,
                    'data' => [
                        'documento' => $row['documento'],
                        'nombre' => $row['nombre'],
                        'apellido' => $row['apellido'],
                        'ficha' => $row['numero_ficha'],
                        'similitud' => $similitud
                    ],
                    'pertenece_ficha' => true
                ]);
                exit;
            }
        }
        
        // Si no, buscar en usuarios (opcional, simplificado)
        echo json_encode(['success' => true, 'match' => false]);
        exit;
    }

    // ==================== IDENTIFICAR LÍDERES (1:N) ====================
    if ($action === 'identificar_lideres' && $method === 'POST') {
        $embeddingInput = $data['embedding'];
        $reunionId = $data['reunion_id'] ?? null;
        
        $normInput = 0;
        foreach ($embeddingInput as $v) $normInput += $v * $v;
        $normInput = sqrt($normInput);
        if ($normInput == 0) throw new Exception('Embedding inválido');

        // Buscar en líderes biométricos
        $sql = "SELECT b.documento, b.embedding_facial, a.nombre, a.apellido, a.tipo_poblacion as tipo_liderazgo, a.numero_ficha as ficha 
                FROM biometria_lideres b
                INNER JOIN aprendices a ON b.documento = a.documento
                WHERE a.estado = 'LECTIVA' 
                AND (a.tipo_liderazgo IS NOT NULL AND a.tipo_liderazgo != '')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        while ($row = $stmt->fetch()) {
            $blob = $row['embedding_facial'];
            if (is_resource($blob)) $blob = stream_get_contents($blob);
            
            $embeddingStored = array_values(unpack('f*', $blob));
            if (count($embeddingStored) !== count($embeddingInput)) continue;
            
            $similitud = calcularSimilitudCoseno($embeddingInput, $embeddingStored);
            
            if ($similitud >= 0.85) {
                // Verificar si el líder está convocado a la reunión
                $perteneceReunion = true;
                if ($reunionId) {
                    $checkStmt = $conn->prepare("SELECT 1 FROM reunion_lideres WHERE id_reunion = :reunion AND id_lider = (SELECT id FROM aprendices WHERE numero_documento = :documento)");
                    $checkStmt->execute([':reunion' => $reunionId, ':documento' => $row['documento']]);
                    $perteneceReunion = $checkStmt->fetch() ? true : false;
                }
                
                echo json_encode([
                    'success' => true,
                    'match' => true,
                    'data' => [
                        'documento' => $row['documento'],
                        'nombre' => $row['nombres'],
                        'apellido' => $row['apellidos'],
                        'tipo_liderazgo' => $row['tipo_liderazgo'],
                        'ficha' => $row['ficha'],
                        'similitud' => $similitud
                    ],
                    'pertenece_reunion' => $perteneceReunion
                ]);
                exit;
            }
        }
        
        echo json_encode(['success' => true, 'match' => false]);
        exit;
    }

    if ($action === 'eliminar') {
        $id = $_GET['id'] ?? '';
        $tipo = $_GET['tipo'] ?? '';
        $tabla = match($tipo) {
            'usuario' => 'biometria_usuarios',
            'lider' => 'biometria_lideres',
            default => 'biometria_aprendices'
        };
        $campoId = match($tipo) {
            'usuario' => 'id_usuario',
            default => 'documento'
        };
        $stmt = $conn->prepare("DELETE FROM $tabla WHERE $campoId = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function calcularSimilitudCoseno($vec1, $vec2) {
    if (count($vec1) !== count($vec2)) return 0;
    $dot = 0; $n1 = 0; $n2 = 0;
    for ($i = 0; $i < count($vec1); $i++) {
        $dot += $vec1[$i] * $vec2[$i];
        $n1 += $vec1[$i] * $vec1[$i];
        $n2 += $vec2[$i] * $vec2[$i];
    }
    return ($n1 == 0 || $n2 == 0) ? 0 : $dot / (sqrt($n1) * sqrt($n2));
}
?>
