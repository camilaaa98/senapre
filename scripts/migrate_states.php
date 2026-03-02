<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "Iniciando migración de estados...\n";

    // 1. Rename CANCELADA -> CANCELADO
    $sql1 = "UPDATE aprendices SET estado = 'CANCELADO' WHERE estado = 'CANCELADA'";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute();
    echo "Actualizados CANCELADA -> CANCELADO: " . $stmt1->rowCount() . " registros.\n";

    // 2. Rename FINALIZADA -> FINALIZADO
    $sql2 = "UPDATE aprendices SET estado = 'FINALIZADO' WHERE estado = 'FINALIZADA'";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute();
    echo "Actualizados FINALIZADA -> FINALIZADO: " . $stmt2->rowCount() . " registros.\n";
    
    // 3. Update Fichas table if necessary (though usually status is on apprentices)
    // Checking if 'fichas' table has these states too just in case
    $sql3 = "UPDATE fichas SET estado = 'CANCELADO' WHERE estado = 'CANCELADA'";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->execute();
    echo "Actualizados Fichas CANCELADA -> CANCELADO: " . $stmt3->rowCount() . " registros.\n";

    $sql4 = "UPDATE fichas SET estado = 'FINALIZADO' WHERE estado = 'FINALIZADA'";
    $stmt4 = $conn->prepare($sql4);
    $stmt4->execute();
    echo "Actualizados Fichas FINALIZADA -> FINALIZADO: " . $stmt4->rowCount() . " registros.\n";

    echo "Migración completada exitosamente.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
