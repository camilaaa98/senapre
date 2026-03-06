<?php
require_once __DIR__ . '/api/config/Database.php';
$conn = Database::getInstance()->getConnection();

// Verificar exactamente qué tiene la ficha 2995479
echo "=== FICHA 2995479 - DATOS EXACTOS ===\n";
$s = $conn->query("SELECT numero_ficha, vocero_principal, vocero_suplente, nombre_programa, estado FROM fichas WHERE numero_ficha = '2995479'");
$f = $s->fetch(PDO::FETCH_ASSOC);
if ($f) {
    foreach ($f as $k => $v) echo "  $k: '$v'\n";
}

echo "\n=== ID VOCERO: 1004417452 — BUSQUEDA EN FICHAS ===\n";
$s2 = $conn->prepare("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE vocero_principal = :d OR vocero_suplente = :d");
$s2->execute([':d' => '1004417452']);
$rows = $s2->fetchAll(PDO::FETCH_ASSOC);
echo "Fichas encontradas con ese id: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "  Ficha: {$r['numero_ficha']} | P: '{$r['vocero_principal']}' | S: '{$r['vocero_suplente']}'\n";
}

// Con TRIM
echo "\n=== CON TRIM ===\n";
$s3 = $conn->prepare("SELECT numero_ficha, vocero_principal FROM fichas WHERE TRIM(vocero_principal) = :d OR TRIM(vocero_suplente) = :d");
$s3->execute([':d' => '1004417452']);
$rows = $s3->fetchAll(PDO::FETCH_ASSOC);
echo "Con TRIM: " . count($rows) . " fichas\n";

// Buscar si el documento es diferente al id
echo "\n=== USUARIO 1004417452 CAMPOS ===\n";
$s4 = $conn->query("PRAGMA table_info(usuarios)");
$cols = array_column($s4->fetchAll(PDO::FETCH_ASSOC), 'name');
echo "Columnas: " . implode(', ', $cols) . "\n";
$s5 = $conn->query("SELECT * FROM usuarios WHERE id_usuario = 1004417452 OR id_usuario = '1004417452'");
$row = $s5->fetch(PDO::FETCH_ASSOC);
if ($row) { foreach ($row as $k => $v) { if ($k !== 'password_hash') echo "  $k: '$v'\n"; } }

// ¿Qué valores tiene vocero_principal en la DB para la ficha?
echo "\n=== VALOR EXACTO EN fichas.vocero_principal (ficha 2995479) ===\n";
$s6 = $conn->query("SELECT vocero_principal, LENGTH(vocero_principal) as len, hex(vocero_principal) as hex FROM fichas WHERE numero_ficha = '2995479'");
$r = $s6->fetch(PDO::FETCH_ASSOC);
echo "  valor: '{$r['vocero_principal']}'\n";
echo "  length: {$r['len']}\n";
echo "  hex   : {$r['hex']}\n";
