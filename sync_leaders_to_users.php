<?php
header('Content-Type: application/json');
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $count = 0;
    $errors = [];

    // Función para procesar un documento como usuario
    $processUser = function($doc, $subrole) use ($conn, &$count, &$errors) {
        if (!$doc) return;

        // Obtener datos del aprendiz
        $stmt = $conn->prepare("SELECT * FROM aprendices WHERE documento = :doc");
        $stmt->execute([':doc' => $doc]);
        $aprendiz = $stmt->fetch();

        if (!$aprendiz) {
            $errors[] = "Aprendiz no encontrado: $doc";
            return;
        }

        if (empty($aprendiz['correo'])) {
            $errors[] = "Aprendiz sin correo: $doc (" . $aprendiz['nombre'] . ")";
            return;
        }

        // Verificar si ya existe en usuarios
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = :correo OR id_usuario = :id");
        $stmt->execute([':correo' => $aprendiz['correo'], ':id' => $doc]);
        $user = $stmt->fetch();

        $passHash = password_hash('123456', PASSWORD_DEFAULT);

        if (!$user) {
            // Crear usuario
            $sql = "INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                    VALUES (:id, :nom, :ape, :cor, :pass, 'vocero', 'activo')";
            $conn->prepare($sql)->execute([
                ':id' => $doc,
                ':nom' => $aprendiz['nombre'],
                ':ape' => $aprendiz['apellido'],
                ':cor' => $aprendiz['correo'],
                ':pass' => $passHash
            ]);
            $count++;
        } else {
            // Actualizar rol a vocero si es necesario
            $conn->prepare("UPDATE usuarios SET rol = 'vocero' WHERE id_usuario = :id")
                 ->execute([':id' => $doc]);
        }
    };

    // 1. Voceros Principales y Suplentes
    $fichas = $conn->query("SELECT vocero_principal, vocero_suplente FROM fichas")->fetchAll();
    foreach ($fichas as $f) {
        $processUser($f['vocero_principal'], 'principal');
        $processUser($f['vocero_suplente'], 'suplente');
    }

    // 2. Voceros de Enfoque
    $enfoque = $conn->query("SELECT documento FROM voceros_enfoque")->fetchAll();
    foreach ($enfoque as $e) {
        $processUser($e['documento'], 'enfoque');
    }

    // 3. Representantes
    $reps = $conn->query("SELECT documento FROM representantes_jornada")->fetchAll();
    foreach ($reps as $r) {
        $processUser($r['documento'], 'representante');
    }

    echo json_encode([
        'success' => true,
        'message' => "Proceso completado. $count nuevos usuarios creados.",
        'errors' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
