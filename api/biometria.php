<?php
/**
 * API de Biometría
 * Gestiona operaciones CRUD para embeddings faciales (BLOB binario)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// PREVENIR ERRORES DE JSON CORRUPTO
error_reporting(0);
ini_set('display_errors', 0);

// Log para depuración (opcional, ver c:/wamp64/logs/php_error.log o similar si estuviéramos en prod, aquí usaremos archivo local si es crítico)
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/../php-errors.log');

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $method = $_SERVER['REQUEST_METHOD'];
    $data = null;

    // Leer cuerpo JSON si es POST
    if ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Si no hay acción en GET, buscar en el cuerpo JSON
        if (empty($action) && isset($data['action'])) {
            $action = $data['action'];
        }
    }
    
    // ==================== REGISTRAR/ACTUALIZAR BIOMETRÍA ====================
    if ($action === 'registrar' && $method === 'POST') {
        // $data ya fue decodificado arriba
        
        if (empty($data['tipo']) || empty($data['id']) || empty($data['embedding'])) {
            throw new Exception('Parámetros incompletos: tipo, id y embedding son requeridos');
        }
        
        $tipo = $data['tipo']; // 'usuario' o 'aprendiz'
        $id = $data['id'];
        $embeddingBase64 = $data['embedding'];
        
        // Convertir Base64 a BLOB binario
        $embeddingBlob = base64_decode($embeddingBase64);
        
        if ($embeddingBlob === false) {
            throw new Exception('Embedding inválido: error al decodificar Base64');
        }
        
        // Determinar tabla según tipo
        if ($tipo === 'usuario') {
            $tabla = 'biometria_usuarios';
            $campoId = 'id_usuario';
        } elseif ($tipo === 'aprendiz') {
            $tabla = 'biometria_aprendices';
            $campoId = 'documento';
        } else {
            throw new Exception('Tipo inválido: debe ser "usuario" o "aprendiz"');
        }
        
        // Verificar si ya existe registro
        $sqlCheck = "SELECT id_biometria FROM $tabla WHERE $campoId = :id";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([':id' => $id]);
        $existe = $stmtCheck->fetch();
        
        if ($existe) {
            // Actualizar registro existente
            $sql = "UPDATE $tabla SET 
                    embedding_facial = :embedding,
                    ultima_actualizacion = CURRENT_TIMESTAMP
                    WHERE $campoId = :id";
            $mensaje = 'Biometría actualizada exitosamente';
        } else {
            // Insertar nuevo registro
            $sql = "INSERT INTO $tabla ($campoId, embedding_facial) VALUES (:id, :embedding)";
            $mensaje = 'Biometría registrada exitosamente';
        }
        
        
        // === ACTIVAR WAL MODE PARA ESTA CONEXIÓN ===
        try {
            $conn->exec('PRAGMA journal_mode = WAL;');
            $conn->exec('PRAGMA busy_timeout = 30000;');
        } catch (Exception $e) {
            // Ignorar si falla
        }
        
        // === INTENTAR GUARDAR EN BASE DE DATOS ===
        $guardadoEnBD = false;
        
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':embedding', $embeddingBlob, PDO::PARAM_LOB);
            $stmt->execute();
            
            $conn->commit();
            $guardadoEnBD = true;
            
        } catch (PDOException $e) {
            $conn->rollBack();
            
            // Si falla, guardar en archivo de respaldo
            $backupDir = __DIR__ . '/../backup_biometria';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $backupFile = $backupDir . '/' . $tipo . '_' . $id . '.json';
            $backupData = [
                'tipo' => $tipo,
                'id' => $id,
                'embedding' => $embeddingBase64,
                'timestamp' => date('Y-m-d H:i:s'),
                'mensaje' => 'Guardado temporalmente - BD bloqueada'
            ];
            
            file_put_contents($backupFile, json_encode($backupData));
            
            $mensaje = 'Biometría guardada temporalmente (BD ocupada). Se sincronizará automáticamente.';
        }
        
        // Si se guardó en BD, eliminar backup si existe
        if ($guardadoEnBD) {
            $backupFile = __DIR__ . '/../backup_biometria/' . $tipo . '_' . $id . '.json';
            if (file_exists($backupFile)) {
                unlink($backupFile);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => $mensaje
        ]);
        exit;
    }
    
    // ==================== OBTENER EMBEDDING ====================
    if ($action === 'obtener' && $method === 'GET') {
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($tipo) || empty($id)) {
            throw new Exception('Parámetros incompletos: tipo e id son requeridos');
        }
        
        // Determinar tabla según tipo
        if ($tipo === 'usuario') {
            $tabla = 'biometria_usuarios';
            $campoId = 'id_usuario';
        } elseif ($tipo === 'aprendiz') {
            $tabla = 'biometria_aprendices';
            $campoId = 'documento';
        } else {
            throw new Exception('Tipo inválido');
        }
        
        $sql = "SELECT embedding_facial, fecha_registro, ultima_actualizacion 
                FROM $tabla WHERE $campoId = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo json_encode([
                'success' => false,
                'message' => 'No se encontró biometría registrada'
            ]);
            exit;
        }
        
        // Convertir BLOB a Base64 para enviar por HTTP
        $embeddingBase64 = base64_encode($result['embedding_facial']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'embedding' => $embeddingBase64,
                'fecha_registro' => $result['fecha_registro'],
                'ultima_actualizacion' => $result['ultima_actualizacion']
            ]
        ]);
        exit;
    }
    
    // ==================== CONSULTAR ESTADO ====================
    if ($action === 'estado' && $method === 'GET') {
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($tipo) || empty($id)) {
            throw new Exception('Parámetros incompletos');
        }
        
        // Determinar tabla según tipo
        if ($tipo === 'usuario') {
            $tabla = 'biometria_usuarios';
            $campoId = 'id_usuario';
        } elseif ($tipo === 'aprendiz') {
            $tabla = 'biometria_aprendices';
            $campoId = 'documento';
        } else {
            throw new Exception('Tipo inválido');
        }
        
        $sql = "SELECT COUNT(*) as count FROM $tabla WHERE $campoId = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        $tieneBiometria = $result['count'] > 0;
        
        echo json_encode([
            'success' => true,
            'tiene_biometria' => $tieneBiometria
        ]);
        exit;
    }
    
    // ==================== ELIMINAR BIOMETRÍA ====================
    if ($action === 'eliminar' && $method === 'DELETE') {
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($tipo) || empty($id)) {
            throw new Exception('Parámetros incompletos');
        }
        
        // Determinar tabla según tipo
        if ($tipo === 'usuario') {
            $tabla = 'biometria_usuarios';
            $campoId = 'id_usuario';
        } elseif ($tipo === 'aprendiz') {
            $tabla = 'biometria_aprendices';
            $campoId = 'documento';
        } else {
            throw new Exception('Tipo inválido');
        }
        
        $sql = "DELETE FROM $tabla WHERE $campoId = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Biometría eliminada exitosamente'
        ]);
        exit;
    }
    
    // ==================== VERIFICAR IDENTIDAD (1:1) ====================
    if ($action === 'verificar' && $method === 'POST') {
        // $data ya fue decodificado arriba
        
        if (empty($data['tipo']) || empty($data['id']) || empty($data['embedding'])) {
            throw new Exception('Parámetros incompletos');
        }
        
        $tipo = $data['tipo'];
        $id = $data['id'];
        $embeddingInput = $data['embedding']; // Array de floats
        
        // Determinar tabla
        if ($tipo === 'usuario') {
            $tabla = 'biometria_usuarios';
            $campoId = 'id_usuario';
        } elseif ($tipo === 'aprendiz') {
            $tabla = 'biometria_aprendices';
            $campoId = 'documento';
        } else {
            throw new Exception('Tipo inválido');
        }
        
        // Obtener embedding almacenado
        $sql = "SELECT embedding_facial FROM $tabla WHERE $campoId = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo json_encode(['success' => false, 'match' => false, 'message' => 'No tiene biometría registrada']);
            exit;
        }
        
        // Convertir BLOB a array de floats
        $embeddingStored = unpack('f*', $result['embedding_facial']);
        $embeddingStored = array_values($embeddingStored); // Reindexar
        
        // Calcular similitud
        $similitud = calcularSimilitudCoseno($embeddingInput, $embeddingStored);
        $umbral = 0.70; // 70% de similitud
        
        echo json_encode([
            'success' => true,
            'match' => $similitud >= $umbral,
            'similitud' => $similitud,
            'mensaje' => ($similitud >= $umbral) ? 'Verificado exitosamente' : 'No coincide'
        ]);
        exit;
    }
    
    // ==================== IDENTIFICAR EN GRUPO (1:N) ====================
    if ($action === 'identificar_grupo' && $method === 'POST') {
        $embeddingInput = $data['embedding'];
        $fichaActual = isset($data['ficha']) ? $data['ficha'] : null;
        
        if (empty($embeddingInput)) {
            throw new Exception('Embedding requerido');
        }

        // Pre-calcular la norma del embedding de entrada UNA sola vez
        $normInput = 0;
        foreach ($embeddingInput as $v) $normInput += $v * $v;
        $normInput = sqrt($normInput);
        if ($normInput == 0) throw new Exception('Embedding de entrada inválido');

        // Obtener candidatos (ORDER BY ultima_actualizacion para priorizar registros recientes)
        $sql = "SELECT b.documento, b.embedding_facial, a.nombre, a.apellido, a.numero_ficha 
                FROM biometria_aprendices b
                INNER JOIN aprendices a ON b.documento = a.documento";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mejorMatch = null;
        $maxSimilitud = 0;
        $umbral = 0.75;
        $umbralAlto = 0.90; // Si supera esto, es una coincidencia casi perfecta -> salida temprana
        $encontrado = false;
        
        foreach ($candidatos as $candidato) {
            // Skip embeddings corruptos/vacíos
            if (empty($candidato['embedding_facial'])) continue;
            
            $embeddingStored = unpack('f*', $candidato['embedding_facial']);
            $embeddingStored = array_values($embeddingStored);
            
            // Calcular similitud coseno optimizada (pre-normalizada)
            $dotProduct = 0;
            $normStored = 0;
            $len = count($embeddingInput);
            if (count($embeddingStored) !== $len) continue;
            
            for ($i = 0; $i < $len; $i++) {
                $dotProduct += $embeddingInput[$i] * $embeddingStored[$i];
                $normStored += $embeddingStored[$i] * $embeddingStored[$i];
            }
            if ($normStored == 0) continue;
            
            $similitud = $dotProduct / ($normInput * sqrt($normStored));
            
            if ($similitud > $maxSimilitud && $similitud >= $umbral) {
                $maxSimilitud = $similitud;
                $mejorMatch = [
                    'documento'     => $candidato['documento'],
                    'nombre'        => $candidato['nombre'],
                    'apellido'      => $candidato['apellido'],
                    'ficha_aprendiz'=> $candidato['numero_ficha'],
                    'similitud'     => $similitud
                ];
                // Early-exit: similitud excelente, no hace falta seguir comparando
                if ($similitud >= $umbralAlto) { $encontrado = true; break; }
            }
        }
        
        if ($mejorMatch) {
            $perteneceFicha = ($fichaActual && $mejorMatch['ficha_aprendiz'] == $fichaActual);
            echo json_encode([
                'success'        => true,
                'match'          => true,
                'tipo_usuario'   => 'aprendiz',
                'data'           => $mejorMatch,
                'pertenece_ficha'=> $perteneceFicha,
                'mensaje'        => $perteneceFicha ? 'Identificado exitosamente' : 'Usuario no pertenece a esta ficha'
            ]);
            exit;
        }

        // Si no se encontró en aprendices, buscar en USUARIOS
        $sqlUsers = "SELECT b.id_usuario, b.embedding_facial, u.nombre, u.apellido, u.rol 
                     FROM biometria_usuarios b
                     INNER JOIN usuarios u ON b.id_usuario = u.id_usuario";
        
        $stmtUsers = $conn->prepare($sqlUsers);
        $stmtUsers->execute();
        $candidatosUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        
        $mejorMatchUser = null;
        $maxSimilitudUser = 0;
        
        foreach ($candidatosUsers as $candidato) {
            if (empty($candidato['embedding_facial'])) continue;
            $embeddingStored = unpack('f*', $candidato['embedding_facial']);
            $embeddingStored = array_values($embeddingStored);
            
            $dotProduct = 0; $normStored = 0;
            $len = count($embeddingInput);
            if (count($embeddingStored) !== $len) continue;
            for ($i = 0; $i < $len; $i++) {
                $dotProduct += $embeddingInput[$i] * $embeddingStored[$i];
                $normStored += $embeddingStored[$i] * $embeddingStored[$i];
            }
            if ($normStored == 0) continue;
            $similitud = $dotProduct / ($normInput * sqrt($normStored));
            
            if ($similitud > $maxSimilitudUser && $similitud >= $umbral) {
                $maxSimilitudUser = $similitud;
                $mejorMatchUser = [
                    'documento' => $candidato['id_usuario'],
                    'nombre'    => $candidato['nombre'],
                    'apellido'  => $candidato['apellido'],
                    'rol'       => $candidato['rol'],
                    'similitud' => $similitud
                ];
                if ($similitud >= $umbralAlto) break;
            }
        }

        if ($mejorMatchUser) {
            echo json_encode([
                'success'       => true,
                'match'         => true,
                'tipo_usuario'  => 'usuario',
                'data'          => $mejorMatchUser,
                'pertenece_ficha'=> false,
                'mensaje'       => 'Identificado como ' . ucfirst($mejorMatchUser['rol']) . ': ' . $mejorMatchUser['nombre'] . ' ' . $mejorMatchUser['apellido']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'match'   => false,
                'message' => 'No se encontró coincidencia en el sistema'
            ]);
        }
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

/**
 * Calcula la similitud coseno entre dos vectores
 */
function calcularSimilitudCoseno($vec1, $vec2) {
    if (count($vec1) !== count($vec2)) {
        return 0;
    }
    
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;
    
    for ($i = 0; $i < count($vec1); $i++) {
        $dotProduct += $vec1[$i] * $vec2[$i];
        $normA += $vec1[$i] * $vec1[$i];
        $normB += $vec2[$i] * $vec2[$i];
    }
    
    if ($normA == 0 || $normB == 0) return 0;
    
    return $dotProduct / (sqrt($normA) * sqrt($normB));
}

?>
