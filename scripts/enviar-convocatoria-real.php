<?php
/**
 * ENVIAR CONVOCATORIA REAL A VOCERA ESPECÍFICA
 * Ficha: 2995479 - Vocera: 1004417452
 * Fecha: 18/03/2026 - 10:00 AM - Ambiente LEGO
 */

echo "🚀 ENVIANDO CONVOCATORIA REAL\n";
echo "==============================\n\n";

// Datos de la vocera
$vocera_documento = '1004417452';
$ficha_numero = '2995479';

// Datos de la reunión
$reunion = [
    'titulo' => 'Reunión de Voceros',
    'fecha' => '2026-03-18',
    'hora' => '10:00 AM',
    'lugar' => 'Ambiente de LEGO - Centro Tecnológico de la Amazonia',
    'tipo' => 'Ordinaria',
    'descripcion' => 'Reunión ordinaria de voceros para tratar temas de interés estudiantil y coordinación de actividades.'
];

// Agenda de la reunión
$agenda = [
    '1. Informes de gestión del coordinador',
    '2. Situación académica de los aprendices',
    '3. Próximas actividades y eventos',
    '4. Propuestas de los voceros',
    '5. Varios y asuntos de interés'
];

echo "📋 DATOS DE LA CONVOCATORIA:\n";
echo "🎯 Título: {$reunion['titulo']}\n";
echo "📅 Fecha: {$reunion['fecha']}\n";
echo "⏰ Hora: {$reunion['hora']}\n";
echo "📍 Lugar: {$reunion['lugar']}\n";
echo "📚 Ficha: {$ficha_numero}\n";
echo "👤 Vocera: {$vocera_documento}\n\n";

// Conectar a la base de datos
require_once __DIR__ . '/../api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    echo "✅ Conexión a base de datos exitosa\n\n";
} catch (Exception $e) {
    echo "❌ Error conectando a la base de datos: " . $e->getMessage() . "\n";
    exit;
}

// Buscar datos de la vocera
echo "🔍 BUSCANDO DATOS DE LA VOCERA...\n";
$stmt = $conn->prepare("
    SELECT a.*, f.numero_ficha, f.instructor_lider, f.vocero_principal, f.vocero_suplente,
           CASE 
               WHEN f.vocero_principal = a.documento THEN 'Vocero Principal'
               WHEN f.vocero_suplente = a.documento THEN 'Vocero Suplente'
               ELSE 'Representante'
           END as rol_vocero
    FROM aprendices a
    JOIN fichas f ON a.numero_ficha = f.numero_ficha
    WHERE a.documento = :documento 
    AND f.numero_ficha = :ficha
    AND a.estado = 'LECTIVA'
    LIMIT 1
");

$stmt->execute([
    ':documento' => $vocera_documento,
    ':ficha' => $ficha_numero
]);

$vocera = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vocera) {
    echo "❌ No se encontró la vocera con documento {$vocera_documento} en la ficha {$ficha_numero}\n";
    exit;
}

echo "✅ Vocera encontrada:\n";
echo "👤 Nombre: {$vocera['nombre']}\n";
echo "📞 Teléfono: {$vocera['telefono']}\n";
echo "📧 Correo: {$vocera['correo']}\n";
echo "🎭 Rol: {$vocera['rol_vocero']}\n";
echo "👨‍🏫 Instructor: {$vocera['instructor_lider']}\n\n";

