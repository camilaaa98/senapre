<?php

/**
 * Analyze Excel data to identify what needs to be populated
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Análisis de Datos para Poblar Tablas ===\n\n";

    // 1. Obtener fichas únicas de los aprendices
    $stmt = $conn->query("SELECT DISTINCT id_ficha FROM aprendices ORDER BY id_ficha");
    $fichas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "→ Fichas únicas encontradas en aprendices: " . count($fichas) . "\n";
    echo "Ejemplos: " . implode(', ', array_slice($fichas, 0, 5)) . "...\n\n";

    // 2. Verificar qué tablas están vacías
    $tables = ['usuarios', 'instructores', 'fichas', 'programas_formacion', 'asignaciones_instructor_ficha'];

    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM $table");
        $count = $stmt->fetch()['total'];
        echo "→ Tabla '$table': $count registros\n";
    }

    echo "\n=== Plan de Acción ===\n";
    echo "1. Crear fichas basadas en los id_ficha de aprendices\n";
    echo "2. Crear programas de formación (necesarios para fichas)\n";
    echo "3. Crear usuarios e instructores (para poder asignar fichas)\n";
    echo "4. Crear asignaciones instructor-ficha\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
