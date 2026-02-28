<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/api/config/Database.php';

function sync() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    // Use a very long timeout
    $conn->exec("PRAGMA busy_timeout = 60000");

    $passHash = password_hash('123456', PASSWORD_DEFAULT);
    $created = 0;
    $updated = 0;
    $errors = [];

    // All leader documents
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
    echo "Processing " . count($docs) . " potential users...\n";

    foreach ($docs as $doc) {
        $doc = trim($doc);
        if (empty($doc)) continue;

        $stmt = $conn->prepare("SELECT nombre, apellido, correo FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $doc]);
        $ap = $stmt->fetch();

        if (!$ap) {
            $errors[] = "Aprendiz $doc not found in database";
            continue;
        }

        $correo = !empty($ap['correo']) ? trim($ap['correo']) : ($doc . "@senapre.edu.co");

        // Verificar si el correo ya existe en otro usuario
        $checkEmail = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = :cor AND id_usuario != :id");
        $checkEmail->execute([':cor' => $correo, ':id' => $doc]);
        if ($checkEmail->fetch()) {
            $correo = $doc . "@senapre.edu.co"; // Usar alternativo si el principal está duplicado
        }

        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id");
        $check->execute([':id' => $doc]);
        $user = $check->fetch();

        if (!$user) {
            $stmt = $conn->prepare("INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                                    VALUES (:id, :nom, :ape, :cor, :pass, 'vocero', 'activo')");
            $stmt->execute([
                ':id' => $doc,
                ':nom' => $ap['nombre'],
                ':ape' => $ap['apellido'],
                ':cor' => $correo,
                ':pass' => $passHash
            ]);
            $created++;
            echo "Created: $doc ($correo)\n";
        } else {
            $conn->prepare("UPDATE usuarios SET rol = 'vocero', estado = 'activo', correo = :cor WHERE id_usuario = :id")
                 ->execute([':cor' => $correo, ':id' => $doc]);
            $updated++;
        }
    }
    return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
}

for ($i=1; $i<=10; $i++) {
    try {
        $res = sync();
        echo "SUCCESS on attempt $i\n";
        print_r($res);
        exit;
    } catch (Exception $e) {
        echo "Attempt $i failed: " . $e->getMessage() . "\n";
        sleep(1);
    }
}
?>
