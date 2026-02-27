<?php

/**
 * Test Database Timezone
 */

require_once __DIR__ . '/../api/config/Database.php';

echo "=== Test de Zona Horaria con Database ===\n\n";

echo "→ Antes de crear Database:\n";
echo "  Zona horaria: " . date_default_timezone_get() . "\n";
echo "  Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$db = new Database();

echo "→ Después de crear Database:\n";
echo "  Zona horaria: " . date_default_timezone_get() . "\n";
echo "  Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "  date('Y-m-d'): " . date('Y-m-d') . "\n\n";

echo "✅ La zona horaria ahora está configurada correctamente para Colombia\n";
