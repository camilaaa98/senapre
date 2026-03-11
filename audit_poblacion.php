<?php
require_once __DIR__ . '/api/config/Database.php';
header('Content-Type: text/plain');

try {
    $conn = Database::getInstance()->getConnection();
    echo "--- AUDITORÍA PROFUNDA DE POBLACIÓN ---\n\n";

    // 1. Verificar existencia de tablas físicas
    echo "1. Tablas físicas detectadas:\n";
    $tablas = $conn->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['campesino', 'lgbtiq', 'lgbtiqplus', 'poblacion_lgbtiq'] as $t) {
        if (in_array($t, $tablas)) {
            $count = $conn->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "  - Tabla '$t' existe con $count registros.\n";
        }
    }

    // 2. Analizar variaciones en tipo_poblacion (TEXTO)
    echo "\n2. Variaciones en campo 'tipo_poblacion':\n";
    $res = $conn->query("SELECT tipo_poblacion, COUNT(*) as total FROM aprendices WHERE tipo_poblacion IS NOT NULL AND tipo_poblacion != '' GROUP BY tipo_poblacion")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $row) {
        echo "  - '" . $row['tipo_poblacion'] . "': " . $row['total'] . "\n";
    }

    // 3. Analizar columnas booleanas
    echo "\n3. Columnas booleanas específicas:\n";
    foreach (['campesino', 'lgbtiq', 'discapacidad'] as $col) {
        try {
            $count = $conn->query("SELECT COUNT(*) FROM aprendices WHERE $col = 1")->fetchColumn();
            echo "  - Columna '$col' marcada como 1: $count\n";
        } catch (Exception $e) {
            echo "  - Columna '$col' no existe en tabla aprendices.\n";
        }
    }

    // 4. Muestra de sospechosos (Cadenas que contienen 'camp' o 'lgb')
    echo "\n4. Búsqueda por similitud de texto:\n";
    $query = "SELECT documento, nombre, apellido, tipo_poblacion FROM aprendices 
              WHERE tipo_poblacion ILIKE '%camp%' OR tipo_poblacion ILIKE '%lgb%'";
    $sos = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sos as $s) {
        echo "  - " . $s['documento'] . ": " . $s['nombre'] . " " . $s['apellido'] . " -> [" . $s['tipo_poblacion'] . "]\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
