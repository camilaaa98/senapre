<?php
/**
 * API PARA GESTIÓN DE FIRMAS DIGITALES
 * Subir, guardar y aplicar firmas en comunicaciones
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

class FirmaDigitalAPI {
    private $conn;
    private $uploadDir = 'assets/firmas/';
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
        
        // Crear directorio de firmas si no existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Subir firma digital
     */
    public function subirFirma() {
        try {
            // Validar que se haya subido un archivo
            if (!isset($_FILES['firma']) || $_FILES['firma']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error al subir el archivo de firma");
            }
            
            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['firma']['type'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP");
            }
            
            // Validar tamaño (máximo 2MB)
            $maxSize = 2 * 1024 * 1024; // 2MB
            if ($_FILES['firma']['size'] > $maxSize) {
                throw new Exception("El archivo es demasiado grande. Máximo 2MB");
            }
            
            // Obtener datos del formulario
            $usuario_id = $_POST['usuario_id'] ?? null;
            $nombre_completo = $_POST['nombre_completo'] ?? '';
            $cargo = $_POST['cargo'] ?? '';
            
            if (!$usuario_id || !$nombre_completo) {
                throw new Exception("Faltan datos requeridos");
            }
            
            // Procesar archivo
            $file = $_FILES['firma'];
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'firma_' . $usuario_id . '_' . time() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception("Error al guardar el archivo");
            }
            
            // Convertir a base64 como respaldo
            $base64 = base64_encode(file_get_contents($filepath));
            
            // Desactivar firmas anteriores del usuario
            $stmt = $this->conn->prepare("
                UPDATE firmas_digitales 
                SET activa = false 
                WHERE usuario_id = :usuario_id
            ");
            $stmt->execute([':usuario_id' => $usuario_id]);
            
            // Insertar nueva firma
            $stmt = $this->conn->prepare("
                INSERT INTO firmas_digitales (
                    usuario_id, nombre_completo, cargo, 
                    firma_imagen_path, firma_base64, activa
                ) VALUES (
                    :usuario_id, :nombre_completo, :cargo,
                    :firma_imagen_path, :firma_base64, true
                )
            ");
            
            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':nombre_completo' => $nombre_completo,
                ':cargo' => $cargo,
                ':firma_imagen_path' => $filepath,
                ':firma_base64' => $base64
            ]);
            
            $firma_id = $this->conn->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'firma_id' => $firma_id,
                'message' => 'Firma digital guardada exitosamente',
                'firma_url' => $filepath
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener firma activa de un usuario
     */
    public function obtenerFirmaActiva() {
        try {
            $usuario_id = $_GET['usuario_id'] ?? null;
            
            if (!$usuario_id) {
                throw new Exception("ID de usuario requerido");
            }
            
            $stmt = $this->conn->prepare("
                SELECT * FROM firmas_digitales 
                WHERE usuario_id = :usuario_id AND activa = true
                ORDER BY fecha_creacion DESC
                LIMIT 1
            ");
            $stmt->execute([':usuario_id' => $usuario_id]);
            $firma = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$firma) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No hay firma activa para este usuario'
                ]);
                return;
            }
            
            // Convertir imagen a base64 para mostrar
            if (file_exists($firma['firma_imagen_path'])) {
                $imageData = base64_encode(file_get_contents($firma['firma_imagen_path']));
                $mimeType = mime_content_type($firma['firma_imagen_path']);
                $firma['firma_base64'] = "data:$mimeType;base64,$imageData";
            }
            
            echo json_encode([
                'success' => true,
                'firma' => $firma
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener todas las firmas de un usuario
     */
    public function obtenerFirmasUsuario() {
        try {
            $usuario_id = $_GET['usuario_id'] ?? null;
            
            if (!$usuario_id) {
                throw new Exception("ID de usuario requerido");
            }
            
            $stmt = $this->conn->prepare("
                SELECT * FROM firmas_digitales 
                WHERE usuario_id = :usuario_id
                ORDER BY fecha_creacion DESC
            ");
            $stmt->execute([':usuario_id' => $usuario_id]);
            $firmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir imágenes a base64
            foreach ($firmas as &$firma) {
                if (file_exists($firma['firma_imagen_path'])) {
                    $imageData = base64_encode(file_get_contents($firma['firma_imagen_path']));
                    $mimeType = mime_content_type($firma['firma_imagen_path']);
                    $firma['firma_base64'] = "data:$mimeType;base64,$imageData";
                }
            }
            
            echo json_encode([
                'success' => true,
                'firmas' => $firmas
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Activar una firma específica
     */
    public function activarFirma() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $firma_id = $data['firma_id'] ?? null;
            $usuario_id = $data['usuario_id'] ?? null;
            
            if (!$firma_id || !$usuario_id) {
                throw new Exception("ID de firma y usuario requeridos");
            }
            
            // Desactivar todas las firmas del usuario
            $stmt = $this->conn->prepare("
                UPDATE firmas_digitales 
                SET activa = false 
                WHERE usuario_id = :usuario_id
            ");
            $stmt->execute([':usuario_id' => $usuario_id]);
            
            // Activar la firma seleccionada
            $stmt = $this->conn->prepare("
                UPDATE firmas_digitales 
                SET activa = true, fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id = :firma_id AND usuario_id = :usuario_id
            ");
            $stmt->execute([
                ':firma_id' => $firma_id,
                ':usuario_id' => $usuario_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Firma activada exitosamente'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Eliminar una firma
     */
    public function eliminarFirma() {
        try {
            $firma_id = $_GET['firma_id'] ?? null;
            $usuario_id = $_GET['usuario_id'] ?? null;
            
            if (!$firma_id || !$usuario_id) {
                throw new Exception("ID de firma y usuario requeridos");
            }
            
            // Obtener datos de la firma
            $stmt = $this->conn->prepare("
                SELECT * FROM firmas_digitales 
                WHERE id = :firma_id AND usuario_id = :usuario_id
            ");
            $stmt->execute([
                ':firma_id' => $firma_id,
                ':usuario_id' => $usuario_id
            ]);
            $firma = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$firma) {
                throw new Exception("Firma no encontrada");
            }
            
            // No permitir eliminar si es la única firma activa
            if ($firma['activa']) {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as total FROM firmas_digitales 
                    WHERE usuario_id = :usuario_id
                ");
                $stmt->execute([':usuario_id' => $usuario_id]);
                $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                if ($total <= 1) {
                    throw new Exception("No puede eliminar la única firma del usuario");
                }
            }
            
            // Eliminar archivo físico
            if (file_exists($firma['firma_imagen_path'])) {
                unlink($firma['firma_imagen_path']);
            }
            
            // Eliminar registro de base de datos
            $stmt = $this->conn->prepare("
                DELETE FROM firmas_digitales 
                WHERE id = :firma_id AND usuario_id = :usuario_id
            ");
            $stmt->execute([
                ':firma_id' => $firma_id,
                ':usuario_id' => $usuario_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Firma eliminada exitosamente'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generar HTML de firma para documentos
     */
    public function generarHTMLFirma($usuario_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM firmas_digitales 
                WHERE usuario_id = :usuario_id AND activa = true
                LIMIT 1
            ");
            $stmt->execute([':usuario_id' => $usuario_id]);
            $firma = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$firma) {
                return '';
            }
            
            $firma_url = '';
            if (file_exists($firma['firma_imagen_path'])) {
                $firma_url = $firma['firma_imagen_path'];
            }
            
            $html = '<div style="text-align: center; margin: 30px 0;">';
            $html .= '<div style="margin-bottom: 10px;">';
            if ($firma_url) {
                $html .= '<img src="' . $firma_url . '" style="max-height: 60px; max-width: 200px;" alt="Firma">';
            }
            $html .= '</div>';
            $html .= '<div style="border-bottom: 1px solid #333; width: 250px; margin: 0 auto;"></div>';
            $html .= '<div style="margin-top: 5px; font-weight: bold;">' . htmlspecialchars($firma['nombre_completo']) . '</div>';
            if ($firma['cargo']) {
                $html .= '<div style="font-size: 0.9em; color: #666;">' . htmlspecialchars($firma['cargo']) . '</div>';
            }
            $html .= '</div>';
            
            return $html;
            
        } catch (Exception $e) {
            return '';
        }
    }
}

// Manejo de solicitudes
$api = new FirmaDigitalAPI();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'subirFirma':
                $api->subirFirma();
                break;
            case 'activarFirma':
                $api->activarFirma();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    case 'GET':
        switch ($action) {
            case 'obtenerFirmaActiva':
                $api->obtenerFirmaActiva();
                break;
            case 'obtenerFirmasUsuario':
                $api->obtenerFirmasUsuario();
                break;
            case 'eliminarFirma':
                $api->eliminarFirma();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
