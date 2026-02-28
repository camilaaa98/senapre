<?php
/**
 * Logout System
 * Destroys server session and cookies
 * REPAIR MODE: logout.php?repair=1
 */
if (isset($_GET['repair'])) {
    require_once __DIR__ . '/config/Database.php';
    try {
        $db = Database::getInstance()->getConnection();
        $db->exec("ALTER TABLE administrador ADD COLUMN IF NOT EXISTS telefono TEXT");
        $db->exec("UPDATE administrador a SET nombres = u.nombre, apellidos = u.apellido, correo = u.correo FROM usuarios u WHERE a.id_usuario = u.id_usuario AND (a.nombres IS NULL OR a.nombres = '')");
        echo "REPAIRED_OK";
        exit;
    } catch (Exception $e) {
        echo "ERROR_REPAIR: " . $e->getMessage();
        exit;
    }
}
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Borrar la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Sesión cerrada correctamente']);
?>
