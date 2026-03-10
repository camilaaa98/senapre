<?php
header('Content-Type: application/json');
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!getenv('DATABASE_URL')) { $conn->exec("PRAGMA busy_timeout = 5000"); // Esperar hasta 5 segundos si está bloqueada }

    $passHash = password_hash('123456', PASSWORD_DEFAULT);
    $count = 0;

    // Obtener todos los documentos de líderes únicos
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

    foreach ($docs as $doc) {
        // Obtener datos del aprendiz
        $stmt = $conn->prepare("SELECT nombre, apellido, correo FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $doc]);
        $ap = $stmt->fetch();

        if (!$ap || empty($ap['correo'])) continue;

        // Verificar si existe
        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = :id OR correo = :cor");
        $check->execute([':id' => $doc, ':cor' => $ap['correo']]);
        $user = $check->fetch();

        if (!$user) {
            $stmt = $conn->prepare("INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                                    VALUES (:id, :nom, :ape, :cor, :pass, 'vocero', 'activo')");
            $stmt->execute([
                ':id' => $doc,
                ':nom' => $ap['nombre'],
                ':ape' => $ap['apellido'],
                ':cor' => $ap['correo'],
                ':pass' => $passHash
            ]);
            $count++;
        } else {
            // Asegurar que tenga el rol vocero
            $conn->prepare("UPDATE usuarios SET rol = 'vocero' WHERE id_usuario = :id OR correo = :cor")
                 ->execute([':id' => $doc, ':cor' => $ap['correo']]);
        }
    }

    echo json_encode(['success' => true, 'created' => $count]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
