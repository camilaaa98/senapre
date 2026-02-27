<?php

/**
 * Populate All Related Tables
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "=== Poblando Tablas Relacionadas ===\n\n";

    $conn->beginTransaction();

    // 1. CREAR PROGRAMAS DE FORMACIÓN
    echo "→ Creando programas de formación...\n";
    $programas = [
        ['id' => 1, 'nombre' => 'Análisis y Desarrollo de Sistemas de Información', 'nivel' => 'Tecnólogo'],
        ['id' => 2, 'nombre' => 'Contabilidad y Finanzas', 'nivel' => 'Tecnólogo'],
        ['id' => 3, 'nombre' => 'Gestión Administrativa', 'nivel' => 'Tecnólogo'],
        ['id' => 4, 'nombre' => 'Gestión de Mercados', 'nivel' => 'Tecnólogo'],
        ['id' => 5, 'nombre' => 'Gestión Logística', 'nivel' => 'Tecnólogo']
    ];

    $stmtProg = $conn->prepare("INSERT INTO programas_formacion (id_programa, nombre_programa, nivel_formacion) VALUES (?, ?, ?)");
    foreach ($programas as $prog) {
        $stmtProg->execute([$prog['id'], $prog['nombre'], $prog['nivel']]);
    }
    echo "  ✓ " . count($programas) . " programas creados\n\n";

    // 2. CREAR FICHAS
    echo "→ Creando fichas...\n";
    $stmt = $conn->query("SELECT DISTINCT id_ficha FROM aprendices ORDER BY id_ficha");
    $fichasNumeros = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmtFicha = $conn->prepare("
        INSERT INTO fichas (id_ficha, numero_ficha, nombre_programa, jornada, estado)
        VALUES (?, ?, ?, ?, ?)
    ");

    $programasCiclo = [1, 2, 3, 4, 5];
    $jornadas = ['Diurna', 'Nocturna', 'Mixta'];

    foreach ($fichasNumeros as $index => $numeroFicha) {
        $idPrograma = $programasCiclo[$index % count($programasCiclo)];
        $programa = $programas[$idPrograma - 1];
        $jornada = $jornadas[$index % count($jornadas)];

        $stmtFicha->execute([
            $index + 1,
            $numeroFicha,
            $programa['nombre'],
            $jornada,
            'Activa'
        ]);
    }
    echo "  ✓ " . count($fichasNumeros) . " fichas creadas\n\n";

    // 3. CREAR USUARIOS E INSTRUCTORES
    echo "→ Creando usuarios e instructores...\n";
    $usuarios = [
        ['id' => 1, 'nombre' => 'Admin', 'apellido' => 'Sistema', 'correo' => 'admin@asistnet.com', 'rol' => 'administrador'],
        ['id' => 2, 'nombre' => 'Juan', 'apellido' => 'Pérez', 'correo' => 'juan.perez@sena.edu.co', 'rol' => 'instructor'],
        ['id' => 3, 'nombre' => 'María', 'apellido' => 'González', 'correo' => 'maria.gonzalez@sena.edu.co', 'rol' => 'instructor'],
        ['id' => 4, 'nombre' => 'Carlos', 'apellido' => 'Rodríguez', 'correo' => 'carlos.rodriguez@sena.edu.co', 'rol' => 'instructor'],
        ['id' => 5, 'nombre' => 'Ana', 'apellido' => 'Martínez', 'correo' => 'ana.martinez@sena.edu.co', 'rol' => 'instructor']
    ];

    $stmtUser = $conn->prepare("
        INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado, creado_en)
        VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'))
    ");

    $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

    foreach ($usuarios as $user) {
        $stmtUser->execute([
            $user['id'],
            $user['nombre'],
            $user['apellido'],
            $user['correo'],
            $passwordHash,
            $user['rol']
        ]);
    }
    echo "  ✓ " . count($usuarios) . " usuarios creados\n";

    $stmtInst = $conn->prepare("
        INSERT INTO instructores (id_instructor, id_usuario, nombres, apellidos, correo, telefono, estado)
        VALUES (?, ?, ?, ?, ?, ?, 'Activo')
    ");

    $instructorCount = 0;
    foreach ($usuarios as $user) {
        if ($user['rol'] === 'instructor') {
            $instructorCount++;
            $stmtInst->execute([
                $instructorCount,
                $user['id'],
                $user['nombre'],
                $user['apellido'],
                $user['correo'],
                '300' . rand(1000000, 9999999)
            ]);
        }
    }
    echo "  ✓ $instructorCount instructores creados\n\n";

    // 4. CREAR ASIGNACIONES
    echo "→ Creando asignaciones instructor-ficha...\n";
    $stmtAsig = $conn->prepare("
        INSERT INTO asignaciones_instructor_ficha (id_instructor, id_ficha, fecha_asignacion)
        VALUES (?, ?, datetime('now'))
    ");

    $asignaciones = 0;
    foreach ($fichasNumeros as $index => $numeroFicha) {
        $idFicha = $index + 1;
        $idInstructor = ($index % $instructorCount) + 1;

        $stmtAsig->execute([$idInstructor, $idFicha]);
        $asignaciones++;
    }
    echo "  ✓ $asignaciones asignaciones creadas\n\n";

    $conn->commit();

    echo "✅ Todas las tablas pobladas exitosamente.\n\n";
    echo "=== Resumen ===\n";
    echo "Programas: " . count($programas) . "\n";
    echo "Fichas: " . count($fichasNumeros) . "\n";
    echo "Usuarios: " . count($usuarios) . "\n";
    echo "Instructores: $instructorCount\n";
    echo "Asignaciones: $asignaciones\n";
    echo "Aprendices: 1886 (ya importados)\n\n";
    echo "Credenciales de acceso:\n";
    echo "  - Admin: admin@asistnet.com / 123456\n";
    echo "  - Instructores: [correo] / 123456\n";
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
