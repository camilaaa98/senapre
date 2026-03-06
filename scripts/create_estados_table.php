<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Crear tabla estados
    $sqlCreate = "CREATE TABLE IF NOT EXISTS estados (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL UNIQUE,
        color TEXT NOT NULL
    )";
    $conn->exec($sqlCreate);
    echo "Tabla 'estados' verificada/creada.\n";

    // 2. Datos iniciales
    $estados = [
        ['nombre' => 'INDUCCION',      'color' => '#fd7e14'], // Naranja
        ['nombre' => 'LECTIVA',        'color' => '#6c757d'], // Gris
        ['nombre' => 'PRODUCTIVA',     'color' => '#ffc107'], // Amarillo
        ['nombre' => 'FINALIZADA',     'color' => '#dc3545'], // Rojo
        ['nombre' => 'RETIRO',         'color' => '#dc3545'], // Rojo
        ['nombre' => 'CANCELADA',      'color' => '#dc3545'], // Rojo
        ['nombre' => 'POR CERTIFICAR', 'color' => '#0d6efd'], // Azul
        ['nombre' => 'CERTIFICADO',    'color' => '#198754']  // Verde
    ];

    $stmtInsert = $conn->prepare("INSERT OR IGNORE INTO estados (nombre, color) VALUES (:nombre, :color)");
    $stmtUpdate = $conn->prepare("UPDATE estados SET color = :color WHERE nombre = :nombre");

    foreach ($estados as $estado) {
        // Insertar si no existe
        $stmtInsert->execute([':nombre' => $estado['nombre'], ':color' => $estado['color']]);
        
        // Actualizar color si ya existe (para asegurar consistencia)
        $stmtUpdate->execute([':nombre' => $estado['nombre'], ':color' => $estado['color']]);
        
        echo "Validado estado: " . $estado['nombre'] . "\n";
    }

    echo "Migración de estados completada exitosamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
