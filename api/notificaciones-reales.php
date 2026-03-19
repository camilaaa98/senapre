<?php
/**
 * API DE NOTIFICACIONES REALES
 * Integración con APIs verdaderas: WhatsApp, SMS, Correo
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

class NotificacionesRealesAPI {
    private $conn;
    
    // Configuración de APIs (reemplazar con claves reales)
    private $config = [
        'whatsapp' => [
            'token' => 'YOUR_WHATSAPP_BUSINESS_TOKEN',
            'phone_id' => 'YOUR_PHONE_NUMBER_ID',
            'api_url' => 'https://graph.facebook.com/v18.0/'
        ],
        'sms' => [
            'api_key' => 'YOUR_SMS_API_KEY',
            'api_url' => 'https://api.twilio.com/2010-04-01/Accounts/'
        ],
        'email' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'your-email@gmail.com',
            'password' => 'your-app-password'
        ]
    ];
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Enviar mensaje WhatsApp REAL
     */
    public function enviarWhatsApp($destinatario, $mensaje, $convocatoria_id = null) {
        try {
            // Para desarrollo: Simulación con logging
            if ($this->isDevelopmentMode()) {
                $this->logNotification('whatsapp', $destinatario, $mensaje, 'simulated');
                return [
                    'success' => true,
                    'message_id' => 'WA_SIM_' . time(),
                    'status' => 'simulated',
                    'message' => 'Mensaje simulado (modo desarrollo)'
                ];
            }
            
            // Producción: Integración real con WhatsApp Business API
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => '57' . $destinatario,
                'type' => 'text',
                'text' => [
                    'body' => $mensaje
                ]
            ];
            
            $headers = [
                'Authorization: Bearer ' . $this->config['whatsapp']['token'],
                'Content-Type: application/json'
            ];
            
            $url = $this->config['whatsapp']['api_url'] . $this->config['whatsapp']['phone_id'] . '/messages';
            
            $response = $this->makeHttpRequest('POST', $url, $payload, $headers);
            $result = json_decode($response, true);
            
            if (isset($result['messages'][0]['id'])) {
                $this->logNotification('whatsapp', $destinatario, $mensaje, 'sent', $result['messages'][0]['id']);
                return [
                    'success' => true,
                    'message_id' => $result['messages'][0]['id'],
                    'status' => 'sent'
                ];
            } else {
                throw new Exception('Error en API WhatsApp: ' . json_encode($result));
            }
            
        } catch (Exception $e) {
            $this->logNotification('whatsapp', $destinatario, $mensaje, 'failed', null, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar SMS REAL
     */
    public function enviarSMS($destinatario, $mensaje, $convocatoria_id = null) {
        try {
            // Para desarrollo: Simulación
            if ($this->isDevelopmentMode()) {
                $this->logNotification('sms', $destinatario, $mensaje, 'simulated');
                return [
                    'success' => true,
                    'message_id' => 'SMS_SIM_' . time(),
                    'status' => 'simulated',
                    'message' => 'SMS simulado (modo desarrollo)'
                ];
            }
            
            // Producción: Integración con Twilio o API SMS
            $payload = [
                'To' => '+57' . $destinatario,
                'From' => 'YOUR_TWILIO_PHONE',
                'Body' => $mensaje
            ];
            
            $headers = [
                'Authorization: Basic ' . base64_encode($this->config['sms']['api_key'] . ':'),
                'Content-Type: application/x-www-form-urlencoded'
            ];
            
            $url = $this->config['sms']['api_url'] . 'YOUR_ACCOUNT_SID/Messages.json';
            
            $response = $this->makeHttpRequest('POST', $url, $payload, $headers);
            $result = json_decode($response, true);
            
            if (isset($result['sid'])) {
                $this->logNotification('sms', $destinatario, $mensaje, 'sent', $result['sid']);
                return [
                    'success' => true,
                    'message_id' => $result['sid'],
                    'status' => 'sent'
                ];
            } else {
                throw new Exception('Error en API SMS: ' . json_encode($result));
            }
            
        } catch (Exception $e) {
            $this->logNotification('sms', $destinatario, $mensaje, 'failed', null, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar Correo REAL
     */
    public function enviarCorreo($destinatario, $asunto, $contenido, $convocatoria_id = null) {
        try {
            // Para desarrollo: Simulación
            if ($this->isDevelopmentMode()) {
                $this->logNotification('email', $destinatario, $asunto, 'simulated');
                return [
                    'success' => true,
                    'message_id' => 'EMAIL_SIM_' . time(),
                    'status' => 'simulated',
                    'message' => 'Correo simulado (modo desarrollo)'
                ];
            }
            
            // Producción: Integración con PHPMailer
            require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
            require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['email']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['email']['username'];
            $mail->Password = $this->config['email']['password'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config['email']['port'];
            
            // Destinatarios
            $mail->setFrom($this->config['email']['username'], 'SENA - Centro Tecnológico de la Amazonia');
            $mail->addAddress($destinatario);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            $mail->AltBody = strip_tags($contenido);
            
            $mail->send();
            
            $message_id = 'EMAIL_' . time();
            $this->logNotification('email', $destinatario, $asunto, 'sent', $message_id);
            
            return [
                'success' => true,
                'message_id' => $message_id,
                'status' => 'sent'
            ];
            
        } catch (Exception $e) {
            $this->logNotification('email', $destinatario, $asunto, 'failed', null, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enviar notificaciones múltiples
     */
    public function enviarNotificacionesMultiples($data) {
        $resultados = [];
        $medios = $data['medios'] ?? [];
        $destinatario = $data['destinatario'];
        $mensaje = $data['mensaje'];
        $asunto = $data['asunto'] ?? '';
        $contenido = $data['contenido'] ?? $mensaje;
        
        foreach ($medios as $medio) {
            switch ($medio) {
                case 'whatsapp':
                    $resultados['whatsapp'] = $this->enviarWhatsApp($destinatario['telefono'], $mensaje, $data['convocatoria_id'] ?? null);
                    break;
                case 'sms':
                    $resultados['sms'] = $this->enviarSMS($destinatario['telefono'], $mensaje, $data['convocatoria_id'] ?? null);
                    break;
                case 'correo':
                    $resultados['correo'] = $this->enviarCorreo($destinatario['correo'], $asunto, $contenido, $data['convocatoria_id'] ?? null);
                    break;
            }
        }
        
        return [
            'success' => true,
            'resultados' => $resultados,
            'enviados' => count(array_filter($resultados, fn($r) => $r['success'])),
            'fallidos' => count(array_filter($resultados, fn($r) => !$r['success']))
        ];
    }
    
    /**
     * Obtener estadísticas de notificaciones
     */
    public function obtenerEstadisticas() {
        $sql = "
            SELECT 
                tipo,
                estado,
                COUNT(*) as total,
                DATE(fecha_envio) as fecha
            FROM notificaciones 
            WHERE fecha_envio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY tipo, estado, DATE(fecha_envio)
            ORDER BY fecha DESC, tipo, estado
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Resumen general
        $resumen = [
            'total_enviados' => 0,
            'total_exitosos' => 0,
            'total_fallidos' => 0,
            'por_medio' => []
        ];
        
        foreach ($estadisticas as $stat) {
            $resumen['total_enviados'] += $stat['total'];
            
            if ($stat['estado'] === 'sent') {
                $resumen['total_exitosos'] += $stat['total'];
            } elseif ($stat['estado'] === 'failed') {
                $resumen['total_fallidos'] += $stat['total'];
            }
            
            if (!isset($resumen['por_medio'][$stat['tipo']])) {
                $resumen['por_medio'][$stat['tipo']] = [
                    'enviados' => 0,
                    'exitosos' => 0,
                    'fallidos' => 0
                ];
            }
            
            $resumen['por_medio'][$stat['tipo']]['enviados'] += $stat['total'];
            
            if ($stat['estado'] === 'sent') {
                $resumen['por_medio'][$stat['tipo']]['exitosos'] += $stat['total'];
            } elseif ($stat['estado'] === 'failed') {
                $resumen['por_medio'][$stat['tipo']]['fallidos'] += $stat['total'];
            }
        }
        
        return [
            'success' => true,
            'resumen' => $resumen,
            'detalles' => $estadisticas
        ];
    }
    
    /**
     * Verificar si está en modo desarrollo
     */
    private function isDevelopmentMode() {
        return true; // Cambiar a false en producción
    }
    
    /**
     * Registrar notificación en base de datos
     */
    private function logNotification($tipo, $destinatario, $mensaje, $estado, $message_id = null, $error = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notificaciones (
                    tipo, destinatario, mensaje, estado, 
                    message_id, error, fecha_envio, creado_por
                ) VALUES (
                    :tipo, :destinatario, :mensaje, :estado,
                    :message_id, :error, NOW(), 'sistema'
                )
            ");
            
            $stmt->execute([
                ':tipo' => $tipo,
                ':destinatario' => $destinatario,
                ':mensaje' => substr($mensaje, 0, 500),
                ':estado' => $estado,
                ':message_id' => $message_id,
                ':error' => $error
            ]);
        } catch (Exception $e) {
            error_log("Error logging notification: " . $e->getMessage());
        }
    }
    
    /**
     * Realizar petición HTTP
     */
    private function makeHttpRequest($method, $url, $data, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error: $httpCode - $response");
        }
        
        return $response;
    }
}

// Manejo de solicitudes
$api = new NotificacionesRealesAPI();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'enviarWhatsApp':
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($api->enviarWhatsApp($data['destinatario'], $data['mensaje'], $data['convocatoria_id'] ?? null));
                break;
            case 'enviarSMS':
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($api->enviarSMS($data['destinatario'], $data['mensaje'], $data['convocatoria_id'] ?? null));
                break;
            case 'enviarCorreo':
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($api->enviarCorreo($data['destinatario'], $data['asunto'], $data['contenido'], $data['convocatoria_id'] ?? null));
                break;
            case 'enviarMultiples':
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode($api->enviarNotificacionesMultiples($data));
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    case 'GET':
        switch ($action) {
            case 'estadisticas':
                echo json_encode($api->obtenerEstadisticas());
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
