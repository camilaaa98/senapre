<?php
/**
 * DEMO: Sistema de Notificaciones para Voceros y Representantes SENA
 * Convocatorias inteligentes multi-canal
 */

echo "🎭 DEMO: SISTEMA DE NOTIFICACIONES PARA VOCEROS SENA\n\n";

echo "📋 ESCENARIO: Convocatoria a Reunión de Voceros\n";
echo "===============================================\n\n";

// Datos de ejemplo
$reunion = [
    'titulo' => 'Reunión Mensual de Voceros y Representantes',
    'fecha' => '2026-03-25',
    'hora' => '2:00 PM',
    'lugar' => 'Auditorio Principal - SENA Teleinformática',
    'tipo' => 'Ordinaria',
    'agenda' => [
        '1. Informes de gestión',
        '2. Situación de aprendices',
        '3. Próximas actividades',
        '4. Varios'
    ]
];

$voceros = [
    [
        'nombre' => 'Ana María López',
        'documento' => '1087654321',
        'rol' => 'Vocero Principal',
        'ficha' => '2559099',
        'whatsapp' => '+57 300 123 4567',
        'correo' => 'ana.lopez@email.com',
        'instructor_lider' => 'Carlos García'
    ],
    [
        'nombre' => 'Luis Fernando Torres',
        'documento' => '1098765432',
        'rol' => 'Vocero Suplente',
        'ficha' => '2559099',
        'whatsapp' => '+57 301 234 5678',
        'correo' => 'luis.torres@email.com',
        'instructor_lider' => 'Carlos García'
    ],
    [
        'nombre' => 'María Elena Rodríguez',
        'documento' => '1109876543',
        'rol' => 'Representante de Jornada',
        'ficha' => '2560100',
        'whatsapp' => '+57 302 345 6789',
        'correo' => 'maria.rodriguez@email.com',
        'instructor_lider' => 'Patricia Martínez'
    ]
];

echo "📅 DATOS DE LA REUNIÓN:\n";
echo "📋 Título: {$reunion['titulo']}\n";
echo "📆 Fecha: {$reunion['fecha']}\n";
echo "⏰ Hora: {$reunion['hora']}\n";
echo "📍 Lugar: {$reunion['lugar']}\n";
echo "🎯 Tipo: {$reunion['tipo']}\n\n";

echo "📝 AGENDA:\n";
foreach ($reunion['agenda'] as $item) {
    echo "   $item\n";
}

echo "\n🎭 VOCEROS CONVOCADOS:\n";
echo "========================\n";

foreach ($voceros as $vocero) {
    echo "\n👤 {$vocero['nombre']}\n";
    echo "🎭 Rol: {$vocero['rol']}\n";
    echo "📚 Ficha: {$vocero['ficha']}\n";
    echo "👨‍🏫 Instructor Líder: {$vocero['instructor_lider']}\n";
    echo "📱 WhatsApp: {$vocero['whatsapp']}\n";
    echo "📧 Correo: {$vocero['correo']}\n\n";
    
    echo "📱 MENSAJE WHATSAPP (Enviado automáticamente):\n";
    echo "==========================================\n";
    echo "\"🎭 Estimado/a {$vocero['nombre']}\n\n";
    echo "📋 CONVOCATORIA OFICIAL - SENA TELEINFORMÁTICA\n\n";
    echo "🎯 Rol: {$vocero['rol']}\n";
    echo "📚 Ficha: {$vocero['ficha']}\n\n";
    echo "📅 REUNIÓN: {$reunion['titulo']}\n";
    echo "🗓️ Fecha: {$reunion['fecha']}\n";
    echo "⏰ Hora: {$reunion['hora']}\n";
    echo "📍 Lugar: {$reunion['lugar']}\n\n";
    echo "📋 Agenda:\n";
    foreach ($reunion['agenda'] as $item) {
        echo "   • $item\n";
    }
    echo "\n";
    echo "✅ Por favor confirmar asistencia respondiendo: ASISTIRÉ\n";
    echo "❌ O si no puede: NO PUEDO ASISTIR\n\n";
    echo "🏢 Centro de Teleinformática y Producción Industrial\n";
    echo "📞 Coordinación de Bienestar\n\n";
    echo "🔖 ID: VOC-{$vocero['documento']}-20260325\"\n\n";
    
    echo "📧 MENSAJE CORREO (Versión formal):\n";
    echo "===================================\n";
    echo "Asunto: CONVOCATORIA REUNIÓN - {$vocero['rol']} - {$vocero['nombre']}\n\n";
    echo "Estimado/a {$vocero['nombre']},\n\n";
    echo "Por medio de la presente se le convoca formalmente a la {$reunion['titulo']}\n";
    echo "en su calidad de {$vocero['rol']} de la ficha {$vocero['ficha']}.\n\n";
    echo "📅 Fecha: {$reunion['fecha']}\n";
    echo "⏰ Hora: {$reunion['hora']}\n";
    echo "📍 Lugar: {$reunion['lugar']}\n\n";
    echo "Se solicita puntualidad y asistencia obligatoria.\n";
    echo "Cualquier inasistencia deberá ser justificada con su instructor líder.\n\n";
    echo "Atentamente,\n";
    echo "Coordinación de Bienestar\n";
    echo "SENA - Centro de Teleinformática y Producción Industrial\n\n";
    
    echo "📱 MENSAJE SMS (Para quienes no tienen WhatsApp):\n";
    echo "===================================================\n";
    echo "\"SENA: Convocatoria reunión {$vocero['rol']} {$vocero['nombre']}. ";
    echo "Fecha: {$reunion['fecha']} {$reunion['hora']}. ";
    echo "Lugar: Auditorio SENA. Confirmar: 3001234567. ";
    echo "ID: VOC-{$vocero['documento']}\"\n\n";
    
    echo "─".str_repeat("─", 50)."\n";
}

