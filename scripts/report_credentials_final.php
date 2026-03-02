<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

function getScopes($doc, $conn) {
    $voceroScopes = [];
    
    // 1. ¿Es vocero principal?
    $stmt = $conn->prepare("SELECT numero_ficha FROM fichas WHERE vocero_principal = :doc");
    $stmt->execute([':doc' => $doc]);
    $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($f as $row) {
        $voceroScopes[] = ['tipo' => 'principal', 'ficha' => $row['numero_ficha']];
    }

    // 2. ¿Es vocero suplente?
    $stmt = $conn->prepare("SELECT numero_ficha FROM fichas WHERE vocero_suplente = :doc");
    $stmt->execute([':doc' => $doc]);
    $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($f as $row) {
        $voceroScopes[] = ['tipo' => 'suplente', 'ficha' => $row['numero_ficha']];
    }

    // 3. ¿Es vocero de enfoque?
    $stmt = $conn->prepare("SELECT tipo_poblacion FROM voceros_enfoque WHERE documento = :doc");
    $stmt->execute([':doc' => $doc]);
    $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($f as $row) {
        $voceroScopes[] = ['tipo' => 'enfoque', 'poblacion' => $row['tipo_poblacion']];
    }

    // 4. ¿Es representante?
    $stmt = $conn->prepare("SELECT jornada FROM representantes_jornada WHERE documento = :doc");
    $stmt->execute([':doc' => $doc]);
    $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($f as $row) {
        $voceroScopes[] = ['tipo' => 'representante', 'jornada' => $row['jornada']];
    }
    
    return $voceroScopes;
}

echo "--- CREDENCIALES SENAPRE ---\n\n";

// CAMILA
$idC = '1004417452';
$stmtC = $conn->prepare('SELECT nombre, apellido, correo, rol FROM usuarios WHERE id_usuario = :id');
$stmtC->execute([':id' => $idC]);
$uC = $stmtC->fetch(PDO::FETCH_ASSOC);
if ($uC) {
    $scopesC = getScopes($idC, $conn);
    echo "USUARIA: CAMILA (1004417452)\n";
    echo "Nombre: " . $uC['nombre'] . " " . $uC['apellido'] . "\n";
    echo "Correo: " . $uC['correo'] . "\n";
    echo "Clave: 123456\n";
    echo "Roles Detectados:\n";
    foreach ($scopesC as $s) {
        if ($s['tipo'] == 'enfoque') echo "- Vocera de Enfoque: " . $s['poblacion'] . "\n";
        else if ($s['tipo'] == 'principal') echo "- Vocera Principal Ficha: " . $s['ficha'] . "\n";
        else echo "- " . ucfirst($s['tipo']) . "\n";
    }
} else {
    echo "Camila (1004417452) no tiene usuario creado aún.\n";
}

echo "\n---------------------------\n\n";

// JANCY
$idJ = '111784566';
$stmtJ = $conn->prepare('SELECT nombre, apellido, correo, rol FROM usuarios WHERE id_usuario = :id');
$stmtJ->execute([':id' => $idJ]);
$uJ = $stmtJ->fetch(PDO::FETCH_ASSOC);
if ($uJ) {
    echo "USUARIA: JANCY (111784566)\n";
    echo "Nombre: " . $uJ['nombre'] . " " . $uJ['apellido'] . "\n";
    echo "Correo: " . $uJ['correo'] . "\n";
    echo "Rol: " . $uJ['rol'] . "\n";
    echo "Clave: 123456\n";
}
?>
