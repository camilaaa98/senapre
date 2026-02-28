<?php
require_once __DIR__ . '/api/config/Database.php';

header('Content-Type: text/plain');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $search = '';
    $rol = 'director,administrativo,coordinador';
    $estado = '';
    $limit = -1;
    
    $where = [];
    $params = [];
    
    if (!empty($rol)) {
        if (strpos($rol, ',') !== false) {
            $roles = explode(',', $rol);
            $placeholders = [];
            foreach ($roles as $i => $r) {
                $key = ":rol$i";
                $placeholders[] = $key;
                $params[$key] = strtolower(trim($r));
            }
            $where[] = "LOWER(u.rol) IN (" . implode(',', $placeholders) . ")";
        } else {
            $where[] = "LOWER(u.rol) = :rol";
            $params[':rol'] = strtolower($rol);
        }
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    echo "--- DIAGNÓSTICO API USUARIOS ---\n";
    echo "Where Clause: $whereClause\n";
    echo "Params: " . print_r($params, true) . "\n";

    // Prueba 1: COUNT
    echo "\nPrueba 1: COUNT\n";
    $countSql = "SELECT COUNT(*) as total FROM usuarios u $whereClause";
    $countStmt = $conn->prepare($countSql);
    if ($countStmt->execute($params)) {
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "Total: $total\n";
    } else {
        echo "Error en COUNT: " . print_r($countStmt->errorInfo(), true) . "\n";
    }

    // Prueba 2: SELECT completo
    echo "\nPrueba 2: SELECT completo\n";
    $selectFields = "u.id_usuario, u.rol, 
                     COALESCE(a.nombres, i.nombres, u.nombre) as nombre,
                     COALESCE(a.apellidos, i.apellidos, u.apellido) as apellido,
                     COALESCE(a.correo, i.correo, u.correo) as correo,
                     COALESCE(a.estado, i.estado, u.estado) as estado,
                     COALESCE(a.telefono, i.telefono) as telefono";
    
    $joins = "LEFT JOIN administrador a ON u.id_usuario = a.id_usuario
              LEFT JOIN instructores i ON u.id_usuario = i.id_usuario";

    $sql = "SELECT $selectFields FROM usuarios u $joins $whereClause ORDER BY u.apellido, u.nombre";
    echo "SQL: $sql\n";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute($params)) {
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Resultados: " . count($res) . "\n";
        if (count($res) > 0) {
            echo "Muestra: " . print_r($res[0], true) . "\n";
        }
    } else {
        echo "Error en SELECT: " . print_r($stmt->errorInfo(), true) . "\n";
    }

} catch (Exception $e) {
    echo "EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
