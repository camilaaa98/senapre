<?php
/**
 * VERSIÓN OFFLINE - MOSTRAR MENSAJES PARA VOCERA
 * Ficha: 2995479 - Vocera: 1004417452
 * Fecha: 18/03/2026 - 10:00 AM - Ambiente LEGO
 */

echo "🚀 CONVOCATORIA REAL - VERSIÓN OFFLINE\n";
echo "====================================\n\n";

// Datos de la vocera (simulados)
$vocera = [
    'nombre' => 'NOMBRE DE LA VOCERA', // Se actualizará con datos reales
    'documento' => '1004417452',
    'telefono' => 'CELULAR DE LA VOCERA',
    'correo' => 'CORREO DE LA VOCERA',
    'numero_ficha' => '2995479',
    'rol_vocero' => 'Vocero Principal', // O Suplente
    'instructor_lider' => 'NOMBRE DEL INSTRUCTOR'
];

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
echo "📚 Ficha: {$vocera['numero_ficha']}\n";
echo "👤 Vocera: {$vocera['documento']}\n\n";

// Generar mensajes
echo "📱 MENSAJES QUE SE ENVIARÁN:\n";
echo "==========================\n\n";

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

// Instrucciones para envío manual
echo "📋 INSTRUCCIONES PARA ENVÍO MANUAL:\n";
echo "===================================\n\n";

echo "📱 WHATSAPP:\n";
echo "1. Abrir WhatsApp\n";
echo "2. Buscar el número: {$vocera['telefono']}\n";
echo "3. Copiar y pegar el mensaje de arriba\n";
echo "4. Enviar\n\n";

echo "📱 SMS:\n";
echo "1. Abrir aplicación de mensajes\n";
echo "2. Enviar al número: {$vocera['telefono']}\n";
echo "3. Copiar y pegar el mensaje SMS de arriba\n";
echo "4. Enviar\n\n";

echo "📧 CORREO ELECTRÓNICO:\n";
echo "1. Abrir cliente de correo (Gmail, Outlook, etc.)\n";
echo "2. Nuevo correo\n";
echo "3. Para: {$vocera['correo']}\n";
echo "4. Asunto: {$asunto_correo}\n";
echo "5. Copiar y pegar el contenido HTML de arriba\n";
echo "6. Enviar\n\n";

echo "🎯 ¡LISTO PARA ENVIAR!\n";
echo "====================\n";
echo "✅ Mensajes generados para 3 medios\n";
echo "✅ Fecha reunión: {$reunion['fecha']} {$reunion['hora']}\n";
echo "✅ Lugar: {$reunion['lugar']}\n";
echo "✅ Vocera: Documento {$vocera['documento']}\n\n";

echo "📞 DATOS DE CONTACTO (ACTUALIZAR CON DATOS REALES):\n";
echo "• Teléfono: {$vocera['telefono']}\n";
echo "• Correo: {$vocera['correo']}\n\n";

echo "📋 AGENDA COMPLETA:\n";
foreach ($agenda as $item) {
    echo "• {$item}\n";
}
echo "\n";

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
