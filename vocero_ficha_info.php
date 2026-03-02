<?php
require_once __DIR__ . '/api/config/Database.php';
$c = Database::getInstance()->getConnection();

echo "=== FICHA 2995479 - RESUMEN ===\n";
$s = $c->prepare("SELECT estado, COUNT(*) as cnt FROM aprendices WHERE TRIM(numero_ficha)='2995479' GROUP BY estado ORDER BY cnt DESC");
$s->execute();
foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  {$r['estado']}: {$r['cnt']}\n";
}

$st = $c->prepare("SELECT COUNT(*) as t FROM aprendices WHERE TRIM(numero_ficha)='2995479'");
$st->execute();
echo "  TOTAL: " . $st->fetch()['total'] . "\n";

echo "\n=== LISTADO COMPLETO ===\n";
printf("%-16s %-20s %-20s %-12s\n", "Documento", "Nombre", "Apellido", "Estado");
echo str_repeat("-", 72) . "\n";
$s2 = $c->prepare("SELECT documento, nombre, apellido, estado FROM aprendices WHERE TRIM(numero_ficha)='2995479' ORDER BY apellido, nombre");
$s2->execute();
foreach ($s2->fetchAll(PDO::FETCH_ASSOC) as $r) {
    printf("%-16s %-20s %-20s %-12s\n", $r['documento'], $r['nombre'], $r['apellido'], $r['estado']);
}

echo "\n=== VOCEROS DEL SISTEMA (91 registrados) ===\n";
echo "Ficha asignada a vocero 1004417452:\n";
$cols = $c->query("PRAGMA table_info(fichas)")->fetchAll(PDO::FETCH_COLUMN, 1);
echo "Columnas de fichas: " . implode(', ', $cols) . "\n";
