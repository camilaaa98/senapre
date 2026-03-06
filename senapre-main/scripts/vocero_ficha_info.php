<?php
require_once __DIR__ . '/api/config/Database.php';

$conn = Database::getInstance()->getConnection();

$inactivos = ['CANCELADO','RETIRADO','APLAZADO','TRASLADO','FINALIZADO'];
$placeholders = implode(',', array_fill(0, count($inactivos), '?'));

$stmt = $conn->prepare(
    "SELECT documento, nombre, apellido, correo, celular, estado
     FROM aprendices
     WHERE TRIM(numero_ficha) = '2995479'
       AND UPPER(TRIM(estado)) NOT IN ($placeholders)
     ORDER BY apellido, nombre"
);
$stmt->execute($inactivos);
$aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== APRENDICES ACTIVOS — FICHA 2995479 ===\n";
echo "Total: " . count($aprendices) . " aprendices\n\n";
printf("%-5s %-15s %-20s %-22s %-30s %-12s\n",
    "#", "Documento", "Nombre", "Apellido", "Correo", "Estado");
echo str_repeat("-", 108) . "\n";
foreach ($aprendices as $i => $a) {
    printf("%-5s %-15s %-20s %-22s %-30s %-12s\n",
        $i+1,
        $a['documento'],
        $a['nombre'],
        $a['apellido'],
        $a['correo'] ?: '—',
        $a['estado']
    );
}
