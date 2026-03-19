<?php
/**
 * SCRIPT DE TESTING PARA VOCERA ESPECÍFICA
 * Ficha: 2995479 - Vocera: 1004417452
 * Fecha: 18/03/2026 - Ambiente LEGO - Centro Tecnológico de la Amazonia
 */

echo "🧪 TESTING ESPECÍFICO PARA VOCERA\n";
echo "===================================\n\n";

// Datos de la vocera
$vocera_documento = '1004417452';
$ficha_numero = '2995479';

// Datos de la reunión
$reunion = [
    'titulo' => 'Reunión de Voceros',
    'fecha' => '2026-03-18',
    'hora' => '10:00 AM',
    'lugar' => 'Ambiente de LEGO - Centro Tecnológico de la Amazonia',
    'tipo' => 'Ordinaria'
];

echo "📋 DATOS DE LA REUNIÓN DE PRUEBA:\n";
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
    
    // Mostrar aprendices disponibles en esa ficha
    echo "\n📚 APRENDICES DISPONIBLES EN LA FICHA {$ficha_numero}:\n";
    $stmt = $conn->prepare("
        SELECT a.*, f.vocero_principal, f.vocero_suplente
        FROM aprendices a
        JOIN fichas f ON a.numero_ficha = f.numero_ficha
        WHERE f.numero_ficha = :ficha
        AND a.estado = 'LECTIVA'
        ORDER BY a.nombre
    ");
    $stmt->execute([':ficha' => $ficha_numero]);
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($aprendices as $aprendiz) {
        $rol = 'Aprendiz';
        if ($aprendiz['documento'] == $aprendiz['vocero_principal']) $rol = '🎭 Vocero Principal';
        elseif ($aprendiz['documento'] == $aprendiz['vocero_suplente']) $rol = '🔄 Vocero Suplente';
        
        echo "• {$aprendiz['nombre']} ({$aprendiz['documento']}) - {$rol}\n";
    }
    exit;
}

echo "✅ Vocera encontrada:\n";
echo "👤 Nombre: {$vocera['nombre']}\n";
echo "📞 Teléfono: {$vocera['telefono']}\n";
echo "📧 Correo: {$vocera['correo']}\n";
echo "🎭 Rol: {$vocera['rol_vocero']}\n";
echo "👨‍🏫 Instructor: {$vocera['instructor_lider']}\n\n";

// Buscar datos del instructor
echo "🔍 BUSCANDO DATOS DEL INSTRUCTOR...\n";
$stmt = $conn->prepare("
    SELECT * FROM usuarios 
    WHERE nombre LIKE :nombre 
    AND rol IN ('instructor', 'director')
    LIMIT 1
");

$stmt->execute([':nombre' => "%{$vocera['instructor_lider']}%"]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($instructor) {
    echo "✅ Instructor encontrado:\n";
    echo "👨‍🏫 Nombre: {$instructor['nombre']}\n";
    echo "📧 Correo: {$instructor['correo']}\n";
    echo "📱 Teléfono: {$instructor['telefono']}\n\n";
} else {
    echo "⚠️ No se encontró el instructor {$vocera['instructor_lider']}\n\n";
}

// Generar mensajes de prueba
echo "📱 GENERANDO MENSAJES DE PRUEBA...\n\n";

// Mensaje de WhatsApp
$mensaje_whatsapp = generarMensajeWhatsAppVocera($vocera, $reunion);
echo "📱 MENSAJE WHATSAPP:\n";
echo "==================\n";
echo $mensaje_whatsapp . "\n\n";

// Mensaje de SMS
$mensaje_sms = generarMensajeSMSVocera($vocera, $reunion);
echo "📱 MENSAJE SMS:\n";
echo "==============\n";
echo $mensaje_sms . "\n\n";

// Asunto de correo
$asunto_correo = "OFICIO DE CONVOCATORIA - {$vocera['rol_vocero']} - {$vocera['nombre']}";

// Contenido de correo
$contenido_correo = generarCorreoVocera($vocera, $instructor, $reunion);
echo "📧 CORREO ELECTRÓNICO:\n";
echo "===================\n";
echo "Asunto: {$asunto_correo}\n\n";
echo $contenido_correo . "\n\n";

// Simular envío
echo "🚀 SIMULANDO ENVÍO DE COMUNICACIONES...\n";
echo "========================================\n";

// Registrar en base de datos (simulación)
$convocatoria_id = 'TEST_' . time();
$envios = [];

// Envío WhatsApp
$envios[] = [
    'medio' => 'WhatsApp',
    'destinatario' => $vocera['telefono'],
    'mensaje' => substr($mensaje_whatsapp, 0, 100) . '...',
    'estado' => '✅ Simulado exitosamente'
];

// Envío SMS
$envios[] = [
    'medio' => 'SMS',
    'destinatario' => $vocera['telefono'],
    'mensaje' => $mensaje_sms,
    'estado' => '✅ Simulado exitosamente'
];

// Envío Correo
$envios[] = [
    'medio' => 'Correo Electrónico',
    'destinatario' => $vocera['correo'],
    'mensaje' => $asunto_correo,
    'estado' => '✅ Simulado exitosamente'
];

foreach ($envios as $envio) {
    echo "{$envio['medio']}:\n";
    echo "  📱 Para: {$envio['destinatario']}\n";
    echo "  📄 Mensaje: {$envio['mensaje']}\n";
    echo "  📊 Estado: {$envio['estado']}\n\n";
}

// Resumen final
echo "📊 RESUMEN DEL TESTING:\n";
echo "======================\n";
echo "✅ Vocera identificada: {$vocera['nombre']}\n";
echo "✅ Mensajes generados: 3 (WhatsApp, SMS, Correo)\n";
echo "✅ Envíos simulados: 3\n";
echo "✅ Fecha de reunión: {$reunion['fecha']}\n";
echo "✅ Lugar: {$reunion['lugar']}\n\n";

echo "🎯 TESTING COMPLETADO EXITOSAMENTE!\n";
echo "=====================================\n";
echo "Para activar los envíos reales, configura:\n";
echo "• API de WhatsApp Business\n";
echo "• Servicio de correo (PHPMailer)\n";
echo "• API de SMS\n\n";

echo "📞 DATOS DE CONTACTO DE LA VOCERA:\n";
echo "• Teléfono: {$vocera['telefono']}\n";
echo "• Correo: {$vocera['correo']}\n";
echo "• Nombre: {$vocera['nombre']}\n\n";

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

function generarCorreoVocera($vocera, $instructor, $reunion) {
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
