<?php
/**
 * MIGRADOR DE DATOS: SQLite (Asistnet.db) → PostgreSQL (Supabase)
 * SENAPRE - Sistema de Asistencias SENA
 *
 * USO: php migrate_data_to_postgres.php
 * REQUIERE: DATABASE_URL=tu_url_de_supabase php migrate_data_to_postgres.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

// ==================== CONFIG ====================
$sqlite_path = __DIR__ . '/database/Asistnet.db';
$database_url = getenv('DATABASE_URL') ?: '';

echo "🚀 MIGRADOR SENAPRE: SQLite → PostgreSQL\n";
echo "=========================================\n\n";

if (empty($database_url)) {
    die("❌ ERROR: Define la variable DATABASE_URL\n   Uso: set DATABASE_URL=postgres://... && php migrate_data_to_postgres.php\n");
}

if (!file_exists($sqlite_path)) {
    die("❌ ERROR: No se encuentra $sqlite_path\n");
}

// ==================== CONEXIONES ====================
echo "📦 Conectando a SQLite...\n";
$sqlite = new PDO("sqlite:$sqlite_path");
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
echo "✅ SQLite conectado\n";

echo "🐘 Conectando a PostgreSQL...\n";
$parsed = parse_url($database_url);
$host  = $parsed['host'];
$port  = $parsed['port'] ?? 5432;
$db    = ltrim($parsed['path'], '/');
$user  = $parsed['user'];
$pass  = $parsed['pass'];

$pg = new PDO(
    "pgsql:host=$host;port=$port;dbname=$db;sslmode=require",
    $user, $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
echo "✅ PostgreSQL conectado\n\n";

// ==================== APLICAR SCHEMA ====================
echo "📋 Aplicando esquema PostgreSQL...\n";
$schema = file_get_contents(__DIR__ . '/database/postgres_schema.sql');
// Dividir por ; para ejecutar sentencia a sentencia
$statements = array_filter(array_map('trim', explode(';', $schema)));
$ok = 0; $err = 0;
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    try {
        $pg->exec($stmt);
        $ok++;
    } catch (PDOException $e) {
        // Ignorar errores de "ya existe"
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "  ⚠️  Error SQL: " . $e->getMessage() . "\n";
            $err++;
        }
    }
}
echo "✅ Schema aplicado ($ok OK, $err errores ignorados)\n\n";

// ==================== FUNCIÓN MIGRAR TABLA ====================
function migrarTabla($sqlite, $pg, $tabla, $blob_cols = []) {
    try {
        $rows = $sqlite->query("SELECT * FROM \"$tabla\"")->fetchAll();
        if (empty($rows)) {
            echo "  ⚪ $tabla: vacía, omitida\n";
            return;
        }

        $count = 0;
        $cols = array_keys($rows[0]);
        $colStr = '"' . implode('","', $cols) . '"';
        $params = ':' . implode(', :', $cols);

        $pg->exec("DELETE FROM \"$tabla\""); // Limpiar primero
        $stmt = $pg->prepare("INSERT INTO \"$tabla\" ($colStr) VALUES ($params)");

        foreach ($rows as $row) {
            foreach ($cols as $col) {
                if (in_array($col, $blob_cols) && $row[$col] !== null) {
                    // Convertir BLOB SQLite a formato BYTEA de PostgreSQL
                    $stmt->bindValue(":$col", $row[$col], PDO::PARAM_LOB);
                } else {
                    $stmt->bindValue(":$col", $row[$col]);
                }
            }
            try {
                $stmt->execute();
                $count++;
            } catch (PDOException $e) {
                // Continuar aunque falle un registro
            }
        }
        echo "  ✅ $tabla: $count/" . count($rows) . " registros migrados\n";
    } catch (Exception $e) {
        echo "  ❌ $tabla: " . $e->getMessage() . "\n";
    }
}

// ==================== MIGRACIÓN EN ORDEN (por FKs) ====================
echo "🔄 Migrando datos...\n";

// 1. Tablas sin dependencias
migrarTabla($sqlite, $pg, 'estados');
migrarTabla($sqlite, $pg, 'programas_formacion');
migrarTabla($sqlite, $pg, 'tipoFormacion');

// 2. Usuarios (base de todo)
migrarTabla($sqlite, $pg, 'usuarios');

// 3. Fichas (depende de programas_formacion)
migrarTabla($sqlite, $pg, 'fichas');

// 4. Aprendices (depende de fichas)
migrarTabla($sqlite, $pg, 'aprendices');

// 5. Instructores (depende de usuarios)
migrarTabla($sqlite, $pg, 'instructores');
migrarTabla($sqlite, $pg, 'administracion');
migrarTabla($sqlite, $pg, 'administrador');

// 6. Asignaciones y horarios
migrarTabla($sqlite, $pg, 'asignacion_instructores');
migrarTabla($sqlite, $pg, 'horarios_formacion');

// 7. Asistencias
migrarTabla($sqlite, $pg, 'asistencias');
migrarTabla($sqlite, $pg, 'excusas_asistencia');

// 8. Biometría (BLOBs) - CRÍTICO
echo "\n⚠️  Migrando biometría (embeddings faciales - puede tardar)...\n";
migrarTabla($sqlite, $pg, 'biometria_aprendices', ['embedding_facial']);
migrarTabla($sqlite, $pg, 'biometria_usuarios',   ['embedding_facial']);

// 9. Bienestar
migrarTabla($sqlite, $pg, 'bienestar_reuniones');
migrarTabla($sqlite, $pg, 'bienestar_asistencia');
migrarTabla($sqlite, $pg, 'bienestar_excusas');

// 10. Población diferencial
migrarTabla($sqlite, $pg, 'Mujer');
migrarTabla($sqlite, $pg, 'campesino');
migrarTabla($sqlite, $pg, 'discapacidad');
migrarTabla($sqlite, $pg, 'lgbtiq');
migrarTabla($sqlite, $pg, 'narp');

// 11. Representantes y voceros
migrarTabla($sqlite, $pg, 'representantes_jornada');
migrarTabla($sqlite, $pg, 'voceros_enfoque');
migrarTabla($sqlite, $pg, 'propuestas_enfoque');
migrarTabla($sqlite, $pg, 'area_responsables');

// 12. Logs
migrarTabla($sqlite, $pg, 'logs');

// ==================== SINCRONIZAR SECUENCIAS ====================
echo "\n🔢 Sincronizando secuencias AUTO INCREMENT...\n";
$sequences = [
    'estados'           => 'id',
    'tipoFormacion'     => 'id',
    'usuarios'          => 'id_usuario',
    'asignacion_instructores' => 'id_asignacion',
    'asistencias'       => 'id_asistencia',
    'excusas_asistencia'=> 'id_excusa',
    'biometria_aprendices' => 'id_biometria',
    'biometria_usuarios'   => 'id_biometria',
    'bienestar_reuniones'  => 'id',
    'bienestar_asistencia' => 'id',
    'bienestar_excusas'    => 'id',
    'propuestas_enfoque'   => 'id',
    'logs'              => 'id_log',
    'horarios_formacion'=> 'id',
    'area_responsables' => 'id',
];
foreach ($sequences as $table => $col) {
    try {
        $pg->exec("SELECT setval(pg_get_serial_sequence('\"$table\"', '$col'), COALESCE((SELECT MAX($col) FROM \"$table\"), 1))");
        echo "  ✅ $table.$col sincronizada\n";
    } catch (Exception $e) {
        // Ignorar si no tiene secuencia
    }
}

echo "\n🎉 MIGRACIÓN COMPLETADA\n";
echo "========================\n";
echo "El sistema SENAPRE está listo en PostgreSQL/Supabase.\n";
echo "Ahora puedes hacer git push y desplegar en Render.\n";
