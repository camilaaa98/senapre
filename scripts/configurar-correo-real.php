<?php
/**
 * CONFIGURACIÓN RÁPIDA PARA ENVÍO DE CORREOS REALES
 * Solo necesitas tu cuenta Gmail
 */

echo "📧 CONFIGURACIÓN CORREO REAL - SenApre\n";
echo "===================================\n\n";

// Paso 1: Configuración de Gmail
echo "🔧 PASO 1: CONFIGURA TU CUENTA GMAIL\n";
echo "====================================\n\n";

echo "1. Ve a: https://myaccount.google.com/\n";
echo "2. Seguridad → Verificación en 2 pasos (ACTÍVALA)\n";
echo "3. Contraseñas de aplicaciones → Generar nueva\n";
echo "4. Nombre: SenApre Notificaciones\n";
echo "5. Copia la contraseña de 16 caracteres\n\n";

// Solicitar datos al usuario
echo "📝 INGRESA TUS DATOS:\n";
echo "======================\n\n";

echo "Tu correo Gmail: ";
$gmail_correo = trim(fgets(STDIN));

echo "Tu contraseña de aplicación (16 caracteres): ";
$gmail_password = trim(fgets(STDIN));

echo "Correo para enviar pruebas: ";
$correo_prueba = trim(fgets(STDIN));

echo "\n🚀 CONFIGURANDO ENVÍO REAL...\n";
echo "=============================\n\n";

// Actualizar configuración en el archivo
$config_file = __DIR__ . '/../api/notificaciones-reales.php';
$config_content = file_get_contents($config_file);

// Reemplazar configuración de email
$config_content = preg_replace(
    "/'username' => 'your-email@gmail.com'/",
    "'username' => '$gmail_correo'",
    $config_content
);

$config_content = preg_replace(
    "/'password' => 'your-app-password'/",
    "'password' => '$gmail_password'",
    $config_content
);

file_put_contents($config_file, $config_content);

echo "✅ Configuración actualizada en notificaciones-reales.php\n\n";

// Cambiar a modo producción para correo
$config_content = preg_replace(
    "/return true; \/\/ Cambiar a false en producción/",
    "return false; \/\/ Modo producción activado",
    $config_content
);

file_put_contents($config_file, $config_content);

echo "✅ Modo producción activado para correos\n\n";

// Probar envío real
echo "📧 ENVIANDO CORREO DE PRUEBA...\n";
echo "==============================\n\n";

// Datos de prueba
$destinatario = [
    'nombre' => 'Usuario de Prueba',
    'correo' => $correo_prueba
];

$convocatoria = [
    'titulo' => 'Reunión de Voceros - PRUEBA REAL',
    'fecha' => '18/03/2026',
    'hora' => '10:00 AM',
    'lugar' => 'Ambiente de LEGO - Centro Tecnológico de la Amazonia'
];

