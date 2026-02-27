<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/api/config/Database.php';

function sync() {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->exec("PRAGMA busy_timeout = 60000"); // 60s timeout

    $passHash = password_hash('123456', PASSWORD_DEFAULT);
    $created = 0;
    $updated = 0;
    $errors = [];

    // 1. Collect all distinct documents from leader tables
    $sql = "SELECT DISTINCT doc FROM (
                SELECT vocero_principal as doc FROM fichas WHERE vocero_principal IS NOT NULL
                UNION
                SELECT vocero_suplente as doc FROM fichas WHERE vocero_suplente IS NOT NULL
                UNION
                SELECT documento as doc FROM voceros_enfoque
                UNION
                SELECT documento as doc FROM representantes_jornada
            ) as t WHERE doc IS NOT NULL";
    
    $docs = $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($docs) . " unique documents to sync.\n";

    foreach ($docs as $doc) {
        $doc = trim($doc);
        if (empty($doc)) continue;

        // Get apprentice data
        $stmt = $conn->prepare("SELECT nombre, apellido, correo FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $doc]);
        $ap = $stmt->fetch();

        if (!$ap) {
            $errors[] = "Aprendiz NOT FOUND in 'aprendices' table: $doc";
            continue;
        }

        if (empty($ap['correo'])) {
            // Support: if no email, create one as doc@sena.edu.co to allow login?
            // Actually, better use a placeholder or skip. The user said they don't have credentials.
            $ap['correo'] = $doc . "@senapre.edu.co"; 
        }

        // Check if user exists
        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id");
        $check->execute([':id' => $doc]);
        $user = $check->fetch();

        if (!$user) {
            // Create user
            $stmt = $conn->prepare("INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                                    VALUES (:id, :nom, :ape, :cor, :pass, 'vocero', 'activo')");
            $stmt->execute([
                ':id' => $doc,
                ':nom' => $ap['nombre'],
                ':ape' => $ap['apellido'],
                ':cor' => $ap['correo'],
                ':pass' => $passHash
            ]);
            $created++;
            echo "Created user for $doc: " . $ap['nombre'] . "\n";
        } else {
            // Update role to 'vocero'
            $conn->prepare("UPDATE usuarios SET rol = 'vocero', estado = 'activo' WHERE id_usuario = :id")
                 ->execute([':id' => $doc]);
            $updated++;
        }
    }

    echo "\nSummary:\nCreated: $created\nUpdated: $updated\nErrors: " . count($errors) . "\n";
    if (!empty($errors)) print_r($errors);
}

// Retry loop for locked database
$maxRetries = 5;
$retryCount = 0;
while ($retryCount < $maxRetries) {
    try {
        sync();
        break;
    } catch (Exception $e) {
        $retryCount++;
        echo "Attempt $retryCount failed: " . $e->getMessage() . "\n";
        if (strpos($e->getMessage(), 'locked') !== false) {
            sleep(2);
        } else {
            break;
        }
    }
}
?>
