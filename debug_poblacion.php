<?php
require_once __DIR__ . '/api/config/Database.php';

header('Content-Type: text/plain');

try {
    $conn = Database::getInstance()->getConnection();
    echo "--- DIAGNÓSTICO DE POBLACIÓN ---\n\n";

    // 1. Conteo por columna tipo_poblacion
    echo "1. Conteo por columna 'tipo_poblacion' (usado por Liderazgo):\n";
    $res = $conn->query("SELECT tipo_poblacion, COUNT(*) as total FROM aprendices GROUP BY tipo_poblacion")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $row) {
        echo "  - " . ($row['tipo_poblacion'] ?: '[VACÍO]') . ": " . $row['total'] . "\n";
    }

    echo "\n2. Conteo por columnas booleanas (usado por Vocero):\n";
    $cols = ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
    foreach ($cols as $c) {
        try {
            $count = $conn->query("SELECT COUNT(*) FROM aprendices WHERE $c = 1")->fetchColumn();
            echo "  - $c: $count\n";
        } catch (Exception $e) {
            echo "  - $c: [ERROR O NO EXISTE]\n";
        }
    }

    echo "\n3. Muestra de inconsistencias (Boolean = 1 pero tipo_poblacion vacio):\n";
    $sql = "SELECT documento, nombre, apellido, mujer, indigena, narp, campesino, lgbtiq, discapacidad, tipo_poblacion 
            FROM aprendices 
            WHERE (mujer=1 OR indigena=1 OR narp=1 OR campesino=1 OR lgbtiq=1 OR discapacidad=1) 
            AND (tipo_poblacion IS NULL OR tipo_poblacion = '') 
            LIMIT 10";
    $inc = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if ($inc) {
        foreach ($inc as $row) {
            echo "  - " . $row['documento'] . ": " . $row['nombre'] . " (TP: " . ($row['tipo_poblacion'] ?: 'NULL') . ")\n";
        }
    } else {
        echo "  - No hay inconsistencias detectadas en la muestra.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