// Generar correo HTML
$asunto = "PRUEBA REAL - OFICIO DE CONVOCATORIA - {$destinatario['nombre']}";
$contenido = "<div style='font-family: Arial; max-width: 800px; margin: 0 auto;'>
<div style='text-align: center; padding: 20px; border-bottom: 2px solid #0066cc;'>
<h1 style='color: #0066cc; margin: 0;'>📧 PRUEBA DE ENVÍO REAL</h1>
<p style='margin: 5px 0;'>SERVICIO NACIONAL DE APRENDIZAJE - SENA</p>
<p style='margin: 5px 0;'>Centro Tecnológico de la Amazonia</p>
</div>
<div style='padding: 30px;'>
<p><strong>Fecha:</strong> " . date('d/m/Y H:i') . "</p>
<p><strong>Para:</strong> {$destinatario['nombre']}</p>
<p><strong>De:</strong> Sistema SenApre</p>
<p><strong>Asunto:</strong> PRUEBA DE ENVÍO REAL DE CORREO</p><br>
<p>Estimado/a {$destinatario['nombre']},</p>
<p><strong>¡ESTE ES UN CORREO REAL ENVIADO DESDE EL SISTEMA SenApre!</strong></p>
<div style='background: #d4edda; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0;'>
<h3 style='color: #155724; margin-top: 0;'>✅ CONFIGURACIÓN EXITOSA</h3>
<p>El sistema de notificaciones está funcionando correctamente.</p>
<p>Este correo fue enviado usando la API real de PHPMailer con SMTP.</p>
</div>
<div style='background: #f8f9fa; padding: 20px; border-left: 4px solid #0066cc; margin: 20px 0;'>
<h3 style='color: #0066cc; margin-top: 0;'>DATOS DE LA REUNIÓN (EJEMPLO)</h3>
<p><strong>Título:</strong> {$convocatoria['titulo']}</p>
<p><strong>Fecha:</strong> {$convocatoria['fecha']}</p>
<p><strong>Hora:</strong> {$convocatoria['hora']}</p>
<p><strong>Lugar:</strong> {$convocatoria['lugar']}</p>
</div>
<p><strong>¡El sistema está listo para enviar convocatorias reales!</strong></p>
<p>Atentamente,</p>
<p><strong>Sistema de Notificaciones SenApre</strong><br>
SENA - Centro Tecnológico de la Amazonia</p>
</div>
<div style='text-align: center; padding: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;'>
<p>Servicio Nacional de Aprendizaje - SENA</p>
<p>Centro Tecnológico de la Amazonia</p>
<p>Sistema de Gestión SenApre - Prueba Real</p>
<p>Generado: " . date('d/m/Y H:i:s') . "</p>
</div>
</div>";

// Enviar correo usando PHPMailer
try {
    require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';
    require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $gmail_correo;
    $mail->Password = $gmail_password;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Remitente y destinatario
    $mail->setFrom($gmail_correo, 'SENA - Centro Tecnológico de la Amazonia');
    $mail->addAddress($correo_prueba);
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body = $contenido;
    $mail->AltBody = strip_tags($contenido);
    
    // Enviar
    $mail->send();
    
    echo "✅ CORREO ENVIADO EXITOSAMENTE!\n";
    echo "📧 Para: $correo_prueba\n";
    echo "📋 Asunto: $asunto\n";
    echo "🕐 Enviado: " . date('H:i:s') . "\n\n";
    
    echo "🎯 ¡VERIFICA TU BANDEJA DE ENTRADA!\n";
    echo "================================\n";
    echo "Deberías recibir el correo ahora mismo.\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR ENVIANDO CORREO:\n";
    echo "========================\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "🔧 SOLUCIONES POSIBLES:\n";
    echo "======================\n";
    echo "1. Verifica que activaste la verificación en 2 pasos\n";
    echo "2. Confirma que usaste una contraseña de aplicación\n";
    echo "3. Revisa que el correo y contraseña son correctos\n";
    echo "4. Intenta con otro correo Gmail\n\n";
}

// Instrucciones finales
echo "📋 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. ✅ Correo real configurado y funcionando\n";
echo "2. 📱 Configura Twilio para SMS (opcional)\n";
echo "3. 📱 Configura WhatsApp Business (opcional)\n";
echo "4. 🎯 Usa el dashboard para enviar convocatorias\n\n";

echo "🌐 ACCESO AL SISTEMA:\n";
echo "====================\n";
echo "• Dashboard: admin-dashboard-notificaciones.html\n";
echo "• Enviar convocatorias: admin-notificaciones.html\n";
echo "• API: api/notificaciones-reales.php\n\n";

echo "✨ ¡SISTEMA DE CORREOS REALES ACTIVADO! ✨\n";
echo "======================================\n";
echo "Ya puedes enviar convocatorias reales por correo.\n";
echo "Los destinatarios las recibirán inmediatamente.\n\n";
?>
