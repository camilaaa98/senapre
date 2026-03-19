<?php
/**
 * API PARA GESTIÓN BIOMÉTRICA DE LÍDERES
 * Integración con sistema de reconocimiento facial del panel de director
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

class BiometricoLideresAPI {
    private $conn;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Obtener líderes convocados a una reunión con sus datos biométricos
     */
    public function obtenerLideresReunion($reunion_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    a.id,
                    a.numero_documento as documento,
                    a.nombres,
                    a.apellidos,
                    a.telefono,
                    a.correo,
                    a.foto_url,
                    a.tipo_liderazgo,
                    fr.id as facial_id,
                    fr.descriptor,
                    fr.imagen_path,
                    fr.activo as facial_activo,
                    fr.fecha_registro as facial_fecha
                FROM aprendices a
                LEFT JOIN facial_recognition fr ON a.numero_documento = fr.documento
                WHERE a.id IN (
                    SELECT DISTINCT aprendiz_id 
                    FROM convocados_reunion 
                    WHERE reunion_id = :reunion_id
                    AND estado = 'CONFIRMADO'
                )
                AND a.estado = 'LECTIVA'
                ORDER BY a.apellidos, a.nombres
            ");
            
            $stmt->execute([':reunion_id' => $reunion_id]);
            $lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar datos para el frontend
            foreach ($lideres as &$lider) {
                $lider['nombre_completo'] = trim($lider['nombres'] . ' ' . $lider['apellidos']);
                $lider['tiene_registro_facial'] = !empty($lider['facial_id']);
                $lider['descriptor'] = $lider['descriptor'] ? json_decode($lider['descriptor']) : null;
                
                // Estado para la UI
                if ($lider['tiene_registro_facial'] && $lider['facial_activo']) {
                    $lider['estado_biometrico'] = 'registrado';
                } else if ($lider['tiene_registro_facial'] && !$lider['facial_activo']) {
                    $lider['estado_biometrico'] = 'inactivo';
                } else {
                    $lider['estado_biometrico'] = 'no_registrado';
                }
            }
            
            echo json_encode([
                'success' => true,
                'lideres' => $lideres,
                'total' => count($lideres)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener todos los registros faciales de líderes
     */
    public function obtenerRegistrosFaciales() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    fr.id,
                    fr.documento,
                    fr.descriptor,
                    fr.imagen_path,
                    fr.activo,
                    fr.fecha_registro,
                    a.nombres,
                    a.apellidos,
                    a.tipo_liderazgo,
                    a.ficha
                FROM facial_recognition fr
                INNER JOIN aprendices a ON fr.documento = a.numero_documento
                WHERE a.estado = 'LECTIVA'
                AND (a.tipo_liderazgo IS NOT NULL AND a.tipo_liderazgo != '')
                ORDER BY fr.fecha_registro DESC
            ");
            
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preparar datos para reconocimiento facial
            $faces_data = [];
            foreach ($registros as $registro) {
                if ($registro['activo'] && $registro['descriptor']) {
                    $faces_data[] = [
                        'id' => $registro['documento'],
                        'name' => trim($registro['nombres'] . ' ' . $registro['apellidos']),
                        'descriptor' => json_decode($registro['descriptor']),
                        'image' => $registro['imagen_path'],
                        'role' => $registro['tipo_liderazgo'],
                        'ficha' => $registro['ficha']
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'registros' => $registros,
                'faces_data' => $faces_data,
                'total' => count($faces_data)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Verificar si un líder tiene registro facial activo
     */
    public function verificarRegistroFacial($documento) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    fr.id,
                    fr.descriptor,
                    fr.imagen_path,
                    fr.activo,
                    fr.fecha_registro,
                    a.nombres,
                    a.apellidos,
                    a.tipo_liderazgo
                FROM facial_recognition fr
                INNER JOIN aprendices a ON fr.documento = a.numero_documento
                WHERE fr.documento = :documento
                AND fr.activo = true
                LIMIT 1
            ");
            
            $stmt->execute([':documento' => $documento]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registro) {
                echo json_encode([
                    'success' => true,
                    'tiene_registro' => true,
                    'registro' => [
                        'id' => $registro['id'],
                        'nombre' => trim($registro['nombres'] . ' ' . $registro['apellidos']),
                        'descriptor' => json_decode($registro['descriptor']),
                        'imagen' => $registro['imagen_path'],
                        'role' => $registro['tipo_liderazgo'],
                        'fecha_registro' => $registro['fecha_registro']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'tiene_registro' => false,
                    'message' => 'El líder no tiene registro facial activo'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Registrar asistencia biométrica
     */
    public function registrarAsistencia($data) {
        try {
            $reunion_id = $data['reunion_id'];
            $documento = $data['documento'];
            $metodo = $data['metodo'] ?? 'biometrico';
            $confianza = $data['confianza'] ?? null;
            
            // Verificar que el líder está convocado
            $stmt = $this->conn->prepare("
                SELECT cr.id, a.nombres, a.apellidos
                FROM convocados_reunion cr
                INNER JOIN aprendices a ON cr.aprendiz_id = a.id
                WHERE cr.reunion_id = :reunion_id
                AND a.numero_documento = :documento
                AND cr.estado = 'CONFIRMADO'
                LIMIT 1
            ");
            
            $stmt->execute([
                ':reunion_id' => $reunion_id,
                ':documento' => $documento
            ]);
            
            $convocado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$convocado) {
                throw new Exception('El líder no está convocado a esta reunión');
            }
            
            // Verificar si ya registró asistencia
            $stmt = $this->conn->prepare("
                SELECT id FROM asistencia_reunion 
                WHERE reunion_id = :reunion_id 
                AND aprendiz_documento = :documento
                LIMIT 1
            ");
            
            $stmt->execute([
                ':reunion_id' => $reunion_id,
                ':documento' => $documento
            ]);
            
            if ($stmt->fetch()) {
                throw new Exception('El líder ya registró su asistencia');
            }
            
            // Registrar asistencia
            $stmt = $this->conn->prepare("
                INSERT INTO asistencia_reunion (
                    reunion_id, aprendiz_documento, metodo_registro,
                    hora_entrada, confianza, fecha_registro
                ) VALUES (
                    :reunion_id, :documento, :metodo,
                    CURRENT_TIME(), :confianza, NOW()
                )
            ");
            
            $stmt->execute([
                ':reunion_id' => $reunion_id,
                ':documento' => $documento,
                ':metodo' => $metodo,
                ':confianza' => $confianza
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'nombre' => trim($convocado['nombres'] . ' ' . $convocado['apellidos']),
                'documento' => $documento,
                'hora' => date('H:i:s'),
                'metodo' => $metodo
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener asistencia de una reunión
     */
    public function obtenerAsistenciaReunion($reunion_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ar.id,
                    ar.aprendiz_documento,
                    ar.metodo_registro,
                    ar.hora_entrada,
                    ar.confianza,
                    ar.fecha_registro,
                    a.nombres,
                    a.apellidos,
                    a.tipo_liderazgo,
                    a.ficha
                FROM asistencia_reunion ar
                INNER JOIN aprendices a ON ar.aprendiz_documento = a.numero_documento
                WHERE ar.reunion_id = :reunion_id
                ORDER BY ar.hora_entrada
            ");
            
            $stmt->execute([':reunion_id' => $reunion_id]);
            $asistencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($asistencia as &$registro) {
                $registro['nombre_completo'] = trim($registro['nombres'] . ' ' . $registro['apellidos']);
            }
            
            echo json_encode([
                'success' => true,
                'asistencia' => $asistencia,
                'total' => count($asistencia)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Sincronizar registros faciales del sistema principal
     */
    public function sincronizarRegistrosFaciales() {
        try {
            // Obtener todos los líderes con registros faciales
            $stmt = $this->conn->prepare("
                SELECT 
                    fr.documento,
                    fr.descriptor,
                    fr.imagen_path,
                    fr.activo,
                    a.nombres,
                    a.apellidos,
                    a.tipo_liderazgo
                FROM facial_recognition fr
                INNER JOIN aprendices a ON fr.documento = a.numero_documento
                WHERE a.estado = 'LECTIVA'
                AND (a.tipo_liderazgo IS NOT NULL AND a.tipo_liderazgo != '')
                AND fr.activo = true
            ");
            
            $stmt->execute();
            $lideres = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preparar datos para el sistema de reconocimiento
            $faces_data = [];
            foreach ($lideres as $lider) {
                $faces_data[] = [
                    'id' => $lider['documento'],
                    'name' => trim($lider['nombres'] . ' ' . $lider['apellidos']),
                    'descriptor' => json_decode($lider['descriptor']),
                    'image' => $lider['imagen_path'],
                    'role' => $lider['tipo_liderazgo']
                ];
            }
            
            // Guardar en archivo JSON para el reconocimiento facial
            $json_file = __DIR__ . '/../data/lideres-faciales.json';
            $json_dir = dirname($json_file);
            
            if (!is_dir($json_dir)) {
                mkdir($json_dir, 0755, true);
            }
            
            file_put_contents($json_file, json_encode([
                'faces' => $faces_data,
                'updated' => date('Y-m-d H:i:s'),
                'total' => count($faces_data)
            ], JSON_PRETTY_PRINT));
            
            echo json_encode([
                'success' => true,
                'message' => 'Registros faciales sincronizados',
                'total_lideres' => count($lideres),
                'archivo_generado' => $json_file
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
$api = new BiometricoLideresAPI();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'lideresReunion':
                $reunion_id = $_GET['reunion_id'] ?? null;
                if ($reunion_id) {
                    $api->obtenerLideresReunion($reunion_id);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID de reunión requerido']);
                }
                break;
            case 'registrosFaciales':
                $api->obtenerRegistrosFaciales();
                break;
            case 'verificarRegistro':
                $documento = $_GET['documento'] ?? null;
                if ($documento) {
                    $api->verificarRegistroFacial($documento);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Documento requerido']);
                }
                break;
            case 'asistenciaReunion':
                $reunion_id = $_GET['reunion_id'] ?? null;
                if ($reunion_id) {
                    $api->obtenerAsistenciaReunion($reunion_id);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID de reunión requerido']);
                }
                break;
            case 'sincronizar':
                $api->sincronizarRegistrosFaciales();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    case 'POST':
        switch ($action) {
            case 'registrarAsistencia':
                $data = json_decode(file_get_contents('php://input'), true);
                $api->registrarAsistencia($data);
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
