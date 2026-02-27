<?php

/**
 * Add More Sample Data
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Agregando Más Datos de Ejemplo ===\n\n";

    $conn->beginTransaction();

    // 1. AGREGAR MÁS USUARIOS
    echo "→ Agregando más usuarios...\n";
    $nuevosUsuarios = [
        ['id' => 6, 'nombre' => 'Pedro', 'apellido' => 'Sánchez', 'correo' => 'pedro.sanchez@sena.edu.co', 'rol' => 'instructor'],
        ['id' => 7, 'nombre' => 'Laura', 'apellido' => 'Torres', 'correo' => 'laura.torres@sena.edu.co', 'rol' => 'instructor'],
        ['id' => 8, 'nombre' => 'Diego', 'apellido' => 'Ramírez', 'correo' => 'diego.ramirez@sena.edu.co', 'rol' => 'instructor'],
        ['id' => 9, 'nombre' => 'Sofia', 'apellido' => 'Morales', 'correo' => 'sofia.morales@sena.edu.co', 'rol' => 'instructor'],
        ['id' => 10, 'nombre' => 'Administrador', 'apellido' => 'General', 'correo' => 'admin2@asistnet.com', 'rol' => 'administrador']
    ];

    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado, creado_en)
        VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'))
    ");

    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

    foreach ($nuevosUsuarios as $user) {
        $stmtUser->execute([
            $user['id'],
            $user['nombre'],
            $user['apellido'],
            $user['correo'],
            $passwordHash,
            $user['rol']
        ]);
    }
    echo "  ✓ " . count($nuevosUsuarios) . " usuarios agregados\n\n";

    // 2. AGREGAR MÁS INSTRUCTORES
    echo "→ Agregando más instructores...\n";
    $stmtInst = $conn->prepare("
        INSERT INTO instructores (id_instructor, id_usuario, nombres, apellidos, correo, telefono, estado)
        VALUES (?, ?, ?, ?, ?, ?, 'Activo')
    ");

    $instructorCount = 0;
    foreach ($nuevosUsuarios as $user) {
        if ($user['rol'] === 'instructor') {
            $instructorCount++;
            $idInstructor = 4 + $instructorCount; // Continuar desde el 5
            $stmtInst->execute([
                $idInstructor,
                $user['id'],
                $user['nombre'],
                $user['apellido'],
                $user['correo'],
                '300' . rand(1000000, 9999999)
            ]);
        }
    }
    echo "  ✓ $instructorCount instructores agregados\n\n";

    // 3. AGREGAR MÁS FICHAS
    echo "→ Agregando más fichas...\n";

    // Obtener el último id_ficha
    $stmt = $conn->query("SELECT MAX(id_ficha) as max_id FROM fichas");
    $maxId = $stmt->fetch()['max_id'] ?? 0;

    $nuevasFichas = [
        ['numero' => 2500123, 'programa' => 'Desarrollo de Software', 'jornada' => 'Diurna'],
        ['numero' => 2500124, 'programa' => 'Gestión Empresarial', 'jornada' => 'Nocturna'],
        ['numero' => 2500125, 'programa' => 'Contabilidad', 'jornada' => 'Mixta'],
        ['numero' => 2500126, 'programa' => 'Marketing Digital', 'jornada' => 'Diurna'],
        ['numero' => 2500127, 'programa' => 'Recursos Humanos', 'jornada' => 'Nocturna']
    ];

    $stmtFicha = $conn->prepare("
        INSERT INTO fichas (id_ficha, numero_ficha, nombre_programa, jornada, estado)
        VALUES (?, ?, ?, ?, 'Activa')
    ");

    foreach ($nuevasFichas as $index => $ficha) {
        $stmtFicha->execute([
            $maxId + $index + 1,
            $ficha['numero'],
            $ficha['programa'],
            $ficha['jornada']
        ]);
    }
    echo "  ✓ " . count($nuevasFichas) . " fichas agregadas\n\n";

    // 4. AGREGAR MÁS APRENDICES
    echo "→ Agregando más aprendices...\n";

    // Obtener el último id_aprendiz
    $stmt = $conn->query("SELECT MAX(id_aprendiz) as max_id FROM aprendices");
    $maxAprendizId = $stmt->fetch()['max_id'] ?? 0;

    $nuevosAprendices = [
        ['tipo_id' => 'CC', 'doc' => 1234567890, 'nombre' => 'CARLOS ANDRES', 'apellido' => 'LOPEZ GARCIA', 'correo' => 'carlos.lopez@example.com', 'celular' => 3001234567, 'ficha' => 2500123],
        ['tipo_id' => 'CC', 'doc' => 1234567891, 'nombre' => 'MARIA FERNANDA', 'apellido' => 'GOMEZ PEREZ', 'correo' => 'maria.gomez@example.com', 'celular' => 3001234568, 'ficha' => 2500123],
        ['tipo_id' => 'CC', 'doc' => 1234567892, 'nombre' => 'JUAN PABLO', 'apellido' => 'MARTINEZ RUIZ', 'correo' => 'juan.martinez@example.com', 'celular' => 3001234569, 'ficha' => 2500124],
        ['tipo_id' => 'CC', 'doc' => 1234567893, 'nombre' => 'ANA SOFIA', 'apellido' => 'RODRIGUEZ DIAZ', 'correo' => 'ana.rodriguez@example.com', 'celular' => 3001234570, 'ficha' => 2500124],
        ['tipo_id' => 'CC', 'doc' => 1234567894, 'nombre' => 'LUIS FERNANDO', 'apellido' => 'HERNANDEZ CASTRO', 'correo' => 'luis.hernandez@example.com', 'celular' => 3001234571, 'ficha' => 2500125],
        ['tipo_id' => 'CC', 'doc' => 1234567895, 'nombre' => 'LAURA VALENTINA', 'apellido' => 'SANCHEZ MORA', 'correo' => 'laura.sanchez@example.com', 'celular' => 3001234572, 'ficha' => 2500125],
        ['tipo_id' => 'CC', 'doc' => 1234567896, 'nombre' => 'DIEGO ALEJANDRO', 'apellido' => 'RAMIREZ ORTIZ', 'correo' => 'diego.ramirez@example.com', 'celular' => 3001234573, 'ficha' => 2500126],
        ['tipo_id' => 'CC', 'doc' => 1234567897, 'nombre' => 'CAMILA ANDREA', 'apellido' => 'TORRES VARGAS', 'correo' => 'camila.torres@example.com', 'celular' => 3001234574, 'ficha' => 2500126],
        ['tipo_id' => 'CC', 'doc' => 1234567898, 'nombre' => 'SEBASTIAN DAVID', 'apellido' => 'MORALES SILVA', 'correo' => 'sebastian.morales@example.com', 'celular' => 3001234575, 'ficha' => 2500127],
        ['tipo_id' => 'CC', 'doc' => 1234567899, 'nombre' => 'VALENTINA ISABEL', 'apellido' => 'GUTIERREZ ROJAS', 'correo' => 'valentina.gutierrez@example.com', 'celular' => 3001234576, 'ficha' => 2500127]
    ];

    $stmtAprendiz = $conn->prepare("
        INSERT INTO aprendices (id_aprendiz, tipo_identificacion, documento, nombre, apellido, correo, celular, id_ficha, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'EN FORMACION')
    ");

    foreach ($nuevosAprendices as $index => $aprendiz) {
        $stmtAprendiz->execute([
            $maxAprendizId + $index + 1,
            $aprendiz['tipo_id'],
            $aprendiz['doc'],
            $aprendiz['nombre'],
            $aprendiz['apellido'],
            $aprendiz['correo'],
            $aprendiz['celular'],
            $aprendiz['ficha']
        ]);
    }
    echo "  ✓ " . count($nuevosAprendices) . " aprendices agregados\n\n";

    // 5. AGREGAR ASIGNACIONES PARA LAS NUEVAS FICHAS
    echo "→ Agregando asignaciones para nuevas fichas...\n";
    $stmtAsig = $conn->prepare("
        INSERT INTO asignaciones_instructor_ficha (id_instructor, id_ficha, fecha_asignacion)
        VALUES (?, ?, datetime('now'))
    ");

    $asignacionesCount = 0;
    foreach ($nuevasFichas as $index => $ficha) {
        $idFicha = $maxId + $index + 1;
        $idInstructor = ($index % 8) + 1; // Distribuir entre los 8 instructores (4 originales + 4 nuevos)

        $stmtAsig->execute([$idInstructor, $idFicha]);
        $asignacionesCount++;
    }
    echo "  ✓ $asignacionesCount asignaciones agregadas\n\n";

    $conn->commit();

    echo "✅ Datos agregados exitosamente.\n\n";
    echo "=== Resumen Total ===\n";

    // Contar totales
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios");
    echo "Usuarios: " . $stmt->fetch()['total'] . "\n";

    $stmt = $conn->query("SELECT COUNT(*) as total FROM instructores");
    echo "Instructores: " . $stmt->fetch()['total'] . "\n";

    $stmt = $conn->query("SELECT COUNT(*) as total FROM fichas");
    echo "Fichas: " . $stmt->fetch()['total'] . "\n";

    $stmt = $conn->query("SELECT COUNT(*) as total FROM aprendices");
    echo "Aprendices: " . $stmt->fetch()['total'] . "\n";

    $stmt = $conn->query("SELECT COUNT(*) as total FROM asignaciones_instructor_ficha");
    echo "Asignaciones: " . $stmt->fetch()['total'] . "\n";
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
