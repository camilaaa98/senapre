<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "--- FICHAS CON VOCEROS ---\n";
    $fichas = $db->query("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE vocero_principal IS NOT NULL OR vocero_suplente IS NOT NULL LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    if (!$fichas) echo "No se encontraron fichas con voceros asignados.\n";
    foreach ($fichas as $f) {
        echo "Ficha: {$f['numero_ficha']} | Principal: " . ($f['vocero_principal'] ?: 'NULL') . " | Suplente: " . ($f['vocero_suplente'] ?: 'NULL') . "\n";
        
        if ($f['vocero_principal']) {
            $a = $db->query("SELECT nombre, apellido FROM aprendices WHERE documento = '{$f['vocero_principal']}'")->fetch();
            if ($a) echo "   -> Principal Encontrado: {$a['nombre']} {$a['apellido']}\n";
            else echo "   -> ERROR: Aprendiz principal {$f['vocero_principal']} NO existe en tabla aprendices.\n";
        }
    }

    echo "\n--- ÚLTIMOS 5 APRENDICES ---\n";
    $ap = $db->query("SELECT documento, nombre, apellido, mujer, indigena, narp FROM aprendices LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($ap);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
