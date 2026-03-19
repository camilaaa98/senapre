<?php
/**
 * SENAPRE Database Initialization Script
 * Purpose: Create tables and essential schema on new environments (Render, etc.)
 */
header('Content-Type: text/plain');
require_once __DIR__ . '/config/Database.php';

try {
    echo "=== Iniciando Inicialización de Base de Datos ===\n";
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Ruta al archivo del esquema
    $schemaPath = __DIR__ . '/../database/postgres_schema.sql';
    
    if (!file_exists($schemaPath)) {
        throw new Exception("No se encontró el archivo del esquema en: $schemaPath");
    }
    
    $sql = file_get_contents($schemaPath);
    
    // Eliminar comentarios de una línea del SQL para evitar errores en exec
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    
    echo "Ejecutando esquema SQL...\n";
    $conn->exec($sql);
    echo "✅ Esquema de base de datos creado exitosamente.\n";
    
    // Corrección CTPI a CTA en tablas de configuración si existen
    // (Ejemplo: en la tabla de centros o configuraciones)
    try {
        $conn->exec("UPDATE configuracion SET valor = 'Centro de Tecnología Agroindustrial (CTA)' WHERE valor LIKE '%Teleinformática%'");
        echo "✅ Nombre del centro actualizado a CTA.\n";
    } catch (Exception $e) { /* Tabla puede no existir aún */ }

    echo "=== Proceso Finalizado con Éxito ===\n";

} catch (Exception $e) {
    http_response_code(500);
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    error_log("DB Init Error: " . $e->getMessage());
}
