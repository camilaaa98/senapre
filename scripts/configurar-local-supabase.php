<?php
/**
 * Script para configurar conexión local a Supabase
 * Trabajar localmente con base de datos en la nube
 */

echo "=== CONFIGURAR LOCAL CON SUPABASE ===\n\n";

echo "🎯 OBJETIVO: Trabajar localmente con Supabase en la nube\n";
echo "💾 Los datos se guardan en Supabase, pero trabajas desde tu PC\n\n";

echo "📋 PASOS PARA CONFIGURAR:\n\n";

echo "🔍 PASO 1: OBTENER CONTRASEÑA DE SUPABASE\n";
echo "1. Ve a: https://blbbixrdmwhjywhlqjwx.supabase.co\n";
echo "2. Settings > Database (⚙️ > Database)\n";
echo "3. Busca 'Connection string'\n";
echo "4. Copia la contraseña después de 'postgres://postgres:'\n\n";

echo "✏️ PASO 2: EDITAR ARCHIVO .env.local\n";
echo "1. Abre el archivo: .env.local\n";
echo "2. Reemplaza [CONTRASEÑA] con la contraseña real\n";
echo "3. Guarda el archivo\n\n";

echo "🧪 PASO 3: PROBAR CONEXIÓN\n";
echo "Ejecuta: php scripts/verificar-conexion-local.php\n\n";

echo "🌐 PASO 4: ACCEDER LOCALMENTE\n";
echo "URL: http://localhost/senapre/index.html\n";
echo "Credenciales: admin@sena.edu.co / admin123\n\n";

echo "=== VENTAJAS DE ESTE MÉTODO ===\n";
echo "✅ Trabajas desde tu PC (familiar y rápido)\n";
echo "✅ Datos guardados en Supabase (nube, seguros)\n";
echo "✅ Sin gastar créditos de Render\n";
echo "✅ Puedes hacer cambios y probar instantáneamente\n";
echo "✅ Cuando esté listo, subes todo a Render\n\n";

echo "=== ¿QUÉ NECESITAS HACER? ===\n";
echo "1. Obtener la contraseña de Supabase\n";
echo "2. Editar el archivo .env.local\n";
echo "3. Probar la conexión\n";
echo "4. Empezar a trabajar localmente\n\n";

echo "=== ARCHIVOS MODIFICADOS ===\n";
echo "✅ api/config/Database.php - Ahora prioriza .env.local\n";
echo "✅ .env.local - Archivo de configuración local\n";
echo "✅ scripts/verificar-conexion-local.php - Script de prueba\n\n";

echo "🚀 ¿Listo para obtener la contraseña de Supabase?\n";
echo "Dime cuando la tengas y te ayudo a configurar todo.\n\n";

echo "💡 Tip: La contraseña usualmente es una cadena larga de caracteres\n";
echo "     que creaste cuando abriste la cuenta de Supabase\n";
?>
