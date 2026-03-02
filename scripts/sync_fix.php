<?php
header('Content-Type: text/plain');
set_time_limit(300); // 5 minutos para evitar 502 en procesos largos
require_once __DIR__ . '/api/config/Database.php';

function sync() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Optimización SQLite if applicable
    if (!getenv('DATABASE_URL')) {
        $conn->exec("PRAGMA busy_timeout = 60000");
    }

    $created = 0;
    $updated = 0;
    $errors = [];

    // Query optimizada para obtener todos los documentos de líderes en una sola pasada
    $sql = "SELECT DISTINCT doc FROM (
                SELECT vocero_principal as doc FROM fichas WHERE vocero_principal IS NOT NULL
                UNION
                SELECT vocero_suplente as doc FROM fichas WHERE vocero_suplente IS NOT NULL
                UNION
                SELECT documento as doc FROM voceros_enfoque
                UNION
                SELECT documento as doc FROM representantes_jornada
            ) as t WHERE doc IS NOT NULL AND doc != ''";
    
    $docs = $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "Processing " . count($docs) . " potential users...\n";

    // Caché de aprendices para evitar múltiples consultas si hay muchos líderes
    $aprendicesRaw = $conn->query("SELECT documento, nombre, apellido, correo FROM aprendices")->fetchAll(PDO::FETCH_ASSOC);
    $aprendicesMap = [];
    foreach ($aprendicesRaw as $a) {
        $aprendicesMap[$a['documento']] = $a;
    }

    $shouldUpdatePass = isset($_GET['force_reset']) && $_GET['force_reset'] == '1';

    foreach ($docs as $doc) {
        $doc = trim($doc);
        if (empty($doc)) continue;

        $ap = $aprendicesMap[$doc] ?? null;

        if (!$ap) {
            $errors[] = "Aprendiz $doc not found in database";
            continue;
        }

        $correo = !empty($ap['correo']) ? trim($ap['correo']) : ($doc . "@senapre.edu.co");

        $check = $conn->prepare("SELECT id_usuario, password_hash FROM usuarios WHERE id_usuario = :id");
        $check->execute([':id' => $doc]);
        $user = $check->fetch();

        if (!$user) {
            // Usuario NUEVO: Hashear contraseña es obligatorio
            $passHash = password_hash($doc, PASSWORD_DEFAULT);
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
            // Usuario EXISTENTE: Solo hashear si se fuerza o si está vacía (Ahorra mucho CPU)
            if (empty($user['password_hash']) || $shouldUpdatePass) {
                $passHash = password_hash($doc, PASSWORD_DEFAULT);
                $conn->prepare("UPDATE usuarios SET rol = 'vocero', estado = 'activo', correo = :cor, password_hash = :pass WHERE id_usuario = :id")
                     ->execute([
                         ':cor' => $correo, 
                         ':pass' => $passHash,
                         ':id' => $doc
                     ]);
                echo "Updated (reset pass): $doc\n";
            } else {
                $conn->prepare("UPDATE usuarios SET rol = 'vocero', estado = 'activo', correo = :cor WHERE id_usuario = :id")
                     ->execute([
                         ':cor' => $correo,
                         ':id' => $doc
                     ]);
                echo "Updated (role/email): $doc\n";
            }
            $updated++;
        }
    }
    return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
}

try {
    $res = sync();
    echo "SUCCESS\n";
    print_r($res);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
?>
