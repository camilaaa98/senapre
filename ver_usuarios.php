<?php
$database_url = getenv('DATABASE_URL') ?: '';
$parsed = parse_url($database_url);
$pg = new PDO(
    "pgsql:host={$parsed['host']};port=" . ($parsed['port'] ?? 5432) . ";dbname=" . ltrim($parsed['path'], '/') . ";sslmode=require",
    $parsed['user'], $parsed['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo "=== TODOS LOS USUARIOS (para identificar Erik y duplicados) ===\n";
$todos = $pg->query("SELECT id_usuario, nombre, apellido, correo FROM usuarios ORDER BY apellido, nombre")->fetchAll();
foreach ($todos as $u) {
    echo "  ID={$u['id_usuario']} | {$u['nombre']} {$u['apellido']} | {$u['correo']}\n";
}

echo "\n=== CORREOS DUPLICADOS ===\n";
$dup = $pg->query("SELECT correo, COUNT(*) as n FROM usuarios GROUP BY correo HAVING COUNT(*) > 1")->fetchAll();
foreach ($dup as $d) echo "  Duplicado: {$d['correo']} ({$d['n']} veces)\n";

echo "\n=== JANCY - Registros con ese correo ===\n";
$jancy_todos = $pg->query("SELECT id_usuario, nombre, apellido, correo FROM usuarios WHERE correo = 'jebarretom@sena.edu.co'")->fetchAll();
foreach ($jancy_todos as $u) echo "  ID={$u['id_usuario']} | {$u['nombre']} {$u['apellido']}\n";
