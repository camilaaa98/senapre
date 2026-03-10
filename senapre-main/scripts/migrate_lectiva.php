<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== Inicio de Migración Masiva a LECTIVA ===\n";

    // 1. Migrar Fichas: Activa -> LECTIVA
    // Asegurar que LECTIVA no de error de longitud ni nada (TEXT suele ser seguro)
    $sqlFichas = "UPDATE fichas SET estado = 'LECTIVA' WHERE estado = 'Activa' OR estado = 'EN FORMACION'";
    $stmtF = $conn->prepare($sqlFichas);
    $stmtF->execute();
    echo "Fichas actualizadas a LECTIVA: " . $stmtF->rowCount() . "\n";

    // 2. Migrar Aprendices
    // Lógica: Todos a LECTIVA EXCEPTO los estados inactivos especificados
    $estadosExcluidos = [
        'FINALIZADO', 
        'RETIRO', 
        'TRASLADO', 
        'CANCELADO', 
        'APLAZADO',
        // Variaciones para asegurar integridad
        'RETIRADO',
        'CANCELADA',
        'FINALIZADA'
    ];
    
    // Convertir array a string para SQL: 'FINALIZADO','RETIRO',...
    $inQuery = implode("','", $estadosExcluidos);
    
    $sqlAprendices = "UPDATE aprendices 
                      SET estado = 'LECTIVA' 
                      WHERE estado NOT IN ('$inQuery')";
                      
    $stmtA = $conn->prepare($sqlAprendices);
    $stmtA->execute();
    echo "Aprendices actualizados a LECTIVA: " . $stmtA->rowCount() . "\n";

    // 3. Limpiar tabla ESTADOS (Asegurar que LECTIVA existe y EN FORMACION no)
    // Primero insertamos LECTIVA si no existe
    $stmtCheck = $conn->query("SELECT COUNT(*) FROM estados WHERE nombre = 'LECTIVA'");
    if ($stmtCheck->fetchColumn() == 0) {
        $conn->exec("INSERT INTO estados (nombre) VALUES ('LECTIVA')");
        echo "Estado LECTIVA agregado a tabla maestra.\n";
    }

    // Eliminar EN FORMACION de tabla estados
    $conn->exec("DELETE FROM estados WHERE nombre LIKE 'EN FORMACION%' OR nombre LIKE 'EN FORMACIÓN%'");
    echo "Limpieza de tabla estados realizada.\n";

    echo "=== Migración Completada ===\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
