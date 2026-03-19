<?php
/**
 * GUÍA PARA OBTENER DATOS DE SUPABASE Y CONFIGURAR EL SISTEMA
 */

echo "=== OBTENER DATOS DE SUPABASE ===\n\n";

echo "🎉 ¡BUENA NOTICIA! Tu cuenta Supabase está activa\n\n";

echo "📋 PASOS PARA OBTENER LA DATABASE_URL:\n\n";

echo "🔍 PASO 1: ENCONTRAR PROJECT URL\n";
echo "1. En el dashboard de Supabase que tienes abierto\n";
echo "2. Busca en el sidebar izquierdo: 'Settings' (icono de engranaje ⚙️)\n";
echo "3. Click en 'Settings'\n\n";

echo "🔑 PASO 2: OBTENER CREDENCIALES\n";
echo "1. Dentro de Settings, busca 'API' en el menú izquierdo\n";
echo "2. Verás dos secciones importantes:\n";
echo "   📍 Project URL: https://[ALGO].supabase.co\n";
echo "   🔐 API Keys (anon public key)\n\n";

echo "📝 PASO 3: COPIAR DATOS\n";
echo "1. Copia la 'Project URL'\n";
echo "2. Copia la 'anon public key'\n";
echo "3. La DATABASE_URL se construye así:\n";
echo "   postgresql://postgres:[PASSWORD]@db.[PROJECT].supabase.co:5432/postgres\n\n";

echo "⚠️  IMPORTANTE SOBRE LA CONTRASEÑA:\n";
echo "La contraseña de la base de datos NO es la API key.\n";
echo "Para obtenerla:\n";
echo "1. Settings > Database\n";
echo "2. Busca 'Connection string'\n";
echo "3. O usa la contraseña que definiste al crear el proyecto\n\n";

echo "🔧 PASO 4: CONFIGURAR EN RENDER\n";
echo "1. Ve a https://dashboard.render.com\n";
echo "2. Busca tu servicio 'senapre'\n";
echo "3. Environment > Add Environment Variable\n";
echo "4. Key: DATABASE_URL\n";
echo "5. Value: [pegar la URL completa]\n";
echo "6. Guardar y esperar redeploy\n\n";

echo "🧪 PASO 5: VERIFICAR CONEXIÓN\n";
echo "Después de configurar, ejecuta:\n";
echo "php scripts/verificar-base-datos-supabase.php\n\n";

echo "=== EJEMPLO DE DATABASE_URL ===\n";
echo "Se ve así (reemplaza con tus datos):\n";
echo "postgresql://postgres:tu_password_aqui@db.tu_proyecto.supabase.co:5432/postgres\n\n";

echo "=== ¿DÓNDE ENCONTRAR CADA COSA? ===\n";
echo "📍 Project URL:\n";
echo "   Settings > API > Project URL\n\n";
echo "🔐 API Key:\n";
echo "   Settings > API > anon public\n\n";
echo "🔑 DB Password:\n";
echo "   Settings > Database > Connection string\n";
echo "   O la que usaste al crear el proyecto\n\n";

echo "=== SI NO ENCUENTRAS LA CONTRASEÑA ===\n";
echo "Opción 1: Revisa el email de confirmación de Supabase\n";
echo "Opción 2: Restablece la contraseña en Settings > Database\n";
echo "Opción 3: Crea una nueva base de datos con contraseña conocida\n\n";

echo "=== UNA VEZ CONFIGURADO ===\n";
echo "✅ Tu sistema SenApre conectará a Supabase\n";
echo "✅ Los datos se guardarán en la nube\n";
echo "✅ Podrás acceder desde https://senapre.onrender.com\n";
echo "✅ Tendrás backup automático\n\n";

echo "🎯 ¿NECESITAS AYUDA PARA ENCONTRAR ALGÚN DATO?\n";
echo "Dime qué ves en tu dashboard y te guío paso a paso\n\n";

echo "🚀 ¡VAMOS A CONFIGURAR ESTO!\n";
?>
