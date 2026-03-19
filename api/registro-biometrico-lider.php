<?php
/**
 * API PARA REGISTRO BIOMÉTRICO DE LÍDERES
 * Registro facial directo desde panel de liderazgo
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

class RegistroBiometricoLiderAPI {
    private $conn;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Obtener líderes con estado biométrico
     */
    public function obtenerLideresEstadoBiometrico() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    a.id,
                    a.numero_documento as documento,
                    a.nombres,
                    a.apellidos,
                    a.foto_url,
                    a.tipo_liderazgo,
                    a.ficha,
                    fr.id as facial_id,
                    fr.imagen_path,
                    fr.activo as facial_activo,
                    fr.fecha_registro as facial_fecha,
                    CASE 
                        WHEN fr.id IS NOT NULL AND fr.activo = true THEN 'registrado'
                        WHEN fr.id IS NOT NULL AND fr.activo = false THEN 'inactivo'
                        ELSE 'no_registrado'
                    END as estado_biometrico
                FROM aprendices a
                LEFT JOIN facial_recognition fr ON a.numero_documento = fr.documento
                WHERE a.estado = 'LECTIVA'
                AND (a.tipo_liderazgo IS NOT NULL AND a.tipo_liderazgo != '')
                ORDER BY a.apellidos, a.nombres
            ");
            
            $stmt->execute();
            $lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar datos
            foreach ($lideres as &$lider) {
                $lider['nombre_completo'] = trim($lider['nombres'] . ' ' . $lider['apellidos']);
                $lider['puede_registrar'] = $lider['estado_biometrico'] === 'no_registrado' || $lider['estado_biometrico'] === 'inactivo';
            }
            
            echo json_encode([
                'success' => true,
                'lideres' => $lideres,
                'estadisticas' => [
                    'total' => count($lideres),
                    'registrados' => count(array_filter($lideres, fn($l) => $l['estado_biometrico'] === 'registrado')),
                    'no_registrados' => count(array_filter($lideres, fn($l) => $l['estado_biometrico'] === 'no_registrado')),
                    'inactivos' => count(array_filter($lideres, fn($l) => $l['estado_biometrico'] === 'inactivo'))
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Registrar nuevo rostro facial
     */
    public function registrarRostroFacial($data) {
        try {
            $documento = $data['documento'];
            $imagen_base64 = $data['imagen'];
            $descriptor = $data['descriptor'];
            
            // Validar que el líder exista
            $stmt = $this->conn->prepare("
                SELECT id, nombres, apellidos, tipo_liderazgo 
                FROM aprendices 
                WHERE numero_documento = :documento 
                AND estado = 'LECTIVA'
                LIMIT 1
            ");
            
            $stmt->execute([':documento' => $documento]);
            $lider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lider) {
                throw new Exception('Líder no encontrado o no está activo');
            }
            
            // Verificar si ya tiene registro
            $stmt = $this->conn->prepare("
                SELECT id FROM facial_recognition 
                WHERE documento = :documento
                LIMIT 1
            ");
            
            $stmt->execute([':documento' => $documento]);
            $existente = $stmt->fetch();
            
            if ($existente) {
                // Actualizar registro existente
                $stmt = $this->conn->prepare("
                    UPDATE facial_recognition 
                    SET descriptor = :descriptor,
                        imagen_path = :imagen_path,
                        activo = true,
                        fecha_registro = NOW()
                    WHERE documento = :documento
                ");
                
                $stmt->execute([
                    ':descriptor' => json_encode($descriptor),
                    ':imagen_path' => $imagen_base64,
                    ':documento' => $documento
                ]);
                
                $accion = 'actualizado';
            } else {
                // Crear nuevo registro
                $stmt = $this->conn->prepare("
                    INSERT INTO facial_recognition 
                    (documento, descriptor, imagen_path, activo, fecha_registro) 
                    VALUES (:documento, :descriptor, :imagen_path, true, NOW())
                ");
                
                $stmt->execute([
                    ':documento' => $documento,
                    ':descriptor' => json_encode($descriptor),
                    ':imagen_path' => $imagen_base64
                ]);
                
                $accion = 'registrado';
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Rostro facial {$accion} exitosamente",
                'nombre' => trim($lider['nombres'] . ' ' . $lider['apellidos']),
                'documento' => $documento,
                'accion' => $accion
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Eliminar registro facial
     */
    public function eliminarRegistroFacial($documento) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE facial_recognition 
                SET activo = false 
                WHERE documento = :documento
            ");
            
            $stmt->execute([':documento' => $documento]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Registro facial desactivado'
                ]);
            } else {
                throw new Exception('No se encontró registro facial para desactivar');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener detalles de un líder específico
     */
    public function obtenerDetallesLider($documento) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    a.id,
                    a.numero_documento as documento,
                    a.nombres,
                    a.apellidos,
                    a.foto_url,
                    a.tipo_liderazgo,
                    a.ficha,
                    a.telefono,
                    a.correo,
                    fr.id as facial_id,
                    fr.imagen_path,
                    fr.descriptor,
                    fr.activo as facial_activo,
                    fr.fecha_registro as facial_fecha
                FROM aprendices a
                LEFT JOIN facial_recognition fr ON a.numero_documento = fr.documento
                WHERE a.numero_documento = :documento
                AND a.estado = 'LECTIVA'
                LIMIT 1
            ");
            
            $stmt->execute([':documento' => $documento]);
            $lider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lider) {
                $lider['nombre_completo'] = trim($lider['nombres'] . ' ' . $lider['apellidos']);
                $lider['descriptor'] = $lider['descriptor'] ? json_decode($lider['descriptor']) : null;
                
                // Estado biométrico
                if ($lider['facial_id'] && $lider['facial_activo']) {
                    $lider['estado_biometrico'] = 'registrado';
                } else if ($lider['facial_id'] && !$lider['facial_activo']) {
                    $lider['estado_biometrico'] = 'inactivo';
                } else {
                    $lider['estado_biometrico'] = 'no_registrado';
                }
                
                echo json_encode([
                    'success' => true,
                    'lider' => $lider
                ]);
            } else {
                throw new Exception('Líder no encontrado');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sincronizar registros con sistema principal
     */
    public function sincronizarRegistros() {
        try {
            // Obtener todos los registros faciales activos
            $stmt = $this->conn->prepare("
                SELECT 
                    fr.documento,
                    fr.descriptor,
                    fr.imagen_path,
                    fr.fecha_registro,
                    a.nombres,
                    a.apellidos,
                    a.tipo_liderazgo
                FROM facial_recognition fr
                INNER JOIN aprendices a ON fr.documento = a.numero_documento
                WHERE fr.activo = true
                AND a.estado = 'LECTIVA'
                ORDER BY fr.fecha_registro DESC
            ");
            
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preparar datos para reconocimiento
            $faces_data = [];
            foreach ($registros as $registro) {
                $faces_data[] = [
                    'id' => $registro['documento'],
                    'name' => trim($registro['nombres'] . ' ' . $registro['apellidos']),
                    'descriptor' => json_decode($registro['descriptor']),
                    'image' => $registro['imagen_path'],
                    'role' => $registro['tipo_liderazgo']
                ];
            }
            
            // Actualizar archivo de reconocimiento
            $json_file = __DIR__ . '/../data/lideres-faciales.json';
            $json_dir = dirname($json_file);
            
            if (!is_dir($json_dir)) {
                mkdir($json_dir, 0755, true);
            }
            
            file_put_contents($json_file, json_encode([
                'faces' => $faces_data,
                'updated' => date('Y-m-d H:i:s'),
                'total' => count($faces_data),
                'source' => 'liderazgo_sync'
            ], JSON_PRETTY_PRINT));
            
            echo json_encode([
                'success' => true,
                'message' => 'Registros sincronizados exitosamente',
                'total_registros' => count($faces_data),
                'archivo_actualizado' => $json_file
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Manejo de solicitudes
$api = new RegistroBiometricoLiderAPI();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'lideresEstado':
                $api->obtenerLideresEstadoBiometrico();
                break;
            case 'detallesLider':
                $documento = $_GET['documento'] ?? null;
                if ($documento) {
                    $api->obtenerDetallesLider($documento);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Documento requerido']);
                }
                break;
            case 'sincronizar':
                $api->sincronizarRegistros();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    case 'POST':
        switch ($action) {
            case 'registrarRostro':
                $data = json_decode(file_get_contents('php://input'), true);
                $api->registrarRostroFacial($data);
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    case 'DELETE':
        switch ($action) {
            case 'eliminarRegistro':
                $documento = $_GET['documento'] ?? null;
                if ($documento) {
                    $api->eliminarRegistroFacial($documento);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Documento requerido']);
                }
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
