<?php
/**
 * MENSAJES FINALES PARA CAMILA GUEVARA
 * Ficha: 2995479 - Documento: 1004417452
 * Fecha: 18/03/2026 - 10:00 AM - Ambiente LEGO
 */

echo "🎯 MENSAJES FINALES PARA CAMILA GUEVARA\n";
echo "=====================================\n\n";

// Datos reales de la vocera
$vocera = [
    'nombre' => 'Camila Guevara',
    'documento' => '1004417452',
    'telefono' => '3194494992',
    'correo' => 'campguevara@gmail.com',
    'numero_ficha' => '2995479',
    'rol_vocero' => 'Vocero Principal', // Asumimos que es Principal
    'instructor_lider' => 'NOMBRE DEL INSTRUCTOR'
];

// Datos de la reunión
$reunion = [
    'titulo' => 'Reunión de Voceros',
    'fecha' => '2026-03-18',
    'hora' => '10:00 AM',
    'lugar' => 'Ambiente de LEGO - Centro Tecnológico de la Amazonia',
    'tipo' => 'Ordinaria'
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
echo "👤 Vocera: {$vocera['nombre']}\n";
echo "📞 Teléfono: {$vocera['telefono']}\n";
echo "📧 Correo: {$vocera['correo']}\n";
echo "📚 Ficha: {$vocera['numero_ficha']}\n";
echo "📅 Fecha: {$reunion['fecha']} {$reunion['hora']}\n";
echo "📍 Lugar: {$reunion['lugar']}\n\n";

// Mensaje WhatsApp final
$mensaje_whatsapp = "🎭 *¡CONVOCATORIA REUNIÓN DE VOCEROS!*\n\n";
$mensaje_whatsapp .= "👋 Hola {$vocera['nombre']}\n\n";
$mensaje_whatsapp .= "📋 *ESTÁS CONVOCADO/A* a:\n";
$mensaje_whatsapp .= "🎯 *{$reunion['titulo']}*\n\n";
$mensaje_whatsapp .= "📅 *Fecha*: " . date('d/m/Y', strtotime($reunion['fecha'])) . "\n";
$mensaje_whatsapp .= "⏰ *Hora*: {$reunion['hora']}\n";
$mensaje_whatsapp .= "📍 *Lugar*: {$reunion['lugar']}\n";
$mensaje_whatsapp .= "🎭 *Tu rol*: {$vocera['rol_vocero']}\n";
$mensaje_whatsapp .= "📚 *Ficha*: {$vocera['numero_ficha']}\n\n";
$mensaje_whatsapp .= "✅ *IMPORTANTE*: \n";
$mensaje_whatsapp .= "Tu instructor debe autorizar tu asistencia. \n";
$mensaje_whatsapp .= "Espera el oficio oficial para confirmar.\n\n";
$mensaje_whatsapp .= "🏢 *SENA - Centro Tecnológico de la Amazonia* \n";
$mensaje_whatsapp .= "🔖 *ID: VOC-{$vocera['documento']}-" . date('Ymd') . "*";

echo "📱 MENSAJE WHATSAPP (COPIAR Y PEGAR):\n";
echo "=====================================\n";
echo $mensaje_whatsapp . "\n\n";

// Mensaje SMS final
$mensaje_sms = "SENA: Convocatoria reunion {$vocera['rol_vocero']} {$vocera['nombre']}. ";
$mensaje_sms .= "Fecha: " . date('d/m/Y', strtotime($reunion['fecha'])) . " {$reunion['hora']}. ";
$mensaje_sms .= "Lugar: {$reunion['lugar']}. ";
$mensaje_sms .= "Ficha: {$vocera['numero_ficha']}. ";
$mensaje_sms .= "ID: VOC-{$vocera['documento']}-" . date('Ymd');

echo "📱 MENSAJE SMS (COPIAR Y PEGAR):\n";
echo "=================================\n";
echo $mensaje_sms . "\n\n";

// Correo final
$asunto_correo = "OFICIO DE CONVOCATORIA - {$vocera['rol_vocero']} - {$vocera['nombre']}";

$contenido_correo = "<div style='font-family: Arial; max-width: 800px; margin: 0 auto;'>";
$contenido_correo .= "<div style='text-align: center; padding: 20px; border-bottom: 2px solid #0066cc;'>";
$contenido_correo .= "<h1 style='color: #0066cc; margin: 0;'>OFICIO DE CONVOCATORIA</h1>";
$contenido_correo .= "<p style='margin: 5px 0;'>SERVICIO NACIONAL DE APRENDIZAJE - SENA</p>";
$contenido_correo .= "<p style='margin: 5px 0;'>Centro Tecnológico de la Amazonia</p>";
$contenido_correo .= "</div>";
$contenido_correo .= "<div style='padding: 30px;'>";
$contenido_correo .= "<p><strong>Fecha:</strong> " . date('d/m/Y') . "</p>";
$contenido_correo .= "<p><strong>Para:</strong> {$vocera['nombre']}</p>";
$contenido_correo .= "<p><strong>De:</strong> Coordinación de Bienestar</p>";
$contenido_correo .= "<p><strong>Asunto:</strong> CONVOCATORIA REUNIÓN DE VOCEROS</p><br>";
$contenido_correo .= "<p>Estimado/a {$vocera['nombre']},</p>";
$contenido_correo .= "<p>Por medio de la presente se le convoca formalmente a la reunión de voceros programada.</p>";
$contenido_correo .= "<div style='background: #f8f9fa; padding: 20px; border-left: 4px solid #0066cc; margin: 20px 0;'>";
$contenido_correo .= "<h3 style='color: #0066cc; margin-top: 0;'>DATOS DE LA REUNIÓN</h3>";
$contenido_correo .= "<p><strong>Título:</strong> {$reunion['titulo']}</p>";
$contenido_correo .= "<p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($reunion['fecha'])) . "</p>";
$contenido_correo .= "<p><strong>Hora:</strong> {$reunion['hora']}</p>";
$contenido_correo .= "<p><strong>Lugar:</strong> {$reunion['lugar']}</p>";
$contenido_correo .= "<p><strong>Ficha:</strong> {$vocera['numero_ficha']}</p>";
$contenido_correo .= "<p><strong>Tu rol:</strong> {$vocera['rol_vocero']}</p>";
$contenido_correo .= "</div>";
$contenido_correo .= "<div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
$contenido_correo .= "<h3 style='color: #856404; margin-top: 0;'>AGENDA DE LA REUNIÓN</h3>";
$contenido_correo .= "<ol>";
foreach ($agenda as $item) {
    $contenido_correo .= "<li>{$item}</li>";
}
$contenido_correo .= "</ol>";
$contenido_correo .= "</div>";
$contenido_correo .= "<p>Se requiere su puntual asistencia y participación activa en esta reunión.</p>";
$contenido_correo .= "<p>Atentamente,</p>";
$contenido_correo .= "<p><strong>Coordinación de Bienestar</strong><br>";
$contenido_correo .= "SENA - Centro Tecnológico de la Amazonia</p>";
$contenido_correo .= "</div>";
$contenido_correo .= "<div style='text-align: center; padding: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;'>";
$contenido_correo .= "<p>Servicio Nacional de Aprendizaje - SENA</p>";
$contenido_correo .= "<p>Centro Tecnológico de la Amazonia</p>";
$contenido_correo .= "<p>Sistema de Gestión SenApre - Oficio No. " . date('Y-m-d') . "-001</p>";
$contenido_correo .= "<p>Generado: " . date('d/m/Y H:i') . "</p>";
$contenido_correo .= "</div>";
$contenido_correo .= "</div>";

echo "📧 CORREO ELECTRÓNICO:\n";
echo "=====================\n";
echo "Asunto: {$asunto_correo}\n\n";
echo "Contenido HTML:\n";
echo $contenido_correo . "\n\n";

// Instrucciones finales
echo "🚀 INSTRUCCIONES FINALES DE ENVÍO:\n";
echo "===================================\n\n";

echo "📱 WHATSAPP:\n";
echo "1. Abrir WhatsApp\n";
echo "2. Buscar el contacto: +57 {$vocera['telefono']}\n";
echo "3. Copiar y pegar el mensaje de arriba\n";
echo "4. Enviar\n\n";

echo "📱 SMS:\n";
echo "1. Abrir aplicación de mensajes\n";
echo "2. Enviar al número: +57 {$vocera['telefono']}\n";
echo "3. Copiar y pegar el mensaje SMS de arriba\n";
echo "4. Enviar\n\n";

echo "📧 CORREO ELECTRÓNICO:\n";
echo "1. Abrir Gmail o Outlook\n";
echo "2. Nuevo correo\n";
echo "3. Para: {$vocera['correo']}\n";
echo "4. Asunto: {$asunto_correo}\n";
echo "5. Pegar el contenido HTML\n";
echo "6. Enviar\n\n";

echo "🎯 ¡TODOS LOS MENSAJES LISTOS PARA ENVIAR!\n";
echo "========================================\n";
echo "✅ Vocera: {$vocera['nombre']}\n";
echo "✅ Teléfono: +57 {$vocera['telefono']}\n";
echo "✅ Correo: {$vocera['correo']}\n";
echo "✅ Reunión: {$reunion['fecha']} {$reunion['hora']}\n";
echo "✅ Lugar: {$reunion['lugar']}\n\n";

echo "📋 AGENDA COMPLETA:\n";
foreach ($agenda as $item) {
    echo "• {$item}\n";
}
echo "\n";

echo "🔔 IMPORTANTE:\n";
echo "• Los mensajes están personalizados con datos reales\n";
echo "• Incluyen ID único de seguimiento\n";
echo "• Tono formal pero cercano\n";
echo "• Información completa y clara\n\n";

echo "✨ ¡LISTO PARA ENVIAR LAS COMUNICACIONES! ✨\n";
?>
