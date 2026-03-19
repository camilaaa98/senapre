<?php
/**
 * FLUJO COMPLETO DE AUTORIZACIÓN DE VOCEROS
 * Sistema de Notificaciones y Oficios Formales
 */

echo "🎭 FLUJO DE AUTORIZACIÓN DE VOCEROS - SENA\n";
echo "==========================================\n\n";

// Datos de ejemplo
$reunion = [
    'titulo' => 'Reunión Mensual de Voceros y Representantes',
    'fecha' => '2026-03-25',
    'hora' => '2:00 PM',
    'lugar' => 'Auditorio Principal - SENA Teleinformática',
    'ficha' => '2559099'
];

$instructor = [
    'nombre' => 'CARLOS GARCÍA',
    'correo' => 'carlos.garcia@sena.edu.co',
    'whatsapp' => '+57 300 987 6543'
];

$voceros = [
    [
        'nombre' => 'ANA MARÍA LÓPEZ',
        'documento' => '1087654321',
        'rol' => 'Vocero Principal',
        'whatsapp' => '+57 300 123 4567',
        'correo' => 'ana.lopez@email.com'
    ],
    [
        'nombre' => 'LUIS FERNANDO TORRES',
        'documento' => '1098765432',
        'rol' => 'Vocero Suplente',
        'whatsapp' => '+57 301 234 5678',
        'correo' => 'luis.torres@email.com'
    ]
];

echo "📅 DATOS DE LA REUNIÓN:\n";
echo "📋 Título: {$reunion['titulo']}\n";
echo "📆 Fecha: {$reunion['fecha']}\n";
echo "⏰ Hora: {$reunion['hora']}\n";
echo "📍 Lugar: {$reunion['lugar']}\n";
echo "📚 Ficha: {$reunion['ficha']}\n\n";

echo "👨‍🏫 INSTRUCTOR LÍDER:\n";
echo "👤 Nombre: {$instructor['nombre']}\n";
echo "📧 Correo: {$instructor['correo']}\n";
echo "📱 WhatsApp: {$instructor['whatsapp']}\n\n";

echo "🎭 PASO 1: CONVOCATORIA A INSTRUCTOR\n";
echo "===================================\n";
echo "📧 CORREO ELECTRÓNICO ENVIADO:\n";
echo "Para: {$instructor['correo']}\n";
echo "Asunto: SOLICITUD OFICIO AUTORIZACIÓN VOCEROS - Reunión {$reunion['fecha']}\n\n";
echo "Contenido:\n";
echo "\"Estimado Instructor {$instructor['nombre']},\n\n";
echo "Se solicita su autorización para la asistencia del vocero de la ficha {$reunion['ficha']} a la reunión del {$reunion['fecha']}.\n\n";
echo "VOCEROS DESIGNADOS:\n";
foreach ($voceros as $vocero) {
    echo "• {$vocero['nombre']} ({$vocero['rol']})\n";
}
echo "\n";
echo "REGLA IMPORTANTE: Solo puede autorizar a UNO de los dos.\n";
echo "El Vocero Principal tiene prioridad de asistencia.\n\n";
echo "Por favor:\n";
echo "1. Complete el oficio de autorización adjunto\n";
echo "2. Seleccione el vocero que asistirá\n";
echo "3. Firme y devuelva a la mayor brevedad posible\n\n";
echo "Acceda al oficio aquí: http://senapre.sena.edu.co/oficios/autorizar-vocero-{$reunion['ficha']}\n\n";
echo "Atentamente,\n";
echo "Coordinación de Bienestar\n";
echo "SENA - Centro de Teleinformática\"\n\n";

echo "📱 WHATSAPP AL INSTRUCTOR:\n";
echo "========================\n";
echo "\"👨‍🏫 Estimado Instructor {$instructor['nombre']}\n\n";
echo "📋 RECORDATORIO: Oficio de autorización pendiente\n\n";
echo "🎭 VOCEROS FICHA {$reunion['ficha']}:\n";
foreach ($voceros as $vocero) {
    echo "• {$vocero['nombre']} ({$vocero['rol']})\n";
}
echo "\n";
echo "📅 Reunión: {$reunion['fecha']} {$reunion['hora']}\n";
echo "📍 Lugar: {$reunion['lugar']}\n\n";
echo "⚠️ ACCIÓN REQUERIDA:\n";
echo "Complete el oficio de autorización lo antes posible.\n";
echo "Link directo: http://senapre.sena.edu.co/oficios/autorizar-vocero-{$reunion['ficha']}\n\n";
echo "🏢 SENA - Coordinación de Bienestar\"\n\n";

echo "🎭 PASO 2: INSTRUCTOR COMPLETA OFICIO\n";
echo "===================================\n";
echo "✅ Instructor accede al sistema\n";
echo "✅ Ve el oficio formal con logos SENA/SenApre\n";
echo "✅ Selecciona SOLO UN vocero:\n\n";

echo "📋 EJEMPLO DE SELECCIÓN:\n";
echo "========================\n";
echo "🎯 OPCIÓN 1 (Recomendada):\n";
echo "   ✅ AUTORIZADO: ANA MARÍA LÓPEZ (Vocero Principal)\n";
echo "   ❌ NO AUTORIZADO: LUIS FERNANDO TORRES (Vocero Suplente)\n\n";
echo "🔄 OPCIÓN 2 (Si el Principal no puede):\n";
echo "   ❌ NO AUTORIZADO: ANA MARÍA LÓPEZ (Vocero Principal)\n";
echo "   ✅ AUTORIZADO: LUIS FERNANDO TORRES (Vocero Suplente)\n\n";

