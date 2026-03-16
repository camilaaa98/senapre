<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "--- CHECKING FICHAS AND VOCEROS ---\n";
    $stmt = $conn->query("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE vocero_principal IS NOT NULL AND vocero_principal != ''");
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Fichas with vocero_principal: " . count($fichas) . "\n";
    
    foreach ($fichas as $f) {
        $doc = $f['vocero_principal'];
        $stmtA = $conn->prepare("SELECT documento, nombre, apellido FROM aprendices WHERE documento = ?");
        $stmtA->execute([$doc]);
        $aprendiz = $stmtA->fetch(PDO::FETCH_ASSOC);
        
        if ($aprendiz) {
            echo "Ficha: {$f['numero_ficha']} | Doc: {$doc} | Found: {$aprendiz['nombre']} {$aprendiz['apellido']}\n";
        } else {
            echo "Ficha: {$f['numero_ficha']} | Doc: {$doc} | NOT FOUND in aprendices table\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
