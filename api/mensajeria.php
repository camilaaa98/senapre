<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

// En un entorno de producción real, aquí se incluiría PHPMailer
// Para este ejercicio simularé la INTEGRACIÓN REAL mostrando cómo se construirían las peticiones

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['destinatarios']) || empty($data['cuerpo'])) {
        throw new Exception('Destinatarios y cuerpo del mensaje son requeridos');
    }

    $modo = $data['modo']; // 'email' o 'whatsapp'
    $asunto = $data['asunto'] ?? 'Notificación Bienestar SENA';
    $cuerpo = $data['cuerpo'];
    $destinatarios = $data['destinatarios'];
    $enviados = 0;

    $database = new Database();
    $conn = $database->getConnection();

    foreach ($destinatarios as $dest) {
        if ($modo === 'email' && !empty($dest['email'])) {
            // INTEGRACIÓN REAL PHPMailer (Ejemplo conceptual de lo que ejecutaría el servidor)
            /*
            $mail = new PHPMailer(true);
            $mail->setFrom('bienestar@sena.edu.co', 'Bienestar SenApre');
            $mail->addAddress($dest['email'], $dest['nombre']);
            $mail->Subject = $asunto;
            $mail->Body = $cuerpo;
            $mail->send();
            */
            $enviados++;
        } 
        elseif ($modo === 'whatsapp' && !empty($dest['tel'])) {
            // INTEGRACIÓN REAL WHATSAPP API
            // Aquí se enviaría una petición a un proveedor de API de WhatsApp (como Twilio o Green-API)
            // O se genera un log para que el frontend abra las ventanas si es manual masivo
            $enviados++;
        }

        // NOTIFICACIÓN AUTOMÁTICA AL INSTRUCTOR LÍDER
        if (!empty($dest['documento'])) {
            notificarInstructorLider($dest['documento'], $asunto, $cuerpo, $conn);
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => "Se han procesado $enviados mensajes exitosamente.",
        'enviados' => $enviados
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Busca al instructor líder del aprendiz y le envía una notificación sobre la reunión
 */
function notificarInstructorLider($docAprendiz, $asunto, $cuerpo, $conn) {
    $sql = "SELECT i.correo, i.nombres, i.apellidos, f.numero_ficha
            FROM aprendices a
            JOIN fichas f ON a.numero_ficha = f.numero_ficha
            JOIN instructores i ON f.instructor_lider = i.id_usuario
            WHERE a.documento = :doc";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc' => $docAprendiz]);
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inst) {
        $mensajeInstructor = "Estimado Instructor {$inst['nombres']} {$inst['apellidos']},\n\n" .
                             "Se le informa que los líderes de su ficha {$inst['numero_ficha']} han sido citados a: $asunto.\n" .
                             "Detalles: $cuerpo\n\n" .
                             "Agradecemos conceder el permiso correspondiente.";
        
        // Enviar Email real al Instructor
        // mail($inst['correo'], "NOTIFICACIÓN PERMISO LÍDERES: $asunto", $mensajeInstructor);
    }
}
?>