echo "🎭 PASO 3: CONFIRMACIÓN AUTOMÁTICA\n";
echo "================================\n";
echo "✅ Oficio firmado y guardado en sistema\n";
echo "✅ Se genera PDF con firma digital\n";
echo "✅ Se envían notificaciones automáticas:\n\n";

// Simular que el instructor seleccionó al vocero principal
$vocero_autorizado = $voceros[0]; // Vocero Principal
$vocero_no_autorizado = $voceros[1]; // Vocero Suplente

echo "📱 NOTIFICACIÓN AL VOCERO AUTORIZADO:\n";
echo "====================================\n";
echo "Para: {$vocero_autorizado['nombre']}\n";
echo "WhatsApp: {$vocero_autorizado['whatsapp']}\n";
echo "Correo: {$vocero_autorizado['correo']}\n\n";
echo "📩 MENSAJE WHATSAPP:\n";
echo "\"🎭 Estimado/a {$vocero_autorizado['nombre']}\n\n";
echo "✅ ¡BUENA NOTICIA! Has sido autorizado/a para asistir a la reunión.\n\n";
echo "📋 CONVOCATORIA OFICIAL - SENA TELEINFORMÁTICA\n";
echo "🎭 Tu rol: {$vocero_autorizado['rol']}\n";
echo "📚 Ficha: {$reunion['ficha']}\n";
echo "📅 Reunión: {$reunion['titulo']}\n";
echo "🗓️ Fecha: {$reunion['fecha']}\n";
echo "⏰ Hora: {$reunion['hora']}\n";
echo "📍 Lugar: {$reunion['lugar']}\n\n";
echo "📄 Oficio de autorización: http://senapre.sena.edu.co/oficios/ver-{$vocero_autorizado['documento']}\n\n";
echo "✅ Por favor confirma asistencia respondiendo: ASISTIRÉ\n\n";
echo "🏢 SENA - Centro de Teleinformática\n";
echo "🔖 ID: VOC-{$vocero_autorizado['documento']}-{$reunion['fecha']}\"\n\n";

echo "📧 CORREO FORMAL AL VOCERO AUTORIZADO:\n";
echo "======================================\n";
echo "Asunto: OFICIO DE AUTORIZACIÓN - {$vocero_autorizado['rol']} - {$vocero_autorizado['nombre']}\n\n";
echo "Estimado/a {$vocero_autorizado['nombre']},\n\n";
echo "Por medio de la presente se le notifica que ha sido autorizado/a para asistir\n";
echo "a la {$reunion['titulo']} en su calidad de {$vocero_autorizado['rol']}.\n\n";
echo "📅 Fecha: {$reunion['fecha']}\n";
echo "⏰ Hora: {$reunion['hora']}\n";
echo "📍 Lugar: {$reunion['lugar']}\n\n";
echo "Se adjunta copia del oficio de autorización firmado por el instructor.\n\n";
echo "Es requisito indispensable confirmar su asistencia.\n\n";
echo "Atentamente,\n";
echo "Coordinación de Bienestar\n";
echo "SENA - Centro de Teleinformática y Producción Industrial\n\n";

echo "📱 NOTIFICACIÓN AL VOCERO NO AUTORIZADO:\n";
echo "======================================\n";
echo "Para: {$vocero_no_autorizado['nombre']}\n";
echo "WhatsApp: {$vocero_no_autorizado['whatsapp']}\n\n";
echo "📩 MENSAJE WHATSAPP:\n";
echo "\"🎭 Estimado/a {$vocero_no_autorizado['nombre']}\n\n";
echo "ℹ️ INFORMACIÓN IMPORTANTE:\n\n";
echo "Se ha autorizado la asistencia del vocero principal para la reunión del {$reunion['fecha']}.\n\n";
echo "📋 Como {$vocero_no_autorizado['rol']}, tu participación no será requerida en esta ocasión.\n\n";
echo "🎯 Seguimos contando con tu compromiso y participación en futuras actividades.\n\n";
echo "🏢 SENA - Coordinación de Bienestar\"\n\n";

echo "🎭 PASO 4: SEGUIMIENTO Y CONFIRMACIÓN\n";
echo "====================================\n";
echo "📊 DASHBOARD DE SEGUIMIENTO:\n";
echo "✅ Total voceros convocados: " . count($voceros) . "\n";
echo "✅ Vocero autorizado: {$vocero_autorizado['nombre']}\n";
echo "⏳ Esperando confirmación de asistencia...\n";
echo "📧 Correos enviados: 2 (voceros) + 1 (instructor) = 3\n";
echo "📱 WhatsApp enviados: 3\n";
echo "📄 Oficios generados: 1\n\n";

echo "🎯 BENEFICIOS DEL SISTEMA:\n";
echo "========================\n";
echo "✅ Proceso 100% automatizado\n";
echo "✅ Oficios formales con logos SENA/SenApre\n";
echo "✅ Respeto jerarquía (Principal > Suplente)\n";
echo "✅ Registro documentado completo\n";
echo "✅ Comunicación multi-canal\n";
echo "✅ Ahorro de tiempo (90% menos trabajo manual)\n";
echo "✅ Imagen profesional del SENA\n";
echo "✅ Control total del proceso\n\n";

echo "🚀 ¡ESTE SISTEMA HARÍA EL PROCESO PERFECTO!\n";
?>
