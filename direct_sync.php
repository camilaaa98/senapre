<?php
$db_file = 'C:/wamp64/www/YanguasEjercicios/senapre/database/Asistnet.db';
try {
    $conn = new PDO("sqlite:" . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("PRAGMA busy_timeout = 60000");
    $conn->exec("PRAGMA journal_mode = WAL");

    $passHash = password_hash('123456', PASSWORD_DEFAULT);

    // Get leader docs
    $docs = $conn->query("SELECT DISTINCT doc FROM (
                SELECT vocero_principal as doc FROM fichas WHERE vocero_principal IS NOT NULL
                UNION
                SELECT vocero_suplente as doc FROM fichas WHERE vocero_suplente IS NOT NULL
                UNION
                SELECT documento as doc FROM voceros_enfoque
                UNION
                SELECT documento as doc FROM representantes_jornada
            ) as t WHERE doc IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

    echo "Found " . count($docs) . " leaders.\n";

    foreach ($docs as $doc) {
        $stmt = $conn->prepare("SELECT nombre, apellido, correo FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $doc]);
        $ap = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ap) continue;

        $correo = !empty($ap['correo']) ? $ap['correo'] : ($doc . "@senapre.edu.co");

        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id");
        $check->execute([':id' => $doc]);
        if (!$check->fetch()) {
            $ins = $conn->prepare("INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                                   VALUES (:id, :nom, :ape, :cor, :pass, 'vocero', 'activo')");
            $ins->execute([
                ':id' => $doc,
                ':nom' => $ap['nombre'],
                ':ape' => $ap['apellido'],
                ':cor' => $correo,
                ':pass' => $passHash
            ]);
            echo "Created: $doc\n";
        } else {
            $conn->prepare("UPDATE usuarios SET rol = 'vocero' WHERE id_usuario = :id")->execute([':id' => $doc]);
        }
    }
    echo "Done.\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage(); }
?>
