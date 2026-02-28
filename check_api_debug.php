<?php
require_once __DIR__ . '/api/config/Database.php';

header('Content-Type: text/plain');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "--- DETALLES DEL SISTEMA ---\n";
    echo "DB Type: " . $database->getDbPath() . "\n";
    
    // 1. Listar Tablas
    echo "\n--- TABLAS EXISTENTES ---\n";
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);

    // 2. Describir Tablas Críticas
    foreach (['usuarios', 'administrador', 'instructores'] as $table) {
        if (in_array($table, $tables)) {
            echo "\n--- ESTRUCTURA DE '$table' ---\n";
            $stmt = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table'");
            print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            echo "\n¡ADVERTENCIA! La tabla '$table' no existe.\n";
        }
    }

    // 3. Ejecutar consulta de la API manualmente
    echo "\n--- PRUEBA DE CONSULTA API ---\n";
    $rol = 'director,administrativo,coordinador';
    $roles = explode(',', $rol);
    $params = [];
    $placeholders = [];
    foreach ($roles as $i => $r) {
        $key = ":rol$i";
        $placeholders[] = $key;
        $params[$key] = strtolower(trim($r));
    }
    $whereClause = "WHERE LOWER(u.rol) IN (" . implode(',', $placeholders) . ")";
    
    $selectFields = "u.id_usuario, u.rol, 
                     COALESCE(a.nombres, i.nombres, u.nombre) as nombre,
                     COALESCE(a.apellidos, i.apellidos, u.apellido) as apellido,
                     COALESCE(a.correo, i.correo, u.correo) as correo,
                     COALESCE(a.estado, i.estado, u.estado) as estado,
                     COALESCE(a.telefono, i.telefono) as telefono";
    
    $joins = "LEFT JOIN administrador a ON u.id_usuario = a.id_usuario
              LEFT JOIN instructores i ON u.id_usuario = i.id_usuario";

    $sql = "SELECT $selectFields FROM usuarios u $joins $whereClause ORDER BY u.apellido, u.nombre";
    
    echo "SQL a ejecutar: $sql\n";
    echo "Parámetros: " . json_encode($params) . "\n";

    $stmt = $conn->prepare($sql);
    if ($stmt->execute($params)) {
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nÉXITO: Se encontraron " . count($results) . " registros.\n";
        if (count($results) > 0) {
            print_r($results[0]);
        }
    } else {
        echo "\nERROR EN EXECUTE: \n";
        print_r($stmt->errorInfo());
    }

} catch (Exception $e) {
    echo "\nEXCEPCIÓN CRÍTICA: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
