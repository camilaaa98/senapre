<?php
require_once __DIR__ . '/config/Database.php';
header('Content-Type: text/plain');

try {
    $db = Database::getInstance()->getConnection();
    
    echo "--- PROBANDO CONSULTA SQL ---\n";
    
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
    
    echo "Ejecutando: $sql\n";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Exito! Registros encontrados: " . count($res) . "\n";
    if (count($res) > 0) print_r($res[0]);

} catch (Exception $e) {
    echo "ERROR SQL DETECTADO: " . $e->getMessage() . "\n";
}
?>
