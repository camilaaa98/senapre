<?php
/**
 * API DE NOTIFICACIONES PARA VOCEROS Y REPRESENTANTES
 * Sistema de convocatorias inteligentes multi-canal
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

class NotificacionesAPI {
    private $conn;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Crear nueva convocatoria de reunión
     */
    public function crearConvocatoria() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Validar datos requeridos
            if (!isset($data['titulo']) || !isset($data['fecha']) || !isset($data['hora'])) {
                throw new Exception("Faltan datos requeridos");
            }
            
            // Insertar convocatoria
            $stmt = $this->conn->prepare("
                INSERT INTO convocatorias_reunion (
                    titulo, fecha, hora, lugar, tipo, agenda, descripcion, 
                    fichas_invitadas, tipo_envio, creado_por, fecha_creacion
                ) VALUES (
                    :titulo, :fecha, :hora, :lugar, :tipo, :agenda, :descripcion,
                    :fichas_invitadas, :tipo_envio, :creado_por, NOW()
                )
            ");
            
            $stmt->execute([
                ':titulo' => $data['titulo'],
                ':fecha' => $data['fecha'],
                ':hora' => $data['hora'],
                ':lugar' => $data['lugar'] ?? 'Auditorio Principal',
                ':tipo' => $data['tipo'] ?? 'Ordinaria',
                ':agenda' => json_encode($data['agenda'] ?? []),
                ':descripcion' => $data['descripcion'] ?? '',
                ':fichas_invitadas' => json_encode($data['fichas'] ?? []),
                ':tipo_envio' => $data['tipo_envio'] ?? 'grupal', // grupal o individual
                ':creado_por' => $data['creado_por'] ?? 'sistema'
            ]);
            
            $convocatoria_id = $this->conn->lastInsertId();
            
            // Obtener voceros para esta convocatoria
            $voceros = $this->obtenerVocerosParaConvocatoria($data['fichas'] ?? [], $data['tipo_envio'] ?? 'grupal', $data['destinatarios_individuales'] ?? []);
            
            // Enviar notificaciones formales e informaciones
            $this->procesarEnvioConvocatoria($convocatoria_id, $voceros, $data);
            
            echo json_encode([
                'success' => true,
                'convocatoria_id' => $convocatoria_id,
                'voceros_notificados' => count($voceros),
                'tipo_envio' => $data['tipo_envio'] ?? 'grupal',
                'message' => 'Convocatoria creada y notificaciones enviadas'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtener voceros para convocatoria (con soporte grupal/individual)
     */
    private function obtenerVocerosParaConvocatoria($fichas, $tipo_envio = 'grupal', $destinatarios_individuales = []) {
        $voceros = [];
        
        if ($tipo_envio === 'individual' && !empty($destinatarios_individuales)) {
            // Envío individual: obtener solo los destinatarios específicos
            foreach ($destinatarios_individuales as $destinatario) {
                if (isset($destinatario['tipo']) && $destinatario['tipo'] === 'instructor') {
                    // Obtener instructor específico
                    $stmt = $this->conn->prepare("
                        SELECT u.*, 'instructor' as rol_vocero, 
                               NULL as numero_ficha, NULL as documento
                        FROM usuarios u
                        WHERE u.id_usuario = :id_usuario 
                        AND u.rol IN ('instructor', 'director')
                    ");
                    $stmt->execute([':id_usuario' => $destinatario['id']]);
                    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($instructor) {
                        $voceros[] = $instructor;
                    }
                } elseif (isset($destinatario['tipo']) && $destinatario['tipo'] === 'vocero') {
                    // Obtener vocero específico
                    $stmt = $this->conn->prepare("
                        SELECT a.*, f.numero_ficha, f.instructor_lider,
                               CASE 
                                   WHEN f.vocero_principal = a.documento THEN 'Vocero Principal'
                                   WHEN f.vocero_suplente = a.documento THEN 'Vocero Suplente'
                                   ELSE 'Representante'
                               END as rol_vocero
                        FROM aprendices a
                        JOIN fichas f ON a.numero_ficha = f.numero_ficha
                        WHERE a.id_aprendiz = :id_aprendiz
                        AND a.estado = 'LECTIVA'
                        LIMIT 1
                    ");
                    $stmt->execute([':id_aprendiz' => $destinatario['id']]);
                    $vocero = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($vocero) {
                        $voceros[] = $vocero;
                    }
                }
            }
        } else {
            // Envío grupal: obtener todos los voceros de las fichas
            if (empty($fichas)) {
                // Obtener todas las fichas activas
                $stmt = $this->conn->prepare("
                    SELECT DISTINCT numero_ficha 
                    FROM fichas 
                    WHERE estado = 'ACTIVA'
                ");
                $stmt->execute();
                $fichas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            foreach ($fichas as $ficha) {
                // Obtener vocero principal
                $stmt = $this->conn->prepare("
                    SELECT a.*, f.numero_ficha, f.instructor_lider,
                           'Vocero Principal' as rol_vocero
                    FROM aprendices a
                    JOIN fichas f ON a.numero_ficha = f.numero_ficha
                    WHERE f.numero_ficha = :ficha 
                    AND f.vocero_principal = a.documento
                    AND a.estado = 'LECTIVA'
                    LIMIT 1
                ");
                $stmt->execute([':ficha' => $ficha]);
                $principal = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($principal) {
                    $voceros[] = $principal;
                }
                
                // Obtener vocero suplente
                $stmt = $this->conn->prepare("
                    SELECT a.*, f.numero_ficha, f.instructor_lider,
                           'Vocero Suplente' as rol_vocero
                    FROM aprendices a
                    JOIN fichas f ON a.numero_ficha = f.numero_ficha
                    WHERE f.numero_ficha = :ficha 
                    AND f.vocero_suplente = a.documento
                    AND a.estado = 'LECTIVA'
                    LIMIT 1
                ");
                $stmt->execute([':ficha' => $ficha]);
                $suplente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($suplente) {
                    $voceros[] = $suplente;
                }
            }
        }
        
        return $voceros;
    }
    
    /**
     * Procesar envío de convocatoria (formal + informal)
     */
    private function procesarEnvioConvocatoria($convocatoria_id, $voceros, $data) {
        // Agrupar voceros por instructor para envío formal
        $instructores = [];
        foreach ($voceros as $vocero) {
            $instructor_lider = $vocero['instructor_lider'];
            if (!isset($instructores[$instructor_lider])) {
                $instructores[$instructor_lider] = [];
            }
            $instructores[$instructor_lider][] = $vocero;
        }
        
        // Enviar comunicaciones formales a instructores
        foreach ($instructores as $instructor_nombre => $voceros_instructor) {
            $this->enviarComunicacionesFormalesInstructor($convocatoria_id, $instructor_nombre, $voceros_instructor, $data);
        }
        
        // Enviar recordatorios informales a voceros
        foreach ($voceros as $vocero) {
            $this->enviarRecordatorioInformalVocero($convocatoria_id, $vocero, $data);
        }
    }
    
    /**
     * Enviar comunicaciones formales a instructor
     */
    private function enviarComunicacionesFormalesInstructor($convocatoria_id, $instructor_nombre, $voceros, $data) {
        // Obtener datos de la convocatoria
        $stmt = $this->conn->prepare("
            SELECT * FROM convocatorias_reunion WHERE id = :id
        ");
        $stmt->execute([':id' => $convocatoria_id]);
        $convocatoria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener datos del instructor
        $stmt = $this->conn->prepare("
            SELECT * FROM usuarios WHERE nombre LIKE :nombre AND rol IN ('instructor', 'director')
        ");
        $stmt->execute([':nombre' => "%$instructor_nombre%"]);
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$instructor) {
            return;
        }
        
        // 1. Generar oficio PDF formal
        $oficio_url = $this->generarOficioPDF($convocatoria, $instructor, $voceros);
        
        // 2. Enviar correo electrónico formal
        $this->enviarCorreoFormalInstructor($instructor, $voceros, $convocatoria, $oficio_url);
        
        // 3. Enviar WhatsApp informativo (no formal)
        $this->enviarWhatsAppInfoInstructor($instructor, $voceros, $convocatoria, $oficio_url);
        
        // 4. Registrar en base de datos
        $this->registrarNotificacion('correo_formal', $instructor['id_usuario'], 'instructor', $convocatoria_id, 
            "Oficio de autorización para voceros de la ficha {$voceros[0]['numero_ficha']}");
        
        $this->registrarNotificacion('whatsapp_info', $instructor['id_usuario'], 'instructor', $convocatoria_id, 
            "Información sobre convocatoria de voceros");
    }
    
    /**
     * Enviar recordatorio informal a vocero
     */
    private function enviarRecordatorioInformalVocero($convocatoria_id, $vocero, $data) {
        // Obtener datos de la convocatoria
        $stmt = $this->conn->prepare("
            SELECT * FROM convocatorias_reunion WHERE id = :id
        ");
        $stmt->execute([':id' => $convocatoria_id]);
        $convocatoria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 1. Enviar WhatsApp informal (recordatorio)
        $this->enviarWhatsAppRecordatorioVocero($vocero, $convocatoria);
        
        // 2. Enviar SMS si está configurado
        $this->enviarSMSRecordatorioVocero($vocero, $convocatoria);
        
        // 3. Registrar en base de datos
        $this->registrarNotificacion('whatsapp_recordatorio', $vocero['id_aprendiz'], 'vocero', $convocatoria_id, 
            "Recordatorio de convocatoria para {$vocero['nombre']}");
    }
    
    /**
     * Generar oficio PDF formal
     */
    private function generarOficioPDF($convocatoria, $instructor, $voceros) {
        // URL del oficio formal
        $oficio_url = "http://senapre.sena.edu.co/oficios/autorizar-vocero.php?convocatoria={$convocatoria['id']}&ficha={$voceros[0]['numero_ficha']}";
        
        // Aquí iría la generación real del PDF usando una librería como DOMPDF
        // Por ahora, devolvemos la URL del formulario
        return $oficio_url;
    }
    
    /**
     * Enviar correo formal a instructor
     */
    private function enviarCorreoFormalInstructor($instructor, $voceros, $convocatoria, $oficio_url) {
        $asunto = "OFICIO OFICIAL - SOLICITUD AUTORIZACIÓN VOCEROS - Reunión {$convocatoria['fecha']}";
        
        $html = $this->generarPlantillaCorreoFormal($instructor, $voceros, $convocatoria, $oficio_url);
        
        // Aquí iría el envío real con PHPMailer
        error_log("CORREO FORMAL enviado a instructor {$instructor['nombre']}: $asunto");
        
        return true;
    }
    
    /**
     * Enviar WhatsApp informativo a instructor
     */
    private function enviarWhatsAppInfoInstructor($instructor, $voceros, $convocatoria, $oficio_url) {
        $mensaje = $this->generarMensajeWhatsAppInfo($instructor, $voceros, $convocatoria, $oficio_url);
        
        // Aquí iría el envío real con WhatsApp API
        error_log("WHATSAPP INFO enviado a instructor {$instructor['nombre']}: " . substr($mensaje, 0, 100) . "...");
        
        return true;
    }
    
    /**
     * Enviar WhatsApp recordatorio a vocero
     */
    private function enviarWhatsAppRecordatorioVocero($vocero, $convocatoria) {
        $mensaje = $this->generarMensajeWhatsAppRecordatorio($vocero, $convocatoria);
        
        // Aquí iría el envío real con WhatsApp API
        error_log("WHATSAPP RECORDATORIO enviado a vocero {$vocero['nombre']}: " . substr($mensaje, 0, 100) . "...");
        
        return true;
    }
    
    /**
     * Enviar SMS recordatorio a vocero
     */
    private function enviarSMSRecordatorioVocero($vocero, $convocatoria) {
        $mensaje = "SENA: Recordatorio reunión {$vocero['rol_vocero']} {$vocero['nombre']}. Fecha: {$convocatoria['fecha']} {$convocatoria['hora']}. Lugar: {$convocatoria['lugar']}.";
        
        // Aquí iría el envío real con SMS API
        error_log("SMS enviado a vocero {$vocero['nombre']}: $mensaje");
        
        return true;
    }
    
    /**
     * Generar plantilla de correo formal
     */
    private function generarPlantillaCorreoFormal($instructor, $voceros, $convocatoria, $oficio_url) {
        $html = "<div style='font-family: Arial; max-width: 800px; margin: 0 auto;'>";
        $html .= "<div style='text-align: center; padding: 20px; border-bottom: 2px solid #0066cc;'>";
        $html .= "<h1 style='color: #0066cc; margin: 0;'>OFICIO OFICIAL</h1>";
        $html .= "<p style='margin: 5px 0;'>SERVICIO NACIONAL DE APRENDIZAJE - SENA</p>";
        $html .= "<p style='margin: 5px 0;'>Centro de Teleinformática y Producción Industrial</p>";
        $html .= "</div>";
        
        $html .= "<div style='padding: 30px;'>";
        $html .= "<p><strong>Fecha:</strong> " . date('d/m/Y') . "</p>";
        $html .= "<p><strong>Para:</strong> {$instructor['nombre']}</p>";
        $html .= "<p><strong>De:</strong> Coordinación de Bienestar</p>";
        $html .= "<p><strong>Asunto:</strong> SOLICITUD OFICIO AUTORIZACIÓN VOCEROS</p><br>";
        
        $html .= "<p>Estimado Instructor {$instructor['nombre']},</p>";
        $html .= "<p>Por medio de la presente se solicita formalmente su autorización para la asistencia del vocero del grupo a la reunión programada.</p>";
        
        $html .= "<div style='background: #f8f9fa; padding: 20px; border-left: 4px solid #0066cc; margin: 20px 0;'>";
        $html .= "<h3 style='color: #0066cc; margin-top: 0;'>DATOS DE LA REUNIÓN</h3>";
        $html .= "<p><strong>Título:</strong> {$convocatoria['titulo']}</p>";
        $html .= "<p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($convocatoria['fecha'])) . "</p>";
        $html .= "<p><strong>Hora:</strong> {$convocatoria['hora']}</p>";
        $html .= "<p><strong>Lugar:</strong> {$convocatoria['lugar']}</p>";
        $html .= "<p><strong>Ficha:</strong> {$voceros[0]['numero_ficha']}</p>";
        $html .= "</div>";
        
        $html .= "<div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
        $html .= "<h3 style='color: #856404; margin-top: 0;'>VOCEROS DESIGNADOS</h3>";
        foreach ($voceros as $vocero) {
            $html .= "<p><strong>{$vocero['rol_vocero']}:</strong> {$vocero['nombre']} ({$vocero['documento']})</p>";
        }
        $html .= "<p style='color: #856404; font-weight: bold;'>REGLA IMPORTANTE: Solo puede autorizar a UNO de los dos. El Vocero Principal tiene prioridad.</p>";
        $html .= "</div>";
        
        $html .= "<div style='text-align: center; margin: 30px 0;'>";
        $html .= "<a href='{$oficio_url}' style='background: #0066cc; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; display: inline-block;'>";
        $html .= "📄 COMPLETAR OFICIO DE AUTORIZACIÓN";
        $html .= "</a>";
        $html .= "</div>";
        
        $html .= "<p>Se requiere su pronta atención y colaboración en este proceso de participación estudiantil.</p>";
        $html .= "<p>Atentamente,</p>";
        $html .= "<p><strong>Coordinación de Bienestar</strong><br>";
        $html .= "SENA - Centro de Teleinformática y Producción Industrial</p>";
        $html .= "</div>";
        
        $html .= "<div style='text-align: center; padding: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;'>";
        $html .= "<p>Servicio Nacional de Aprendizaje - SENA</p>";
        $html .= "<p>Sistema de Gestión SenApre - Oficio No. " . date('Y-m-d') . "-001</p>";
        $html .= "<p>Generado: " . date('d/m/Y H:i') . "</p>";
        $html .= "</div>";
        
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Generar mensaje WhatsApp informativo
     */
    private function generarMensajeWhatsAppInfo($instructor, $voceros, $convocatoria, $oficio_url) {
        $mensaje = "📋 *INFORMACIÓN OFICIAL - SENA TELEINFORMÁTICA*\n\n";
        $mensaje .= "👨‍🏫 Estimado Instructor {$instructor['nombre']}\n\n";
        $mensaje .= "📄 *OFICIO DE AUTORIZACIÓN PENDIENTE*\n\n";
        $mensaje .= "🎭 *VOCEROS FICHA {$voceros[0]['numero_ficha']}*: \n";
        
        foreach ($voceros as $vocero) {
            $emoji = $vocero['rol_vocero'] === 'Vocero Principal' ? '🎭' : '🔄';
            $mensaje .= "{$emoji} {$vocero['nombre']} ({$vocero['rol_vocero']})\n";
        }
        
        $mensaje .= "\n📅 *Reunión*: {$convocatoria['titulo']}\n";
        $mensaje .= "🗓️ *Fecha*: " . date('d/m/Y', strtotime($convocatoria['fecha'])) . "\n";
        $mensaje .= "⏰ *Hora*: {$convocatoria['hora']}\n";
        $mensaje .= "📍 *Lugar*: {$convocatoria['lugar']}\n\n";
        
        $mensaje .= "⚠️ *ACCIÓN REQUERIDA*: \n";
        $mensaje .= "Complete el oficio oficial de autorización:\n";
        $mensaje .= "🔗 {$oficio_url}\n\n";
        
        $mensaje .= "🏢 *SENA - Coordinación de Bienestar*";
        
        return $mensaje;
    }
    
    /**
     * Generar mensaje WhatsApp recordatorio
     */
    private function generarMensajeWhatsAppRecordatorio($vocero, $convocatoria) {
        $emoji = $vocero['rol_vocero'] === 'Vocero Principal' ? '🎭' : '🔄';
        
        $mensaje = "{$emoji} *¡Recordatorio Reunión!*\n\n";
        $mensaje .= "👋 Hola {$vocero['nombre']}\n\n";
        $mensaje .= "📋 *QUEDAS CONVOCADO/A* a:\n";
        $mensaje .= "🎯 *{$convocatoria['titulo']}*\n\n";
        $mensaje .= "📅 *Fecha*: " . date('d/m/Y', strtotime($convocatoria['fecha'])) . "\n";
        $mensaje .= "⏰ *Hora*: {$convocatoria['hora']}\n";
        $mensaje .= "📍 *Lugar*: {$convocatoria['lugar']}\n";
        $mensaje .= "🎭 *Tu rol*: {$vocero['rol_vocero']}\n";
        $mensaje .= "📚 *Ficha*: {$vocero['numero_ficha']}\n\n";
        
        $mensaje .= "✅ *IMPORTANTE*: \n";
        $mensaje .= "Tu instructor debe autorizar tu asistencia. \n";
        $mensaje .= "Espera el oficio oficial para confirmar.\n\n";
        
        $mensaje .= "🏢 *SENA Teleinformática* \n";
        $mensaje .= "🔖 *ID: VOC-{$vocero['documento']}-" . date('Ymd') . "*";
        
        return $mensaje;
    }
    
    /**
     * Registrar notificación en base de datos
     */
    private function registrarNotificacion($tipo, $destinatario_id, $destinatario_tipo, $convocatoria_id, $mensaje) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notificaciones (
                    tipo, destinatario_id, destinatario_tipo, convocatoria_id, 
                    mensaje, estado, fecha_envio, creado_por
                ) VALUES (
                    :tipo, :destinatario_id, :destinatario_tipo, :convocatoria_id, 
                    :mensaje, 'enviada', NOW(), 'sistema'
                )
            ");
            
            $stmt->execute([
                ':tipo' => $tipo,
                ':destinatario_id' => $destinatario_id,
                ':destinatario_tipo' => $destinatario_tipo,
                ':convocatoria_id' => $convocatoria_id,
                ':mensaje' => $mensaje
            ]);
        } catch (Exception $e) {
            error_log("Error registrando notificación: " . $e->getMessage());
        }
    }
    
    /**
     * Procesar autorización de instructor
     */
    public function procesarAutorizacion() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Validar datos
            if (!isset($data['convocatoria_id']) || !isset($data['instructor_id']) || !isset($data['vocero_autorizado_id'])) {
                throw new Exception("Faltan datos requeridos");
            }
            
            // Obtener datos del vocero autorizado
            $stmt = $this->conn->prepare("
                SELECT a.*, f.numero_ficha, f.instructor_lider
                FROM aprendices a
                JOIN fichas f ON a.numero_ficha = f.numero_ficha
                WHERE a.id_aprendiz = :vocero_id
            ");
            $stmt->execute([':vocero_id' => $data['vocero_autorizado_id']]);
            $vocero_autorizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vocero_autorizado) {
                throw new Exception("Vocero no encontrado");
            }
            
            // Registrar autorización
            $stmt = $this->conn->prepare("
                INSERT INTO autorizaciones_vocero (
                    convocatoria_id, instructor_id, vocero_autorizado_id, 
                    fecha_autorizacion, estado, observaciones
                ) VALUES (
                    :convocatoria_id, :instructor_id, :vocero_autorizado_id,
                    NOW(), 'autorizado', :observaciones
                )
            ");
            
            $stmt->execute([
                ':convocatoria_id' => $data['convocatoria_id'],
                ':instructor_id' => $data['instructor_id'],
                ':vocero_autorizado_id' => $data['vocero_autorizado_id'],
                ':observaciones' => $data['observaciones'] ?? ''
            ]);
            
            // Enviar notificaciones a voceros
            $this->notificarVocerosAutorizacion($data['convocatoria_id'], $vocero_autorizado);
            
            echo json_encode([
                'success' => true,
                'message' => 'Autorización procesada y notificaciones enviadas',
                'vocero_autorizado' => $vocero_autorizado['nombre']
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Notificar voceros sobre autorización
     */
    private function notificarVocerosAutorizacion($convocatoria_id, $vocero_autorizado) {
        // Obtener datos completos
        $stmt = $this->conn->prepare("
            SELECT cr.*, a.nombre as vocero_nombre, a.correo as vocero_correo
            FROM convocatorias_reunion cr
            JOIN aprendices a ON a.id_aprendiz = :vocero_id
            WHERE cr.id = :convocatoria_id
        ");
        $stmt->execute([
            ':convocatoria_id' => $convocatoria_id,
            ':vocero_id' => $vocero_autorizado['id_aprendiz']
        ]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Enviar notificación al vocero autorizado
        $this->enviarNotificacionVoceroAutorizado($vocero_autorizado, $datos);
        
        // Aquí se notificaría al otro vocero (no autorizado)
        // Por simplicidad, solo notificamos al autorizado en este ejemplo
    }
    
    /**
     * Enviar notificación a vocero autorizado
     */
    private function enviarNotificacionVoceroAutorizado($vocero, $convocatoria) {
        $mensaje = "✅ ¡BUENA NOTICIA! Has sido autorizado/a para asistir a la reunión.\n\n";
        $mensaje .= "📋 CONVOCATORIA OFICIAL - SENA TELEINFORMÁTICA\n";
        $mensaje .= "📅 Reunión: {$convocatoria['titulo']}\n";
        $mensaje .= "🗓️ Fecha: {$convocatoria['fecha']}\n";
        $mensaje .= "⏰ Hora: {$convocatoria['hora']}\n";
        $mensaje .= "📍 Lugar: {$convocatoria['lugar']}\n\n";
        $mensaje .= "✅ Por favor confirma asistencia respondiendo: ASISTIRÉ\n\n";
        $mensaje .= "🏢 SENA - Centro de Teleinformática";
        
        // Aquí iría la integración real con WhatsApp API
        error_log("WhatsApp enviado a vocero {$vocero['nombre']}: " . $mensaje);
        
        return true;
    }
    
    /**
     * Obtener listado de convocatorias
     */
    public function obtenerConvocatorias() {
        $stmt = $this->conn->prepare("
            SELECT cr.*, 
                   COUNT(av.id) as total_autorizaciones,
                   GROUP_CONCAT(DISTINCT u.nombre) as instructores_notificados
            FROM convocatorias_reunion cr
            LEFT JOIN autorizaciones_vocero av ON cr.id = av.convocatoria_id
            LEFT JOIN usuarios u ON av.instructor_id = u.id_usuario
            GROUP BY cr.id
            ORDER BY cr.fecha_creacion DESC
        ");
        $stmt->execute();
        $convocatorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'convocatorias' => $convocatorias
        ]);
    }
    
    /**
     * Obtener dashboard de notificaciones
     */
    public function obtenerDashboard() {
        // Estadísticas generales
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_convocatorias,
                COUNT(CASE WHEN fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as convocatorias_ultimos_30_dias,
                COUNT(DISTINCT instructor_id) as instructores_participantes,
                COUNT(DISTINCT vocero_autorizado_id) as voceros_autorizados
            FROM convocatorias_reunion cr
            LEFT JOIN autorizaciones_vocero av ON cr.id = av.convocatoria_id
        ");
        $stmt->execute();
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Convocatorias recientes
        $stmt = $this->conn->prepare("
            SELECT cr.*, COUNT(av.id) as autorizaciones
            FROM convocatorias_reunion cr
            LEFT JOIN autorizaciones_vocero av ON cr.id = av.convocatoria_id
            WHERE cr.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY cr.id
            ORDER BY cr.fecha DESC
            LIMIT 5
        ");
        $stmt->execute();
        $recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'estadisticas' => $estadisticas,
            'convocatorias_recientes' => $recientes
        ]);
    }
}

// Manejo de solicitudes
$api = new NotificacionesAPI();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'crearConvocatoria':
                $api->crearConvocatoria();
                break;
            case 'procesarAutorizacion':
                $api->procesarAutorizacion();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    case 'GET':
        switch ($action) {
            case 'obtenerConvocatorias':
                $api->obtenerConvocatorias();
                break;
            case 'dashboard':
                $api->obtenerDashboard();
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
