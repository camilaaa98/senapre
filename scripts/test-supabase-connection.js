/**
 * Script para probar conexión con Supabase usando JavaScript
 * Requiere @supabase/supabase-js instalado
 */

const { createClient } = require('@supabase/supabase-js');

console.log('=== PRUEBA DE CONEXIÓN CON SUPABASE ===\n');

// Configuración (necesitas la URL y clave de Supabase)
const supabaseUrl = 'https://[PROJECT_ID].supabase.co';
const supabaseKey = '[SUPABASE_ANON_KEY]';

console.log('📋 CONFIGURACIÓN NECESARIA:');
console.log('1. Supabase URL: https://[PROJECT_ID].supabase.co');
console.log('2. Supabase Key: [SUPABASE_ANON_KEY]');
console.log('3. Ambas se obtienen en Supabase > Settings > API\n');

// Crear cliente de Supabase
const supabase = createClient(supabaseUrl, supabaseKey);

async function testConnection() {
    try {
        console.log('🔍 Probando conexión...\n');
        
        // Intentar conectar a la tabla 'usuarios'
        const { data, error } = await supabase
            .from('usuarios')
            .select('count(*)')
            .limit(1);
        
        if (error) {
            console.log('❌ Error de conexión:', error.message);
            console.log('\n🔧 SOLUCIONES POSIBLES:');
            console.log('1. Verificar que la URL de Supabase sea correcta');
            console.log('2. Verificar que la clave API sea válida');
            console.log('3. Verificar que la tabla "usuarios" exista');
            console.log('4. Verificar permisos de acceso (RLS)');
            return false;
        }
        
        console.log('✅ Conexión exitosa a Supabase!');
        console.log('📊 Datos recibidos:', data);
        
        // Probar consulta de usuarios
        console.log('\n👥 Consultando usuarios...');
        const { data: usuarios, error: errorUsuarios } = await supabase
            .from('usuarios')
            .select('id_usuario, nombre, correo, rol')
            .limit(5);
        
        if (errorUsuarios) {
            console.log('❌ Error consultando usuarios:', errorUsuarios.message);
        } else {
            console.log('✅ Usuarios encontrados:', usuarios.length);
            usuarios.forEach(usuario => {
                console.log(`   👤 ${usuario.nombre} (${usuario.correo}) - ${usuario.rol}`);
            });
        }
        
        return true;
        
    } catch (err) {
        console.log('❌ Error general:', err.message);
        return false;
    }
}

// Función para obtener configuración
function getConfigTemplate() {
    console.log('\n=== PLANTILLA DE CONFIGURACIÓN ===\n');
    
    console.log('// En tu archivo JavaScript/TypeScript:');
    console.log('import { createClient } from \'@supabase/supabase-js\';\n');
    console.log('const supabaseUrl = \'https://[PROJECT_ID].supabase.co\';');
    console.log('const supabaseKey = \'[SUPABASE_ANON_KEY]\';\n');
    console.log('export const supabase = createClient(supabaseUrl, supabaseKey);\n');
    
    console.log('// Para obtener estos valores:');
    console.log('1. Ve a https://supabase.com/dashboard');
    console.log('2. Selecciona tu proyecto');
    console.log('3. Settings > API');
    console.log('4. Copia Project URL y anon public key\n');
}

// Ejecutar prueba
if (supabaseUrl.includes('[PROJECT_ID]') || supabaseKey.includes('[SUPABASE_ANON_KEY]')) {
    console.log('⚠️  CONFIGURACIÓN INCOMPLETA');
    console.log('Por favor, reemplaza los valores de supabaseUrl y supabaseKey\n');
    getConfigTemplate();
} else {
    testConnection().then(success => {
        if (success) {
            console.log('\n🎉 ¡Supabase está funcionando correctamente!');
            console.log('📀 Ya puedes usar @supabase/supabase-js en tu proyecto');
        } else {
            console.log('\n❌ Revisa la configuración y vuelve a intentarlo');
            getConfigTemplate();
        }
    });
}

module.exports = { supabase, testConnection };