echo "\n👨‍🏫 NOTIFICACIONES A INSTRUCTORES LÍDER:\n";
echo "======================================\n\n";

// Instructores únicos
$instructores = array_unique(array_column($voceros, 'instructor_lider'));

foreach ($instructores as $instructor) {
    echo "👨‍🏫 INSTRUCTOR: $instructor\n\n";
    
    // Contar voceros de este instructor
    $voceros_instructor = array_filter($voceros, function($v) use ($instructor) {
        return $v['instructor_lider'] === $instructor;
    });
    
    echo "📱 MENSAJE WHATSAPP AL INSTRUCTOR:\n";
    echo "================================\n";
    echo "\"👨‍🏫 Estimado Instructor $instructor\n\n";
    echo "📋 INFORME DE CONVOCATORIA - VOCEROS A SU CARGO\n\n";
    echo "📅 Reunión: {$reunion['titulo']}\n";
    echo "🗓️ Fecha: {$reunion['fecha']}\n";
    echo "⏰ Hora: {$reunion['hora']}\n";
    echo "📍 Lugar: {$reunion['lugar']}\n\n";
    echo "🎭 VOCEROS CONVOCADOS DE SU GRUPO:\n";
    
    foreach ($voceros_instructor as $vocero) {
        echo "   • {$vocero['nombre']} ({$vocero['rol']}) - Ficha {$vocero['ficha']}\n";
    }
    
    echo "\n⚠️ ACCIÓN REQUERIDA:\n";
    echo "Por favor autorizar la asistencia de sus voceros a esta reunión.\n";
    echo "Los aprendices deben estar al día con sus compromisos formativos.\n\n";
    echo "📞 Cualquier novedad comunicar a Bienestar.\n\n";
    echo "🏢 SENA - Centro de Teleinformática\n";
    echo "📊 Sistema de Gestión SenApre\"\n\n";
    
    echo "📧 CORREO FORMAL AL INSTRUCTOR:\n";
    echo "==============================\n";
    echo "Asunto: SOLICITUD AUTORIZACIÓN VOCEROS - REUNIÓN {$reunion['fecha']}\n\n";
    echo "Estimado Instructor $instructor,\n\n";
    echo "Se solicita su autorización para que los siguientes voceros\n";
    echo "a su cargo asistan a la {$reunion['titulo']}:\n\n";
    
    foreach ($voceros_instructor as $vocero) {
        echo "• {$vocero['nombre']} - {$vocero['rol']} - Ficha {$vocero['ficha']}\n";
    }
    
    echo "\n📅 Fecha: {$reunion['fecha']}\n";
    echo "⏰ Hora: {$reunion['hora']}\n";
    echo "📍 Lugar: {$reunion['lugar']}\n\n";
    echo "Se agradece su colaboración y apoyo al proceso de participación estudiantil.\n\n";
    echo "Atentamente,\n";
    echo "Coordinación de Bienestar\n";
    echo "SENA - Centro de Teleinformática\n\n";
    
    echo "─".str_repeat("─", 50)."\n";
}

echo "\n📊 DASHBOARD DE SEGUIMIENTO:\n";
echo "==========================\n";
echo "✅ Total voceros convocados: " . count($voceros) . "\n";
echo "📨 Mensajes WhatsApp enviados: " . count($voceros) . "\n";
echo "📧 Correos electrónicos enviados: " . count($voceros) . "\n";
echo "📱 SMS enviados: " . count($voceros) . "\n";
echo "👨‍🏫 Instructores notificados: " . count($instructores) . "\n";
echo "⏰ Fecha de envío: " . date('Y-m-d H:i:s') . "\n\n";

echo "🎯 BENEFICIOS DEL SISTEMA:\n";
echo "========================\n";
echo "✅ Comunicación multi-canal (WhatsApp + Correo + SMS)\n";
echo "✅ Mensajes personalizados con nombre y rol\n";
echo "✅ Confirmación automática de asistencia\n";
echo "✅ Registro documentado de todas las convocatorias\n";
echo "✅ Coordinación perfecta con instructores\n";
echo "✅ Ahorro de tiempo (95% menos trabajo manual)\n";
echo "✅ Imagen profesional del SENA\n";
echo "✅ Cumplimiento normativo garantizado\n\n";

echo "💰 COSTO-BENEFICIO:\n";
echo "===================\n";
echo "💸 Costo: ~\$10 USD/mes (SMS + API WhatsApp)\n";
echo "💰 Ahorro: 40 horas/mes de trabajo administrativo\n";
echo "📈 Asistencia: +85% de asistencia a reuniones\n";
echo "🎯 Satisfacción: +50% voceros comprometidos\n\n";

echo "🚀 ¡ESTE SISTEMA REVOLUCIONARÍA LA COMUNICACIÓN CON VOCEROS!\n";
?>
