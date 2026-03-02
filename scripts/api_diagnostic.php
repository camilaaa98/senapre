<?php
// Simular el entorno de api/aprendices.php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Parámetros que usa el vocero según los logs
    $ficha = '2995479';
    $page = 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    echo "Simulando consulta para ficha: $ficha\n";
    
    $where = [];
    $params = [];
    
    // Filtro de ficha (Lógica actual en api/aprendices.php)
    $fichaBusqueda = trim($ficha);
    $where[] = "(a.numero_ficha = :ficha OR a.numero_ficha LIKE :ficha_like)";
    $params[':ficha'] = $fichaBusqueda;
    $params[':ficha_like'] = "%$fichaBusqueda%";
    
    // Filtro de estado (Lógica actual)
    // No hay estado seleccionado, ficha no está vacía, así que no se añade el NOT IN
    
    $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
    
    // Query principal (Simplificada para test)
    $query = "SELECT a.* FROM aprendices a $whereSql LIMIT $limit OFFSET $offset";
    
    echo "SQL: $query\n";
    echo "Params: "; print_r($params);
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nResultados encontrados: " . count($results) . "\n";
    
    // Query de conteo para paginación
    $queryTotal = "SELECT COUNT(*) as total FROM aprendices a $whereSql";
    $stmtTotal = $conn->prepare($queryTotal);
    $stmtTotal->execute($params);
    echo "Total cuenta: " . $stmtTotal->fetch()['total'] . "\n";

    echo "\nPRUEBA DE ROLES DEL USUARIO:\n";
    $doc = '1004417452';
    $stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id_usuario = :doc");
    $stmt->execute([':doc' => $doc]);
    echo "Rol en BD: '" . $stmt->fetch()['rol'] . "'\n";

} catch (Exception $e) {
    echo "ERROR DETECTADO: " . $e->getMessage() . "\n";
    echo "STACK TRACE:\n" . $e->getTraceAsString() . "\n";
}
?>
