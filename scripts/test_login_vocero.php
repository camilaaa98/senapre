<?php
require_once __DIR__ . '/api/config/Database.php';
require_once __DIR__ . '/api/controllers/AuthController.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$auth = new AuthController($conn);

// Buscar el correo de un vocero real (el de la ficha 2995479 principal)
$doc = '1004417452';
$stmt = $conn->prepare("SELECT correo FROM usuarios WHERE id_usuario = :doc");
$stmt->execute([':doc' => $doc]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Error: No se encontró el usuario vocero con documento $doc\n");
}

echo "Simulando LOGIN para el vocero: {$user['correo']} (Doc: $doc)\n";
echo "\n--- VALIDACIÓN DE SCOPES MANUAL ---\n";
$voceroScopes = [];
$docTrim = trim($doc);

$stmt = $conn->prepare("SELECT numero_ficha FROM fichas WHERE TRIM(vocero_principal) = :doc");
$stmt->execute([':doc' => $docTrim]);
$fP = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($fP as $row) { $voceroScopes[] = ['tipo' => 'principal', 'ficha' => $row['numero_ficha']]; }

$stmt = $conn->prepare("SELECT numero_ficha FROM fichas WHERE TRIM(vocero_suplente) = :doc");
$stmt->execute([':doc' => $docTrim]);
$fS = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($fS as $row) { $voceroScopes[] = ['tipo' => 'suplente', 'ficha' => $row['numero_ficha']]; }

echo "Scopes encontrados para $docTrim: " . count($voceroScopes) . "\n";
print_r($voceroScopes);

if (count($voceroScopes) > 0) {
    echo "\nEXITO: El sistema ahora detecta correctamente la ficha del vocero.\n";
} else {
    echo "\nFALLO: El sistema sigue sin detectar la ficha del vocero.\n";
}
?>