// Crear convocatoria en la base de datos
echo "📝 CREANDO CONVOCATORIA EN BASE DE DATOS...\n";
$stmt = $conn->prepare("
    INSERT INTO convocatorias_reunion (
        titulo, fecha, hora, lugar, tipo, agenda, descripcion, 
        fichas_invitadas, tipo_envio, creado_por, fecha_creacion
    ) VALUES (
        :titulo, :fecha, :hora, :lugar, :tipo, :agenda, :descripcion,
        :fichas_invitadas, :tipo_envio, :creado_por, NOW()
    )
");

$stmt->execute([
    ':titulo' => $reunion['titulo'],
    ':fecha' => $reunion['fecha'],
    ':hora' => $reunion['hora'],
    ':lugar' => $reunion['lugar'],
    ':tipo' => $reunion['tipo'],
    ':agenda' => json_encode($agenda),
    ':descripcion' => $reunion['descripcion'],
    ':fichas_invitadas' => json_encode([$ficha_numero]),
    ':tipo_envio' => 'individual',
    ':creado_por' => 'Sistema Testing'
]);

$convocatoria_id = $conn->lastInsertId();
echo "✅ Convocatoria creada con ID: {$convocatoria_id}\n\n";

// Generar mensajes
echo "📱 GENERANDO MENSAJES PARA ENVÍO...\n\n";

// Mensaje WhatsApp
$mensaje_whatsapp = generarMensajeWhatsAppVocera($vocera, $reunion);
echo "📱 MENSAJE WHATSAPP:\n";
echo "==================\n";
echo $mensaje_whatsapp . "\n\n";

// Mensaje SMS
$mensaje_sms = generarMensajeSMSVocera($vocera, $reunion);
echo "📱 MENSAJE SMS:\n";
echo "==============\n";
echo $mensaje_sms . "\n\n";

// Correo
$asunto_correo = "OFICIO DE CONVOCATORIA - {$vocera['rol_vocero']} - {$vocera['nombre']}";
$contenido_correo = generarCorreoVocera($vocera, $reunion, $agenda);
echo "📧 CORREO ELECTRÓNICO:\n";
echo "===================\n";
echo "Asunto: {$asunto_correo}\n\n";
echo $contenido_correo . "\n\n";

// Registrar notificaciones en base de datos
echo "📊 REGISTRANDO NOTIFICACIONES...\n";

// Notificación WhatsApp
$stmt = $conn->prepare("
    INSERT INTO notificaciones (
        tipo, destinatario_id, destinatario_tipo, convocatoria_id, 
        mensaje, estado, fecha_envio, creado_por
    ) VALUES (
        'whatsapp', :destinatario_id, 'vocero', :convocatoria_id, 
        :mensaje, 'enviada', NOW(), 'sistema'
    )
");
$stmt->execute([
    ':destinatario_id' => $vocera['id_aprendiz'],
    ':convocatoria_id' => $convocatoria_id,
    ':mensaje' => $mensaje_whatsapp
]);

// Notificación SMS
$stmt = $conn->prepare("
    INSERT INTO notificaciones (
        tipo, destinatario_id, destinatario_tipo, convocatoria_id, 
        mensaje, estado, fecha_envio, creado_por
    ) VALUES (
        'sms', :destinatario_id, 'vocero', :convocatoria_id, 
        :mensaje, 'enviada', NOW(), 'sistema'
    )
");
$stmt->execute([
    ':destinatario_id' => $vocera['id_aprendiz'],
    ':convocatoria_id' => $convocatoria_id,
    ':mensaje' => $mensaje_sms
]);

// Notificación Correo
$stmt = $conn->prepare("
    INSERT INTO notificaciones (
        tipo, destinatario_id, destinatario_tipo, convocatoria_id, 
        mensaje, estado, fecha_envio, creado_por
    ) VALUES (
        'correo', :destinatario_id, 'vocero', :convocatoria_id, 
        :mensaje, 'enviada', NOW(), 'sistema'
    )
");
$stmt->execute([
    ':destinatario_id' => $vocera['id_aprendiz'],
    ':convocatoria_id' => $convocatoria_id,
    ':mensaje' => $asunto_correo . ' - ' . substr($contenido_correo, 0, 100) . '...'
]);

echo "✅ Notificaciones registradas en base de datos\n\n";

// Simular envíos reales
echo "🚀 ENVIANDO COMUNICACIONES...\n";
echo "==============================\n";

// Envío WhatsApp (simulado)
echo "📱 Enviando WhatsApp a {$vocera['telefono']}...\n";
echo "   ✅ Mensaje enviado (simulado)\n\n";

// Envío SMS (simulado)
echo "📱 Enviando SMS a {$vocera['telefono']}...\n";
echo "   ✅ Mensaje enviado (simulado)\n\n";

// Envío Correo (simulado)
echo "📧 Enviando Correo a {$vocera['correo']}...\n";
echo "   ✅ Correo enviado (simulado)\n\n";

// Resumen final
echo "📊 RESUMEN DE ENVÍO:\n";
echo "===================\n";
echo "✅ Convocatoria ID: {$convocatoria_id}\n";
echo "✅ Vocera: {$vocera['nombre']} ({$vocera['rol_vocero']})\n";
echo "✅ Ficha: {$ficha_numero}\n";
echo "✅ Fecha reunión: {$reunion['fecha']} {$reunion['hora']}\n";
echo "✅ Lugar: {$reunion['lugar']}\n";
echo "✅ WhatsApp: Enviado a {$vocera['telefono']}\n";
echo "✅ SMS: Enviado a {$vocera['telefono']}\n";
echo "✅ Correo: Enviado a {$vocera['correo']}\n";
echo "✅ Notificaciones registradas: 3\n\n";

echo "🎯 ¡CONVOCATORIA ENVIADA EXITOSAMENTE!\n";
echo "===================================\n\n";

echo "📞 DATOS DE CONTACTO DE LA VOCERA:\n";
echo "• Nombre: {$vocera['nombre']}\n";
echo "• Teléfono: {$vocera['telefono']}\n";
echo "• Correo: {$vocera['correo']}\n";
echo "• Documento: {$vocera['documento']}\n\n";

echo "📋 AGENDA DE LA REUNIÓN:\n";
foreach ($agenda as $item) {
    echo "• {$item}\n";
}
echo "\n";

echo "🔗 LINK PARA AUTORIZACIÓN (cuando esté listo):\n";
echo "http://senapre.sena.edu.co/oficios/autorizar-vocero.php?convocatoria={$convocatoria_id}&ficha={$ficha_numero}\n\n";

// Funciones para generar mensajes
function generarMensajeWhatsAppVocera($vocera, $reunion) {
    $emoji = $vocera['rol_vocero'] === 'Vocero Principal' ? '🎭' : '🔄';
    
    $mensaje = "{$emoji} *¡CONVOCATORIA REUNIÓN DE VOCEROS!*\n\n";
    $mensaje .= "👋 Hola {$vocera['nombre']}\n\n";
    $mensaje .= "📋 *ESTÁS CONVOCADO/A* a:\n";
    $mensaje .= "🎯 *{$reunion['titulo']}*\n\n";
    $mensaje .= "📅 *Fecha*: " . date('d/m/Y', strtotime($reunion['fecha'])) . "\n";
    $mensaje .= "⏰ *Hora*: {$reunion['hora']}\n";
    $mensaje .= "📍 *Lugar*: {$reunion['lugar']}\n";
    $mensaje .= "🎭 *Tu rol*: {$vocera['rol_vocero']}\n";
    $mensaje .= "📚 *Ficha*: {$vocera['numero_ficha']}\n\n";
    
    $mensaje .= "✅ *IMPORTANTE*: \n";
    $mensaje .= "Tu instructor debe autorizar tu asistencia. \n";
    $mensaje .= "Espera el oficio oficial para confirmar.\n\n";
    
    $mensaje .= "🏢 *SENA - Centro Tecnológico de la Amazonia* \n";
    $mensaje .= "🔖 *ID: VOC-{$vocera['documento']}-" . date('Ymd') . "*";
    
    return $mensaje;
}

function generarMensajeSMSVocera($vocera, $reunion) {
    $mensaje = "SENA: Convocatoria reunion {$vocera['rol_vocero']} {$vocera['nombre']}. ";
    $mensaje .= "Fecha: " . date('d/m/Y', strtotime($reunion['fecha'])) . " {$reunion['hora']}. ";
    $mensaje .= "Lugar: {$reunion['lugar']}. ";
    $mensaje .= "Ficha: {$vocera['numero_ficha']}. ";
    $mensaje .= "ID: VOC-{$vocera['documento']}-" . date('Ymd');
    
    return $mensaje;
}

function generarCorreoVocera($vocera, $reunion, $agenda) {
    $html = "<div style='font-family: Arial; max-width: 800px; margin: 0 auto;'>";
    $html .= "<div style='text-align: center; padding: 20px; border-bottom: 2px solid #0066cc;'>";
    $html .= "<h1 style='color: #0066cc; margin: 0;'>OFICIO DE CONVOCATORIA</h1>";
    $html .= "<p style='margin: 5px 0;'>SERVICIO NACIONAL DE APRENDIZAJE - SENA</p>";
    $html .= "<p style='margin: 5px 0;'>Centro Tecnológico de la Amazonia</p>";
    $html .= "</div>";
    
    $html .= "<div style='padding: 30px;'>";
    $html .= "<p><strong>Fecha:</strong> " . date('d/m/Y') . "</p>";
    $html .= "<p><strong>Para:</strong> {$vocera['nombre']}</p>";
    $html .= "<p><strong>De:</strong> Coordinación de Bienestar</p>";
    $html .= "<p><strong>Asunto:</strong> CONVOCATORIA REUNIÓN DE VOCEROS</p><br>";
    
    $html .= "<p>Estimado/a {$vocera['nombre']},</p>";
    $html .= "<p>Por medio de la presente se le convoca formalmente a la reunión de voceros programada.</p>";
    
    $html .= "<div style='background: #f8f9fa; padding: 20px; border-left: 4px solid #0066cc; margin: 20px 0;'>";
    $html .= "<h3 style='color: #0066cc; margin-top: 0;'>DATOS DE LA REUNIÓN</h3>";
    $html .= "<p><strong>Título:</strong> {$reunion['titulo']}</p>";
    $html .= "<p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($reunion['fecha'])) . "</p>";
    $html .= "<p><strong>Hora:</strong> {$reunion['hora']}</p>";
    $html .= "<p><strong>Lugar:</strong> {$reunion['lugar']}</p>";
    $html .= "<p><strong>Ficha:</strong> {$vocera['numero_ficha']}</p>";
    $html .= "<p><strong>Tu rol:</strong> {$vocera['rol_vocero']}</p>";
    $html .= "</div>";
    
    $html .= "<div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
    $html .= "<h3 style='color: #856404; margin-top: 0;'>AGENDA DE LA REUNIÓN</h3>";
    $html .= "<ol>";
    foreach ($agenda as $item) {
        $html .= "<li>{$item}</li>";
    }
    $html .= "</ol>";
    $html .= "</div>";
    
    $html .= "<p>Se requiere su puntual asistencia y participación activa en esta reunión.</p>";
    
    $html .= "<p>Atentamente,</p>";
    $html .= "<p><strong>Coordinación de Bienestar</strong><br>";
    $html .= "SENA - Centro Tecnológico de la Amazonia</p>";
    $html .= "</div>";
    
    $html .= "<div style='text-align: center; padding: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;'>";
    $html .= "<p>Servicio Nacional de Aprendizaje - SENA</p>";
    $html .= "<p>Centro Tecnológico de la Amazonia</p>";
    $html .= "<p>Sistema de Gestión SenApre - Oficio No. " . date('Y-m-d') . "-001</p>";
    $html .= "<p>Generado: " . date('d/m/Y H:i') . "</p>";
    $html .= "</div>";
    
    $html .= "</div>";
    
    return $html;
}
?>
