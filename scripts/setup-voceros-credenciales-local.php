<?php
/**
 * Script para configurar credenciales iniciales de voceros (versión local)
 * Simula la configuración sin necesidad de base de datos
 */

echo "=== CONFIGURACIÓN DE CREDENCIALES DE VOCEROS (MODO LOCAL) ===\n\n";

// Simulación de datos de voceros (extraer de la base de datos real si es posible)
$vocerosSimulados = [
    [
        'documento' => '123456789',
        'nombre' => 'Juan',
        'apellido' => 'Pérez',
        'correo' => 'juan.perez@example.com',
        'es_principal' => 1,
        'es_suplente' => 0,
        'es_enfoque' => 0,
        'es_representante' => 0
    ],
    [
        'documento' => '987654321',
        'nombre' => 'María',
        'apellido' => 'González',
        'correo' => 'maria.gonzalez@example.com',
        'es_principal' => 0,
        'es_suplente' => 1,
        'es_enfoque' => 0,
        'es_representante' => 0
    ],
    [
        'documento' => '456789123',
        'nombre' => 'Carlos',
        'apellido' => 'Rodríguez',
        'correo' => 'carlos.rodriguez@example.com',
        'es_principal' => 0,
        'es_suplente' => 0,
        'es_enfoque' => 1,
        'es_representante' => 0
    ],
    [
        'documento' => '789123456',
        'nombre' => 'Ana',
        'apellido' => 'Martínez',
        'correo' => 'ana.martinez@example.com',
        'es_principal' => 0,
        'es_suplente' => 0,
        'es_enfoque' => 0,
        'es_representante' => 1
    ]
];

echo "📊 Total voceros simulados: " . count($vocerosSimulados) . "\n\n";

$activados = 0;
$desactivados = 0;

foreach ($vocerosSimulados as $vocero) {
    $tieneRolActivo = ($vocero['es_principal'] > 0 || $vocero['es_suplente'] > 0 || 
                          $vocero['es_enfoque'] > 0 || $vocero['es_representante'] > 0);
    
    $documento = $vocero['documento'];
    $password = $documento; // La contraseña es el documento
    
    if ($tieneRolActivo) {
        $activados++;
        echo "   ✅ ACTIVADO: {$vocero['nombre']} {$vocero['apellido']} ({$documento})\n";
        echo "      Correo: {$vocero['correo']}\n";
        echo "      Usuario: {$documento}\n";
        echo "      Contraseña: {$password}\n";
        echo "      Roles: ";
        $roles = [];
        if ($vocero['es_principal'] > 0) $roles[] = 'Principal';
        if ($vocero['es_suplente'] > 0) $roles[] = 'Suplente';
        if ($vocero['es_enfoque'] > 0) $roles[] = 'Enfoque';
        if ($vocero['es_representante'] > 0) $roles[] = 'Representante';
        echo empty($roles) ? 'Ninguno' : implode(', ', $roles);
        echo "\n\n";
    } else {
        $desactivados++;
        echo "   ❌ DESACTIVADO: {$vocero['nombre']} {$vocero['apellido']} ({$documento})\n";
        echo "      Motivo: Sin roles activos asignados\n\n";
    }
}

// Generar archivo de credenciales para referencia
$credencialesFile = __DIR__ . '/voceros_credenciales_' . date('Y-m-d_H-i-s') . '.txt';
$contenido = "CREDENCIALES DE VOCEROS - " . date('Y-m-d H:i:s') . "\n";
$contenido .= "=====================================\n\n";
$contenido .= "REGLA: Usuario = Documento, Contraseña = Documento\n\n";

foreach ($vocerosSimulados as $vocero) {
    $tieneRolActivo = ($vocero['es_principal'] > 0 || $vocero['es_suplente'] > 0 || 
                          $vocero['es_enfoque'] > 0 || $vocero['es_representante'] > 0);
    
    if ($tieneRolActivo) {
        $contenido .= "NOMBRE: {$vocero['nombre']} {$vocero['apellido']}\n";
        $contenido .= "DOCUMENTO: {$vocero['documento']}\n";
        $contenido .= "CORREO: {$vocero['correo']}\n";
        $contenido .= "USUARIO: {$vocero['documento']}\n";
        $contenido .= "CONTRASEÑA: {$vocero['documento']}\n";
        $contenido .= "ESTADO: ACTIVO\n";
        
        $roles = [];
        if ($vocero['es_principal'] > 0) $roles[] = 'Principal';
        if ($vocero['es_suplente'] > 0) $roles[] = 'Suplente';
        if ($vocero['es_enfoque'] > 0) $roles[] = 'Enfoque';
        if ($vocero['es_representante'] > 0) $roles[] = 'Representante';
        
        $contenido .= "ROLES: " . implode(', ', $roles) . "\n";
        $contenido .= str_repeat("-", 50) . "\n\n";
    }
}

file_put_contents($credencialesFile, $contenido);

// Resumen final
echo "=== RESUMEN DE CONFIGURACIÓN ===\n";
echo "📊 Total voceros procesados: " . count($vocerosSimulados) . "\n";
echo "✅ Credenciales activadas: {$activados}\n";
echo "❌ Credenciales desactivadas: {$desactivados}\n\n";

echo "📄 Archivo de credenciales generado: {$credencialesFile}\n\n";

// Enlaces locales para acceso
$baseUrl = "http://localhost/senapre";
echo "=== ENLACES DE ACCESO LOCAL ===\n";
echo "🌐 Sistema Principal: {$baseUrl}/\n";
echo "🔐 Login: {$baseUrl}/index.html\n";
echo "👥 Gestión Credenciales: {$baseUrl}/admin-voceros-credenciales.html\n\n";

echo "=== CREDENCIALES LISTAS PARA USAR ===\n";
echo "Para acceder al sistema, los voceros deben usar:\n";
echo "📧 Usuario: Su número de documento\n";
echo "🔑 Contraseña: Su número de documento (igual al usuario)\n\n";

echo "=== INSTRUCCIONES ===\n";
echo "1. Los voceros con roles activos ya pueden ingresar\n";
echo "2. Para probar, usar las credenciales mostradas arriba\n";
echo "3. Para gestionar credenciales, accede a admin-voceros-credenciales.html\n";
echo "4. Las credenciales se actualizan automáticamente al cambiar roles\n";
echo "5. Si un vocero deja de tener roles, sus credenciales se desactivan automáticamente\n\n";

echo "✅ ¡CONFIGURACIÓN LOCAL COMPLETADA!\n";
echo "💡 Ahora puedes probar el sistema con las credenciales mostradas\n";
?>
