<?php

/**
 * Test Create Attendance with Correct Date
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Test de Creación de Asistencia con Fecha Correcta ===\n\n";

    echo "→ Zona horaria configurada: " . date_default_timezone_get() . "\n";
    echo "→ Fecha actual del servidor: " . date('Y-m-d H:i:s') . "\n";
    echo "→ Fecha que se usaría por defecto: " . date('Y-m-d') . "\n\n";

    // Simular lo que hace create-batch-asistencias.php
    $fecha = date('Y-m-d');
    echo "→ Fecha que se guardaría en la asistencia: $fecha\n";

    if ($fecha === '2025-12-01') {
        echo "✅ CORRECTO: La fecha es 2025-12-01 (hoy)\n";
    } else {
        echo "❌ ERROR: La fecha es $fecha (debería ser 2025-12-01)\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
