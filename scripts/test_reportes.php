<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/api/config/Database.php';

// Probar api/reportes.php
ob_start();
include __DIR__ . '/api/reportes.php';
$out = ob_get_clean();
$d = json_decode($out, true);

if ($d === null) {
    echo "ERROR JSON:\n" . substr($out, 0, 800) . "\n";
} else {
    echo "success: " . ($d['success'] ? 'SI' : 'NO') . "\n";
    echo "resumen: "; print_r($d['data']['resumen'] ?? 'SIN RESUMEN');
}
