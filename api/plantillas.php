<?php
/**
 * API PARA GESTIÓN DE PLANTILLAS PERSONALIZABLES
 * Crear, editar, eliminar y usar plantillas de mensajes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

class PlantillasAPI {
    private $conn;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Obtener todas las plantillas
     */
    public function obtenerPlantillas() {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM plantillas_mensajes 
                WHERE activo = true 
                ORDER BY nombre, medio, tipo
            ");
            $stmt->execute();
            $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar variables JSON
            foreach ($plantillas as &$plantilla) {
                $plantilla['variables'] = json_decode($plantilla['variables'] ?: '[]', true);
            }
            
            echo json_encode([
                'success' => true,
                'plantillas' => $plantillas
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Crear nueva plantilla
     */
    public function crearPlantilla() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos requeridos
            $required = ['nombre', 'tipo', 'medio', 'contenido'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo $field es requerido");
                }
            }
            
            // Extraer variables del contenido
            $variables = $this->extraerVariables($data['contenido']);
            
            // Insertar plantilla
            $stmt = $this->conn->prepare("
                INSERT INTO plantillas_mensajes (
                    nombre, tipo, medio, asunto, contenido, 
                    variables, activo, creado_por, fecha_creacion
                ) VALUES (
                    :nombre, :tipo, :medio, :asunto, :contenido,
                    :variables, true, :creado_por, NOW()
                )
            ");
            
            $stmt->execute([
                ':nombre' => $data['nombre'],
                ':tipo' => $data['tipo'],
                ':medio' => $data['medio'],
                ':asunto' => $data['asunto'] ?? '',
                ':contenido' => $data['contenido'],
                ':variables' => json_encode($variables),
                ':creado_por' => $data['creado_por'] ?? 'sistema'
            ]);
            
            $plantilla_id = $this->conn->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'plantilla_id' => $plantilla_id,
                'message' => 'Plantilla creada exitosamente',
                'variables_encontradas' => $variables
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Actualizar plantilla
     */
    public function actualizarPlantilla() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $plantilla_id = $data['id'] ?? null;
            if (!$plantilla_id) {
                throw new Exception("ID de plantilla requerido");
            }
            
            // Extraer variables del contenido
            $variables = $this->extraerVariables($data['contenido']);
            
            $stmt = $this->conn->prepare("
                UPDATE plantillas_mensajes SET 
                    nombre = :nombre,
                    tipo = :tipo,
                    medio = :medio,
                    asunto = :asunto,
                    contenido = :contenido,
                    variables = :variables,
                    fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $plantilla_id,
                ':nombre' => $data['nombre'],
                ':tipo' => $data['tipo'],
                ':medio' => $data['medio'],
                ':asunto' => $data['asunto'] ?? '',
                ':contenido' => $data['contenido'],
                ':variables' => json_encode($variables)
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Plantilla actualizada exitosamente',
                'variables_encontradas' => $variables
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Eliminar plantilla (desactivar)
     */
    public function eliminarPlantilla() {
        try {
            $plantilla_id = $_GET['id'] ?? null;
            if (!$plantilla_id) {
                throw new Exception("ID de plantilla requerido");
            }
            
            $stmt = $this->conn->prepare("
                UPDATE plantillas_mensajes 
                SET activo = false, fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([':id' => $plantilla_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Plantilla eliminada exitosamente'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener plantilla por ID
     */
    public function obtenerPlantillaPorId() {
        try {
            $plantilla_id = $_GET['id'] ?? null;
            if (!$plantilla_id) {
                throw new Exception("ID de plantilla requerido");
            }
            
            $stmt = $this->conn->prepare("
                SELECT * FROM plantillas_mensajes 
                WHERE id = :id AND activo = true
            ");
            $stmt->execute([':id' => $plantilla_id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plantilla) {
                throw new Exception("Plantilla no encontrada");
            }
            
            $plantilla['variables'] = json_decode($plantilla['variables'] ?: '[]', true);
            
            echo json_encode([
                'success' => true,
                'plantilla' => $plantilla
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Previsualizar plantilla con datos
     */
    public function previsualizarPlantilla() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $plantilla_id = $data['plantilla_id'] ?? null;
            $variables_data = $data['variables'] ?? [];
            
            if (!$plantilla_id) {
                throw new Exception("ID de plantilla requerido");
            }
            
            // Obtener plantilla
            $stmt = $this->conn->prepare("
                SELECT * FROM plantillas_mensajes 
                WHERE id = :id AND activo = true
            ");
            $stmt->execute([':id' => $plantilla_id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plantilla) {
                throw new Exception("Plantilla no encontrada");
            }
            
            // Reemplazar variables
            $contenido_procesado = $this->procesarPlantilla($plantilla['contenido'], $variables_data);
            $asunto_procesado = $this->procesarPlantilla($plantilla['asunto'], $variables_data);
            
            echo json_encode([
                'success' => true,
                'preview' => [
                    'asunto' => $asunto_procesado,
                    'contenido' => $contenido_procesado,
                    'medio' => $plantilla['medio'],
                    'tipo' => $plantilla['tipo']
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
     * Aplicar plantilla a convocatoria
     */
    public function aplicarPlantilla() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $plantilla_id = $data['plantilla_id'] ?? null;
            $destinatarios = $data['destinatarios'] ?? [];
            $variables_data = $data['variables'] ?? [];
            
            if (!$plantilla_id || empty($destinatarios)) {
                throw new Exception("ID de plantilla y destinatarios son requeridos");
            }
            
            // Obtener plantilla
            $stmt = $this->conn->prepare("
                SELECT * FROM plantillas_mensajes 
                WHERE id = :id AND activo = true
            ");
            $stmt->execute([':id' => $plantilla_id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plantilla) {
                throw new Exception("Plantilla no encontrada");
            }
            
            // Procesar mensajes para cada destinatario
            $resultados = [];
            foreach ($destinatarios as $destinatario) {
                // Combinar variables del destinatario con las variables adicionales
                $variables_combinadas = array_merge($variables_data, $destinatario);
                
                // Procesar plantilla
                $contenido_procesado = $this->procesarPlantilla($plantilla['contenido'], $variables_combinadas);
                $asunto_procesado = $this->procesarPlantilla($plantilla['asunto'], $variables_combinadas);
                
                $resultados[] = [
                    'destinatario' => $destinatario,
                    'asunto' => $asunto_procesado,
                    'contenido' => $contenido_procesado,
                    'medio' => $plantilla['medio'],
                    'tipo' => $plantilla['tipo']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'mensajes_generados' => $resultados,
                'plantilla_utilizada' => $plantilla['nombre']
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Extraer variables del contenido
     */
    private function extraerVariables($contenido) {
        preg_match_all('/\{\{(\w+)\}\}/', $contenido, $matches);
        return array_unique($matches[1]);
    }
    
    /**
     * Procesar plantilla reemplazando variables
     */
    private function procesarPlantilla($contenido, $variables) {
        foreach ($variables as $key => $value) {
            $contenido = str_replace('{{' . $key . '}}', $value, $contenido);
        }
        return $contenido;
    }
    
    /**
     * Crear plantillas por defecto
     */
    public function crearPlantillasDefecto() {
        try {
            $plantillas_defecto = [
                [
                    'nombre' => 'Convocatoria WhatsApp Voceros',
                    'tipo' => 'convocatoria_voceros',
                    'medio' => 'whatsapp',
                    'asunto' => '',
                    'contenido' => '🎭 ¡CONVOCATORIA REUNIÓN DE VOCEROS!

👋 Hola {{nombre_destinatario}}

📋 ESTÁS CONVOCADO/A a:
🎯 {{titulo_reunion}}

📅 Fecha: {{fecha_reunion}}
⏰ Hora: {{hora_reunion}}
📍 Lugar: {{lugar_reunion}}
🎭 Tu rol: {{rol_vocero}}
📚 Ficha: {{numero_ficha}}

✅ IMPORTANTE:
Tu instructor debe autorizar tu asistencia.
Espera el oficio oficial para confirmar.

🏢 SENA - Centro Tecnológico de la Amazonia
🔖 ID: VOC-{{documento_destinatario}}-{{fecha_actual}}'
                ],
                [
                    'nombre' => 'Recordatorio SMS Voceros',
                    'tipo' => 'recordatorio_voceros',
                    'medio' => 'sms',
                    'asunto' => '',
                    'contenido' => 'SENA: Convocatoria reunion {{rol_vocero}} {{nombre_destinatario}}. Fecha: {{fecha_reunion}} {{hora_reunion}}. Lugar: {{lugar_reunion}}. Ficha: {{numero_ficha}}. ID: VOC-{{documento_destinatario}}-{{fecha_actual}}'
                ],
                [
                    'nombre' => 'Oficio Correo Voceros',
                    'tipo' => 'oficio_voceros',
                    'medio' => 'email',
                    'asunto' => 'OFICIO DE CONVOCATORIA - {{rol_vocero}} - {{nombre_destinatario}}',
                    'contenido' => '<div style="font-family: Arial; max-width: 800px; margin: 0 auto;">
<div style="text-align: center; padding: 20px; border-bottom: 2px solid #0066cc;">
<h1 style="color: #0066cc; margin: 0;">OFICIO DE CONVOCATORIA</h1>
<p style="margin: 5px 0;">SERVICIO NACIONAL DE APRENDIZAJE - SENA</p>
<p style="margin: 5px 0;">Centro Tecnológico de la Amazonia</p>
</div>
<div style="padding: 30px;">
<p><strong>Fecha:</strong> {{fecha_actual}}</p>
<p><strong>Para:</strong> {{nombre_destinatario}}</p>
<p><strong>De:</strong> Coordinación de Bienestar</p>
<p><strong>Asunto:</strong> CONVOCATORIA REUNIÓN DE VOCEROS</p><br>
<p>Estimado/a {{nombre_destinatario}},</p>
<p>Por medio de la presente se le convoca formalmente a la reunión de voceros programada.</p>
<div style="background: #f8f9fa; padding: 20px; border-left: 4px solid #0066cc; margin: 20px 0;">
<h3 style="color: #0066cc; margin-top: 0;">DATOS DE LA REUNIÓN</h3>
<p><strong>Título:</strong> {{titulo_reunion}}</p>
<p><strong>Fecha:</strong> {{fecha_reunion}}</p>
<p><strong>Hora:</strong> {{hora_reunion}}</p>
<p><strong>Lugar:</strong> {{lugar_reunion}}</p>
<p><strong>Ficha:</strong> {{numero_ficha}}</p>
<p><strong>Tu rol:</strong> {{rol_vocero}}</p>
</div>
<p>Se requiere su puntual asistencia y participación activa en esta reunión.</p>
<p>Atentamente,</p>
<p><strong>Coordinación de Bienestar</strong><br>
SENA - Centro Tecnológico de la Amazonia</p>
</div>
<div style="text-align: center; padding: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
<p>Servicio Nacional de Aprendizaje - SENA</p>
<p>Centro Tecnológico de la Amazonia</p>
<p>Sistema de Gestión SenApre - Oficio No. {{fecha_actual}}-001</p>
<p>Generado: {{fecha_hora_actual}}</p>
</div>
</div>'
                ]
            ];
            
            foreach ($plantillas_defecto as $plantilla) {
                $variables = $this->extraerVariables($plantilla['contenido']);
                
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO plantillas_mensajes (
                        nombre, tipo, medio, asunto, contenido, 
                        variables, activo, creado_por, fecha_creacion
                    ) VALUES (
                        :nombre, :tipo, :medio, :asunto, :contenido,
                        :variables, true, 'sistema', NOW()
                    )
                ");
                
                $stmt->execute([
                    ':nombre' => $plantilla['nombre'],
                    ':tipo' => $plantilla['tipo'],
                    ':medio' => $plantilla['medio'],
                    ':asunto' => $plantilla['asunto'],
                    ':contenido' => $plantilla['contenido'],
                    ':variables' => json_encode($variables)
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Plantillas por defecto creadas exitosamente',
                'cantidad' => count($plantillas_defecto)
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
$api = new PlantillasAPI();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'obtenerPlantillas':
                $api->obtenerPlantillas();
                break;
            case 'obtenerPlantilla':
                $api->obtenerPlantillaPorId();
                break;
            case 'eliminarPlantilla':
                $api->eliminarPlantilla();
                break;
            case 'crearPlantillasDefecto':
                $api->crearPlantillasDefecto();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    case 'POST':
        switch ($action) {
            case 'crearPlantilla':
                $api->crearPlantilla();
                break;
            case 'actualizarPlantilla':
                $api->actualizarPlantilla();
                break;
            case 'previsualizarPlantilla':
                $api->previsualizarPlantilla();
                break;
            case 'aplicarPlantilla':
                $api->aplicarPlantilla();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
