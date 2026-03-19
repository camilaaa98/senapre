<?php
/**
 * SENAPRE Emergency Repair Script
 * Purpose: Fix database constraints, clear tables, and recreate critical users.
 */
header('Content-Type: text/plain');
require_once __DIR__ . '/config/Database.php';

try {
    echo "=== Iniciando Reparación Maestro de Base de Datos ===\n";
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $conn->beginTransaction();

    // 1. REPARAR TABLA USUARIOS (Constraints y Tipos)
    echo "Paso 1: Reparando tabla usuarios...\n";
    
    // Eliminar el constraint viejo si existe
    try {
        $conn->exec("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_rol_check");
        echo "- Antiguo constraint eliminado.\n";
    } catch (Exception $e) {}

    // Agregar el nuevo constraint con todos los roles necesarios
    echo "- Aplicando nuevo constraint de roles (incluyendo vocero y admin)...\n";
    $conn->exec("ALTER TABLE usuarios ADD CONSTRAINT usuarios_rol_check 
                 CHECK (rol IN ('director', 'instructor', 'administrativo', 'coordinador', 'vocero', 'admin', 'administrador'))");

    // 2. LIMPIEZA DE DATOS PREVIOS (Evitar conflictos de Unique o Duplicados)
    echo "Paso 2: Limpiando datos previos...\n";
    $conn->exec("DELETE FROM area_responsables");
    $conn->exec("DELETE FROM usuarios");
    $conn->exec("DELETE FROM instructores");
    $conn->exec("DELETE FROM aprendices");
    $conn->exec("DELETE FROM fichas");
    echo "- Tablas críticas limpias.\n";

    // 3. CREACIÓN DE USUARIOS CRÍTICOS (Robustamente)
    echo "Paso 3: Creando usuarios prioritarios...\n";
    
    $usuarios = [
        [
            'id' => 1, 'nom' => 'Administrador', 'ape' => 'SENA', 
            'cor' => 'admin@sena.edu.co', 'pass' => 'Sena2026*Master', 'rol' => 'director', 'area' => 'reportes'
        ],
        [
            'id' => 1056930328, 'nom' => 'Jancy Esperanza', 'ape' => 'Barreto Moreno', 
            'cor' => 'jancy.barreto@sena.edu.co', 'pass' => '1056930328', 'rol' => 'administrativo', 'area' => 'voceros_y_representantes'
        ],
        [
            'id' => 1117506963, 'nom' => 'Erik Jhohana', 'ape' => 'Yáñez Zuleta', 
            'cor' => 'erik.yanez@sena.edu.co', 'pass' => '1117506963', 'rol' => 'administrativo', 'area' => 'fichas'
        ],
        [
            'id' => 999999, 'nom' => 'Vocero', 'ape' => 'Prueba', 
            'cor' => 'vocero@ejemplo.com', 'pass' => '999999', 'rol' => 'vocero', 'area' => null
        ]
    ];

    foreach ($usuarios as $u) {
        $hash = password_hash($u['pass'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'activo')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$u['id'], $u['nom'], $u['ape'], $u['cor'], $hash, $u['rol']]);
        
        if ($u['area']) {
            $conn->prepare("INSERT INTO area_responsables (id_usuario, area) VALUES (?, ?)")
                 ->execute([$u['id'], $u['area']]);
        }
        echo "  - Usuario {$u['nom']} creado ✅\n";
    }

    // 4. ACTUALIZACIÓN DEL CENTRO (CTA)
    try {
        $conn->exec("UPDATE configuracion SET valor = 'Centro de Tecnología Agroindustrial (CTA)' WHERE valor LIKE '%Teleinformática%'");
        echo "Paso 4: Nombre del centro actualizado a CTA.\n";
    } catch(Exception $e) {}

    $conn->commit();
    echo "\n=== REPARACIÓN FINALIZADA CON ÉXITO ===\n";
    echo "Ya puedes ir al index.html e iniciar sesión.";

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
    error_log("Repair Error: " . $e->getMessage());
}
