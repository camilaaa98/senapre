<?php
/**
 * API para gestión de credenciales de voceros
 * Habilita/deshabilita credenciales según rol activo
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
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // GET - Obtener estado de credenciales de un vocero
    if ($method === 'GET' && $action === 'check') {
        $documento = isset($_GET['documento']) ? trim($_GET['documento']) : '';
        
        if (empty($documento)) {
            throw new Exception('Documento requerido');
        }
        
        // Verificar si tiene algún rol activo
        $tieneRolActivo = false;
        $rolesActivos = [];
        
        // 1. Verificar si es vocero principal
        $stmt = $conn->prepare("SELECT numero_ficha FROM fichas WHERE TRIM(vocero_principal) = :doc");
        $stmt->execute([':doc' => $documento]);
        $fichasPrincipal = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($fichasPrincipal)) {
            $tieneRolActivo = true;
            $rolesActivos[] = [
                'tipo' => 'principal',
                'fichas' => array_column($fichasPrincipal, 'numero_ficha')
            ];
        }
        
        // 2. Verificar si es vocero suplente
        $stmt = $conn->prepare("SELECT numero_ficha FROM fichas WHERE TRIM(vocero_suplente) = :doc");
        $stmt->execute([':doc' => $documento]);
        $fichasSuplente = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($fichasSuplente)) {
            $tieneRolActivo = true;
            $rolesActivos[] = [
                'tipo' => 'suplente',
                'fichas' => array_column($fichasSuplente, 'numero_ficha')
            ];
        }
        
        // 3. Verificar si es vocero de enfoque
        $stmt = $conn->prepare("SELECT tipo_poblacion FROM voceros_enfoque WHERE TRIM(documento) = :doc");
        $stmt->execute([':doc' => $documento]);
        $enfoques = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($enfoques)) {
            $tieneRolActivo = true;
            $rolesActivos[] = [
                'tipo' => 'enfoque',
                'poblaciones' => array_column($enfoques, 'tipo_poblacion')
            ];
        }
        
        // 4. Verificar si es representante
        $stmt = $conn->prepare("SELECT jornada FROM representantes_jornada WHERE TRIM(documento) = :doc");
        $stmt->execute([':doc' => $documento]);
        $representantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($representantes)) {
            $tieneRolActivo = true;
            $rolesActivos[] = [
                'tipo' => 'representante',
                'jornadas' => array_column($representantes, 'jornada')
            ];
        }
        
        // Obtener datos del usuario
        $stmt = $conn->prepare("SELECT nombre, apellido, correo FROM usuarios WHERE id_usuario = :doc");
        $stmt->execute([':doc' => $documento]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'documento' => $documento,
                'nombre' => $usuario['nombre'] ?? '',
                'apellido' => $usuario['apellido'] ?? '',
                'correo' => $usuario['correo'] ?? '',
                'tiene_credenciales_activas' => $tieneRolActivo,
                'roles_activos' => $rolesActivos,
                'password_sugerida' => $documento // La contraseña es el documento
            ]
        ]);
        exit;
    }
    
    // POST - Actualizar credenciales (habilitar/deshabilitar)
    if ($method === 'POST' && $action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['documento'])) {
            throw new Exception('Documento requerido');
        }
        
        $documento = trim($data['documento']);
        
        // Verificar roles activos (misma lógica que en GET)
        $tieneRolActivo = false;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM fichas WHERE TRIM(vocero_principal) = :doc");
        $stmt->execute([':doc' => $documento]);
        if ($stmt->fetch()['count'] > 0) $tieneRolActivo = true;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM fichas WHERE TRIM(vocero_suplente) = :doc");
        $stmt->execute([':doc' => $documento]);
        if ($stmt->fetch()['count'] > 0) $tieneRolActivo = true;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM voceros_enfoque WHERE TRIM(documento) = :doc");
        $stmt->execute([':doc' => $documento]);
        if ($stmt->fetch()['count'] > 0) $tieneRolActivo = true;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM representantes_jornada WHERE TRIM(documento) = :doc");
        $stmt->execute([':doc' => $documento]);
        if ($stmt->fetch()['count'] > 0) $tieneRolActivo = true;
        
        // Actualizar estado de credenciales en la tabla usuarios
        if ($tieneRolActivo) {
            // Habilitar credenciales
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET estado = 1, 
                    password_hash = :password,
                    credenciales_vocero_activas = true,
                    fecha_actualizacion_credenciales = CURRENT_TIMESTAMP
                WHERE id_usuario = :documento
            ");
            $stmt->execute([
                ':password' => password_hash($documento, PASSWORD_DEFAULT),
                ':documento' => $documento
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Credenciales habilitadas exitosamente',
                'data' => [
                    'documento' => $documento,
                    'password' => $documento,
                    'credenciales_activas' => true
                ]
            ]);
        } else {
            // Deshabilitar credenciales
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET estado = 0, 
                    credenciales_vocero_activas = false,
                    fecha_actualizacion_credenciales = CURRENT_TIMESTAMP
                WHERE id_usuario = :documento
            ");
            $stmt->execute([':documento' => $documento]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Credenciales deshabilitadas (sin roles activos)',
                'data' => [
                    'documento' => $documento,
                    'credenciales_activas' => false
                ]
            ]);
        }
        exit;
    }
    
    // GET - Listar todos los voceros con estado de credenciales
    if ($method === 'GET' && $action === 'list') {
        $sql = "
            SELECT 
                u.id_usuario as documento,
                u.nombre,
                u.apellido,
                u.correo,
                u.estado,
                COALESCE(u.credenciales_vocero_activas, false) as credenciales_activas,
                u.fecha_actualizacion_credenciales,
                -- Contar roles activos
                (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_principal) = u.id_usuario) as es_principal,
                (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_suplente) = u.id_usuario) as es_suplente,
                (SELECT COUNT(*) FROM voceros_enfoque WHERE TRIM(documento) = u.id_usuario) as es_enfoque,
                (SELECT COUNT(*) FROM representantes_jornada WHERE TRIM(documento) = u.id_usuario) as es_representante
            FROM usuarios u
            WHERE u.rol = 'vocero'
            ORDER BY u.nombre, u.apellido
        ";
        
        $stmt = $conn->query($sql);
        $voceros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada vocero, determinar si debe tener credenciales activas
        foreach ($voceros as &$vocero) {
            $tieneRolActivo = ($vocero['es_principal'] > 0 || $vocero['es_suplente'] > 0 || 
                              $vocero['es_enfoque'] > 0 || $vocero['es_representante'] > 0);
            
            // Sincronizar estado si es necesario
            if ($vocero['credenciales_activas'] !== $tieneRolActivo) {
                $updateStmt = $conn->prepare("
                    UPDATE usuarios 
                    SET estado = :estado, 
                        credenciales_vocero_activas = :tiene_credenciales,
                        fecha_actualizacion_credenciales = CURRENT_TIMESTAMP
                    WHERE id_usuario = :documento
                ");
                $updateStmt->execute([
                    ':estado' => $tieneRolActivo ? 1 : 0,
                    ':tiene_credenciales' => $tieneRolActivo,
                    ':documento' => $vocero['documento']
                ]);
                
                $vocero['estado'] = $tieneRolActivo ? 1 : 0;
                $vocero['credenciales_activas'] = $tieneRolActivo;
                $vocero['sincronizado'] = true;
            } else {
                $vocero['sincronizado'] = false;
            }
            
            $vocero['tiene_rol_activo'] = $tieneRolActivo;
            $vocero['password_sugerida'] = $vocero['documento'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $voceros
        ]);
        exit;
    }
    
    // POST - Sincronizar todas las credenciales de voceros
    if ($method === 'POST' && $action === 'sync-all') {
        $sql = "
            SELECT 
                u.id_usuario as documento,
                COALESCE(u.credenciales_vocero_activas, false) as credenciales_activas,
                (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_principal) = u.id_usuario) as es_principal,
                (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_suplente) = u.id_usuario) as es_suplente,
                (SELECT COUNT(*) FROM voceros_enfoque WHERE TRIM(documento) = u.id_usuario) as es_enfoque,
                (SELECT COUNT(*) FROM representantes_jornada WHERE TRIM(documento) = u.id_usuario) as es_representante
            FROM usuarios u
            WHERE u.rol = 'vocero'
        ";
        
        $stmt = $conn->query($sql);
        $voceros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $actualizados = 0;
        foreach ($voceros as $vocero) {
            $tieneRolActivo = ($vocero['es_principal'] > 0 || $vocero['es_suplente'] > 0 || 
                              $vocero['es_enfoque'] > 0 || $vocero['es_representante'] > 0);
            
            // Solo actualizar si hay cambio
            if ($vocero['credenciales_activas'] !== $tieneRolActivo) {
                $updateStmt = $conn->prepare("
                    UPDATE usuarios 
                    SET estado = :estado, 
                        credenciales_vocero_activas = :tiene_credenciales,
                        fecha_actualizacion_credenciales = CURRENT_TIMESTAMP
                    WHERE id_usuario = :documento
                ");
                $updateStmt->execute([
                    ':estado' => $tieneRolActivo ? 1 : 0,
                    ':tiene_credenciales' => $tieneRolActivo,
                    ':documento' => $vocero['documento']
                ]);
                $actualizados++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Sincronización completada. $actualizados voceros actualizados.",
            'data' => [
                'total_voceros' => count($voceros),
                'actualizados' => $actualizados
            ]
        ]);
        exit;
    }
    
    throw new Exception('Endpoint no encontrado');
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
