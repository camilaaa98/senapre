<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // 1. Crear usuario en la tabla de auth/usuarios (ID 1 para pruebas)
    $correo = 'admin@local.com';
    $password = password_hash('123456', PASSWORD_DEFAULT);
    $rol = 'director';

    // Limpiar si ya existe
    $conn->prepare("DELETE FROM usuarios WHERE correo = ?")->execute([$correo]);

    $stmt = $conn->prepare("INSERT INTO usuarios (correo, password, rol, estado) VALUES (?, ?, ?, 'ACTIVO') RETURNING id");
    $stmt->execute([$correo, $password, $rol]);
    $userId = $stmt->fetchColumn();

    // 2. Crear datos en la tabla de administrador (si existe en tu esquema)
    // Ajustamos según el esquema estándar del proyecto
    $conn->prepare("DELETE FROM administrador WHERE id_usuario = ?")->execute([$userId]);
    $stmtAdmin = $conn->prepare("INSERT INTO administrador (id_usuario, nombres, apellidos, correo, estado) VALUES (?, 'Admin', 'Local', ?, 'ACTIVO')");
    $stmtAdmin->execute([$userId, $correo]);

    echo "<h1>✅ ¡Usuario de pruebas creado con éxito!</h1>";
    echo "<p><b>Correo:</b> admin@local.com</p>";
    echo "<p><b>Clave:</b> 123456</p>";
    echo "<p><b>Rol:</b> Director de Centro</p>";
    echo "<hr><p>Ya puedes iniciar sesión en <a href='index.html'>index.html</a></p>";

} catch (Exception $e) {
    echo "<h1>❌ Error al crear usuario</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Asegúrate de que la base de datos 'Senapre' exista en PostgreSQL y el usuario 'postgres' tenga permisos.</p>";
}
?>
