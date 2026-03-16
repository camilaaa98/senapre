<?php
/**
 * Script para verificar y mejorar el conteo de población en producción
 */

echo "=== VERIFICACIÓN API PRODUCCIÓN ===\n";
echo "URL: https://senapre.onrender.com/api/liderazgo.php?action=getPoblacionStats\n\n";

// Datos actuales del API
$datosActuales = [
    'mujer' => 7,
    'indigena' => 1,
    'narp' => 0,
    'campesino' => 3,
    'lgbtiq' => 0,
    'discapacidad' => 0
];

echo "📊 CONTEO ACTUAL DEL API:\n";
foreach ($datosActuales as $categoria => $conteo) {
    echo "   " . ucfirst($categoria) . ": " . $conteo . "\n";
}

echo "\n🔍 ANÁLISIS DEL PROBLEMA:\n";
echo "   - Usuario dice: 'hay más mujeres, hay más campesinos, hay más del lgbti'\n";
echo "   - API actual muestra conteos bajos\n";
echo "   - El problema está en la lógica de conteo del API\n\n";

echo "🚨 POSIBLES CAUSAS:\n";
echo "   1. Los patrones LIKE son muy restrictivos\n";
echo "   2. Los datos en tipo_poblacion tienen diferentes formatos\n";
echo "   3. Hay aprendices con múltiples categorías no contados\n";
echo "   4. El API local usa columnas boolean, producción usa tipo_poblacion\n\n";

echo "✅ SOLUCIÓN PROPUESTA:\n";
echo "   1. Mejorar patrones de búsqueda en getPoblacionStats\n";
echo "   2. Usar múltiples variantes para cada categoría\n";
echo "   3. Considerar sinónimos y abreviaciones\n";
echo "   4. Unificar lógica entre local y producción\n\n";

echo "🔧 PATRONES MEJORADOS:\n";
echo "   Mujer: '%mujer%', '%mujeres%', '%femenino%', '%F%', '%femenina%'\n";
echo "   Campesino: '%campesino%', '%campesina%', '%rural%', '%campo%'\n";
echo "   LGBTI: '%lgbti%', '%lgbt%', '%trans%', '%gay%', '%lesbiana%', '%bisexual%'\n";
echo "   Indígena: '%indigena%', '%indígena%', '%etnia%', '%pueblos%'\n";
echo "   Discapacidad: '%discapacidad%', '%discapacitado%', '%discapacitada%', '%capacidad%'\n";
echo "   NARP: '%narp%', '%negro%', '%afro%', '%raizal%', '%palenquero%'\n\n";

echo "📋 ACCIONES REQUERIDAS:\n";
echo "   1. Actualizar getPoblacionStats con patrones mejorados\n";
echo "   2. Probar en producción con datos reales\n";
echo "   3. Agregar logging para depuración\n";
echo "   4. Verificar conteos manualmente\n";
?>
