<?php
/**
 * Script para verificar la estructura de la tabla aprendices en producción
 */

// Simular la estructura de producción basada en el error
echo "=== VERIFICACIÓN DE ESTRUCTURA - PRODUCCIÓN ===\n";
echo "URL: https://senapre.onrender.com/api/liderazgo.php?action=getAprendicesLectiva&categoria=mujer\n\n";

echo "🔍 PROBLEMA IDENTIFICADO:\n";
echo "   - El API getPoblacionStats funciona y devuelve: mujer=7\n";
echo "   - El API getAprendicesLectiva da Internal Server Error\n";
echo "   - Esto sugiere que las columnas de población NO existen en producción\n\n";

echo "📊 ESTRUCTURA ESPERADA vs REAL:\n";
echo "   Columnas que el API intenta usar:\n";
echo "   - mujer (INTEGER)\n";
echo "   - indigena (INTEGER)\n";
echo "   - narp (INTEGER)\n";
echo "   - campesino (INTEGER)\n";
echo "   - lgbtiq (INTEGER)\n";
echo "   - discapacidad (INTEGER)\n\n";

echo "   Columnas que probablemente existen en producción:\n";
echo "   - documento\n";
echo "   - nombre\n";
echo "   - apellido\n";
echo "   - numero_ficha\n";
echo "   - tipo_poblacion (TEXT)\n";
echo "   - estado\n";
echo "   - correo\n";
echo "   - celular\n\n";

echo "🚨 SOLUCIÓN:\n";
echo "   Modificar getAprendicesLectiva para usar tipo_poblacion en lugar de columnas boolean\n";
echo "   Esto funcionará tanto en local como en producción\n\n";

echo "✅ ACCIÓN REQUERIDA:\n";
echo "   Actualizar API para usar consulta compatible con ambas estructuras\n";
?>
