<?php

/**
 * Debug Foreign Key Issue - Version 2
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Diagnóstico de Foreign Key Constraint ===\n\n";

    // Verificar estructura de usuarios
    echo "→ Estructura de tabla usuarios...\n";
    $stmt = $conn->query("PRAGMA table_info(usuarios)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }

    // Verificar usuarios
    echo "\n→ Verificando tabla usuarios...\n";
    $stmt = $conn->query("SELECT * FROM usuarios LIMIT 5");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Usuarios encontrados: " . count($usuarios) . "\n";
    foreach ($usuarios as $user) {
        echo "  - ID: {$user['id_usuario']}, Nombre: {$user['nombre']}\n";
    }

    // Verificar estructura de instructores
    echo "\n→ Estructura de tabla instructores...\n";
    $stmt = $conn->query("PRAGMA table_info(instructores)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }

    // Verificar instructores
    echo "\n→ Verificando tabla instructores...\n";
    $stmt = $conn->query("SELECT * FROM instructores LIMIT 5");
    $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Instructores encontrados: " . count($instructores) . "\n";
    foreach ($instructores as $inst) {
        $id_usuario = isset($inst['id_usuario']) ? $inst['id_usuario'] : 'N/A';
        echo "  - ID Instructor: {$inst['id_instructor']}, ID Usuario: {$id_usuario}, Nombre: {$inst['nombre']}\n";
    }

    // Verificar estructura de aprendices
    echo "\n→ Estructura de tabla aprendices...\n";
    $stmt = $conn->query("PRAGMA table_info(aprendices)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }

    // Verificar aprendices
    echo "\n→ Verificando tabla aprendices...\n";
    $stmt = $conn->query("SELECT * FROM aprendices LIMIT 5");
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Aprendices encontrados (primeros 5):\n";
    foreach ($aprendices as $apr) {
        echo "  - ID: {$apr['id_aprendiz']}, Nombre: {$apr['nombre']} {$apr['apellido']}, Ficha: {$apr['id_ficha']}\n";
    }

    // Verificar foreign keys
    echo "\n→ Foreign keys en asistencias...\n";
    $stmt = $conn->query("PRAGMA foreign_key_list(asistencias)");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fks as $fk) {
        echo "  - {$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
    }

    // Verificar si FK están habilitadas
    echo "\n→ Estado de foreign keys...\n";
    $stmt = $conn->query("PRAGMA foreign_keys");
    $fk_status = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Foreign keys habilitadas: " . ($fk_status['foreign_keys'] ? 'SÍ' : 'NO') . "\n";

    // Probar INSERT
    echo "\n→ Probando INSERT con datos reales...\n";

    if (count($usuarios) > 0 && count($aprendices) > 0) {
        $test_usuario = $usuarios[0]['id_usuario'];
        $test_aprendiz = $aprendices[0]['id_aprendiz'];
        $test_ficha = $aprendices[0]['id_ficha'];

        echo "Datos de prueba:\n";
        echo "  - id_usuario: {$test_usuario}\n";
        echo "  - id_aprendiz: {$test_aprendiz}\n";
        echo "  - id_ficha: {$test_ficha}\n";

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                INSERT INTO asistencias (id_aprendiz, id_usuario, id_ficha, fecha, hora_entrada, tipo, observaciones)
                VALUES (:id_aprendiz, :id_usuario, :id_ficha, :fecha, :hora_entrada, :tipo, :observaciones)
            ");

            $stmt->execute([
                ':id_aprendiz' => $test_aprendiz,
                ':id_usuario' => $test_usuario,
                ':id_ficha' => $test_ficha,
                ':fecha' => date('Y-m-d'),
                ':hora_entrada' => date('H:i:s'),
                ':tipo' => 'entrada',
                ':observaciones' => 'TEST'
            ]);

            $insertId = $conn->lastInsertId();
            echo "\n✓ INSERT exitoso! ID: $insertId\n";

            $conn->exec("DELETE FROM asistencias WHERE id_asistencia = $insertId");
            echo "✓ Registro de prueba eliminado.\n";

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            echo "\n❌ Error en INSERT: " . $e->getMessage() . "\n";

            // Verificar si el id_usuario existe
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$test_usuario]);
            $exists = $stmt->fetch()['cnt'];
            echo "\n  Verificación id_usuario ({$test_usuario}): " . ($exists ? "EXISTE" : "NO EXISTE") . "\n";

            // Verificar si el id_aprendiz existe
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM aprendices WHERE id_aprendiz = ?");
            $stmt->execute([$test_aprendiz]);
            $exists = $stmt->fetch()['cnt'];
            echo "  Verificación id_aprendiz ({$test_aprendiz}): " . ($exists ? "EXISTE" : "NO EXISTE") . "\n";
        }
    }

    echo "\n✅ Diagnóstico completado.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
