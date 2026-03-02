<?php
/**
 * MIGRADOR SENAPRE v4 - DEFINITIVO
 * Correcciones:
 * - aprendices incluye columna tipo_poblacion
 * - Primero inserta programas faltantes antes de fichas 
 * - Sin FKs rígidas que bloqueen la migración
 * - Muestra errores detallados
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

$sqlite_path = __DIR__ . '/database/Asistnet.db';
$database_url = getenv('DATABASE_URL') ?: '';

echo "🚀 MIGRADOR SENAPRE v4 (DEFINITIVO)\n";
echo "=====================================\n\n";

if (empty($database_url)) die("❌ Define DATABASE_URL\n");

$sqlite = new PDO("sqlite:$sqlite_path");
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$parsed = parse_url($database_url);
$pg = new PDO(
    "pgsql:host={$parsed['host']};port=" . ($parsed['port'] ?? 5432) . ";dbname=" . ltrim($parsed['path'], '/') . ";sslmode=require",
    $parsed['user'], $parsed['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
echo "✅ Conexiones OK\n\n";

// LIMPIAR tablas anteriores
echo "🧹 Limpiando tablas previas...\n";
foreach (['fichas','aprendices','horarios_formacion','asistencias','asistencias_backup',
    'asignacion_instructores','excusas_asistencia','biometria_aprendices','biometria_usuarios',
    'bienestar_reuniones','bienestar_asistencia','bienestar_excusas',
    'mujer','campesino','discapacidad','indigena','lgbtiq','narp',
    'representantes_jornada','voceros_enfoque','propuestas_enfoque',
    'area_responsables','logs','administrador','administracion',
    'instructores'] as $t) {
    try { $pg->exec("DROP TABLE IF EXISTS $t CASCADE"); }
    catch (Exception $e) {}
}
echo "  ✅ Limpieza completa\n\n";

// SCHEMA SIN FKs (para migración limpia)
echo "📋 Creando schema...\n";
$stmts = [
    "CREATE TABLE IF NOT EXISTS estados (id SERIAL PRIMARY KEY, nombre TEXT NOT NULL UNIQUE, color TEXT NOT NULL)",
    "CREATE TABLE IF NOT EXISTS programas_formacion (nombre_programa TEXT PRIMARY KEY, nivel_formacion TEXT)",
    "CREATE TABLE IF NOT EXISTS tipoformacion (id SERIAL PRIMARY KEY, nombre VARCHAR(50) NOT NULL UNIQUE)",
    "CREATE TABLE IF NOT EXISTS usuarios (id_usuario SERIAL PRIMARY KEY, nombre TEXT, apellido TEXT, correo TEXT UNIQUE, password_hash TEXT, rol TEXT, estado TEXT DEFAULT 'activo', creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    // fichas sin FK restrictiva
    "CREATE TABLE IF NOT EXISTS fichas (numero_ficha TEXT PRIMARY KEY, nombre_programa TEXT, jornada TEXT, estado TEXT DEFAULT 'ACTIVO', instructor_lider TEXT, vocero_principal TEXT, vocero_suplente TEXT, tipoformacion VARCHAR(50))",
    // aprendices incluye tipo_poblacion
    "CREATE TABLE IF NOT EXISTS aprendices (documento VARCHAR(20) PRIMARY KEY, tipo_identificacion TEXT, nombre TEXT NOT NULL, apellido TEXT NOT NULL, correo TEXT, celular TEXT, numero_ficha TEXT, estado TEXT DEFAULT 'EN FORMACION', tipo_poblacion TEXT)",
    "CREATE TABLE IF NOT EXISTS instructores (id_usuario INTEGER PRIMARY KEY, nombres TEXT NOT NULL, apellidos TEXT NOT NULL, correo TEXT NOT NULL, telefono NUMERIC, estado TEXT DEFAULT 'activo')",
    "CREATE TABLE IF NOT EXISTS administracion (id_usuario INTEGER PRIMARY KEY, cargo TEXT, estado TEXT DEFAULT 'activo')",
    "CREATE TABLE IF NOT EXISTS administrador (id_usuario INTEGER PRIMARY KEY, nombres TEXT, apellidos TEXT, correo TEXT, password_hash TEXT, rol TEXT, estado TEXT DEFAULT 'activo')",
    "CREATE TABLE IF NOT EXISTS asignacion_instructores (id_asignacion SERIAL PRIMARY KEY, id_usuario INTEGER, numero_ficha TEXT, fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS horarios_formacion (id SERIAL PRIMARY KEY, numero_ficha TEXT, id_instructor INTEGER, dia_semana INTEGER, hora_inicio TIME, hora_fin TIME, jornada TEXT)",
    "CREATE TABLE IF NOT EXISTS asistencias (id_asistencia SERIAL PRIMARY KEY, documento_aprendiz TEXT NOT NULL, numero_ficha TEXT NOT NULL, fecha DATE NOT NULL, estado TEXT NOT NULL DEFAULT 'Presente', observaciones TEXT, id_instructor INTEGER, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS asistencias_backup (id_asistencia INTEGER, documento_aprendiz INTEGER, id_usuario INTEGER, fecha TEXT, hora_entrada TEXT, hora_salida TEXT, tipo TEXT DEFAULT 'presente', observaciones TEXT, archivo_soporte TEXT, origen_registro TEXT DEFAULT 'Manual', confianza_biometrica INTEGER DEFAULT 0, hora TIME)",
    "CREATE TABLE IF NOT EXISTS excusas_asistencia (id_excusa SERIAL PRIMARY KEY, documento TEXT NOT NULL, numero_ficha TEXT NOT NULL, fecha_falta DATE NOT NULL, fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP, motivo TEXT NOT NULL, archivo_adjunto TEXT, estado TEXT DEFAULT 'PENDIENTE', evaluado_por TEXT, fecha_evaluacion TIMESTAMP, observaciones_admin TEXT, tipo_excusa TEXT DEFAULT 'INASISTENCIA')",
    "CREATE TABLE IF NOT EXISTS biometria_aprendices (id_biometria SERIAL PRIMARY KEY, documento TEXT NOT NULL UNIQUE, embedding_facial BYTEA NOT NULL, fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS biometria_usuarios (id_biometria SERIAL PRIMARY KEY, id_usuario TEXT NOT NULL UNIQUE, embedding_facial BYTEA NOT NULL, fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS bienestar_reuniones (id SERIAL PRIMARY KEY, titulo TEXT NOT NULL, descripcion TEXT, fecha TIMESTAMP NOT NULL, lugar TEXT, tipo_convocatoria TEXT, creado_por INTEGER, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS bienestar_asistencia (id SERIAL PRIMARY KEY, id_reunion INTEGER NOT NULL, id_aprendiz TEXT NOT NULL, estado TEXT DEFAULT 'ausente', fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS bienestar_excusas (id SERIAL PRIMARY KEY, id_asistencia INTEGER NOT NULL, archivo_adjunto TEXT, motivo TEXT, estado_excusa TEXT DEFAULT 'pendiente', fecha_presentacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS mujer (documento VARCHAR(20) PRIMARY KEY)",
    "CREATE TABLE IF NOT EXISTS campesino (documento VARCHAR(20) PRIMARY KEY)",
    "CREATE TABLE IF NOT EXISTS discapacidad (documento VARCHAR(20) PRIMARY KEY)",
    "CREATE TABLE IF NOT EXISTS indigena (documento VARCHAR(20) PRIMARY KEY)",
    "CREATE TABLE IF NOT EXISTS lgbtiq (documento VARCHAR(20) PRIMARY KEY)",
    "CREATE TABLE IF NOT EXISTS narp (documento VARCHAR(20) PRIMARY KEY)",
    "CREATE TABLE IF NOT EXISTS representantes_jornada (jornada VARCHAR(50) PRIMARY KEY, documento VARCHAR(20))",
    "CREATE TABLE IF NOT EXISTS voceros_enfoque (tipo_poblacion VARCHAR(50) PRIMARY KEY, documento VARCHAR(20))",
    "CREATE TABLE IF NOT EXISTS propuestas_enfoque (id SERIAL PRIMARY KEY, tipo_poblacion VARCHAR(50), propuesta TEXT, fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS area_responsables (id SERIAL PRIMARY KEY, id_usuario INTEGER, area TEXT)",
    "CREATE TABLE IF NOT EXISTS logs (id_log SERIAL PRIMARY KEY, id_usuario INTEGER, accion TEXT, fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
    // Índices
    "CREATE INDEX IF NOT EXISTS idx_bio_apr ON biometria_aprendices(documento)",
    "CREATE INDEX IF NOT EXISTS idx_bio_usr ON biometria_usuarios(id_usuario)",
    "CREATE INDEX IF NOT EXISTS idx_exc_doc ON excusas_asistencia(documento)",
    "CREATE INDEX IF NOT EXISTS idx_asi_fic ON asistencias(numero_ficha, fecha)",
    "CREATE INDEX IF NOT EXISTS idx_apr_fic ON aprendices(numero_ficha)",
];
$ok = 0;
foreach ($stmts as $s) {
    try { $pg->exec($s); $ok++; }
    catch(PDOException $e) {
        if (strpos($e->getMessage(),'already exists') === false)
            echo "  ⚠️  " . substr($e->getMessage(),0,120) . "\n";
    }
}
echo "✅ Schema OK ($ok sentencias)\n\n";

// FUNCIÓN DE MIGRACIÓN
function mig($sqlite, $pg, $tbl_src, $tbl_dst, $blob_cols = [], $col_map = []) {
    try {
        $rows = $sqlite->query("SELECT * FROM \"$tbl_src\"")->fetchAll();
        if (empty($rows)) { echo "  ⚪ $tbl_dst: vacía\n"; return; }

        try { $pg->exec("DELETE FROM $tbl_dst"); } catch(Exception $e) {}

        $first  = $rows[0];
        $src_cols = array_keys($first);
        // Mapear nombre de columna fuente → destino PG
        $dst_cols = array_map(fn($c) => $col_map[$c] ?? strtolower($c), $src_cols);

        $sql = "INSERT INTO $tbl_dst (" . implode(', ', $dst_cols) . ") VALUES (:" . implode(', :', $src_cols) . ")";
        $stmt = $pg->prepare($sql);
        $count = 0; $errs = 0;

        foreach ($rows as $row) {
            foreach ($src_cols as $col) {
                if (in_array($col, $blob_cols) && $row[$col] !== null)
                    $stmt->bindValue(":$col", $row[$col], PDO::PARAM_LOB);
                else
                    $stmt->bindValue(":$col", $row[$col] !== '' ? $row[$col] : null);
            }
            try { $stmt->execute(); $count++; }
            catch(PDOException $e) {
                $errs++;
                if ($errs <= 2) echo "    ↪ " . substr($e->getMessage(),0,130) . "\n";
            }
        }
        $icon = $errs === 0 ? '✅' : '⚠️ ';
        echo "  $icon $tbl_dst: $count/" . count($rows) . " registros" . ($errs > 0 ? " ($errs errores)" : '') . "\n";
    } catch(Exception $e) { echo "  ❌ $tbl_dst: " . $e->getMessage() . "\n"; }
}

echo "🔄 Migrando datos...\n";
mig($sqlite, $pg, 'estados', 'estados');
mig($sqlite, $pg, 'programas_formacion', 'programas_formacion');

// Insertar programas faltantes desde fichas
echo "  📥 Agregando programas faltantes de fichas...\n";
$progs_faltantes = $sqlite->query("
    SELECT DISTINCT nombre_programa FROM fichas 
    WHERE nombre_programa NOT IN (SELECT nombre_programa FROM programas_formacion)
")->fetchAll(PDO::FETCH_COLUMN);
$ins_prog = $pg->prepare("INSERT INTO programas_formacion (nombre_programa, nivel_formacion) VALUES (?, 'Formación Complementaria') ON CONFLICT DO NOTHING");
foreach ($progs_faltantes as $prog) {
    try { $ins_prog->execute([$prog]); echo "    + $prog\n"; }
    catch(Exception $e) {}
}

mig($sqlite, $pg, 'tipoFormacion', 'tipoformacion', [], ['tipoFormacion' => 'tipoformacion']);
mig($sqlite, $pg, 'usuarios', 'usuarios');
mig($sqlite, $pg, 'fichas', 'fichas', [], ['tipoFormacion' => 'tipoformacion']);
mig($sqlite, $pg, 'aprendices', 'aprendices'); // ahora incluye tipo_poblacion
mig($sqlite, $pg, 'instructores', 'instructores');
mig($sqlite, $pg, 'administracion', 'administracion');
mig($sqlite, $pg, 'administrador', 'administrador');
mig($sqlite, $pg, 'asignacion_instructores', 'asignacion_instructores');
mig($sqlite, $pg, 'horarios_formacion', 'horarios_formacion');
mig($sqlite, $pg, 'asistencias', 'asistencias');
mig($sqlite, $pg, 'excusas_asistencia', 'excusas_asistencia');

echo "\n⏳ Biometría facial (puede tardar 2-5 min para 154 embeddings)...\n";
mig($sqlite, $pg, 'biometria_aprendices', 'biometria_aprendices', ['embedding_facial']);
mig($sqlite, $pg, 'biometria_usuarios', 'biometria_usuarios', ['embedding_facial']);

mig($sqlite, $pg, 'bienestar_reuniones', 'bienestar_reuniones');
mig($sqlite, $pg, 'bienestar_asistencia', 'bienestar_asistencia');
mig($sqlite, $pg, 'bienestar_excusas', 'bienestar_excusas');
mig($sqlite, $pg, 'Mujer', 'mujer');
mig($sqlite, $pg, 'campesino', 'campesino');
mig($sqlite, $pg, 'discapacidad', 'discapacidad');
mig($sqlite, $pg, 'lgbtiq', 'lgbtiq');
mig($sqlite, $pg, 'narp', 'narp');
mig($sqlite, $pg, 'representantes_jornada', 'representantes_jornada');
mig($sqlite, $pg, 'voceros_enfoque', 'voceros_enfoque');
mig($sqlite, $pg, 'propuestas_enfoque', 'propuestas_enfoque');
mig($sqlite, $pg, 'logs', 'logs');

// Sync secuencias
echo "\n🔢 Sincronizando secuencias...\n";
foreach (['estados'=>'id','tipoformacion'=>'id','usuarios'=>'id_usuario',
    'asistencias'=>'id_asistencia','excusas_asistencia'=>'id_excusa',
    'biometria_aprendices'=>'id_biometria','biometria_usuarios'=>'id_biometria',
    'bienestar_reuniones'=>'id','bienestar_asistencia'=>'id','bienestar_excusas'=>'id',
    'propuestas_enfoque'=>'id','logs'=>'id_log','horarios_formacion'=>'id',
    'asignacion_instructores'=>'id_asignacion'] as $t => $c) {
    try {
        $pg->exec("SELECT setval(pg_get_serial_sequence('$t','$c'), COALESCE((SELECT MAX($c) FROM $t),1))");
        echo "  ✅ $t.$c\n";
    } catch(Exception $e) {}
}

echo "\n🎉 MIGRACIÓN v4 COMPLETADA!\n";
echo "===========================\n";
echo "Aprendices, fichas, biometría y asistencias migradas.\n";
echo "Ahora ya puedes desplegar SENAPRE en Render!\n";
