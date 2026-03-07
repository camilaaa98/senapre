<?php
/**
 * SENAPRE - Sincronización de Base de Datos (Producción)
 * Este script actualiza los nombres en singular a plural para cumplir con el estándar corporativo.
 */
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "--- INICIANDO MIGRACIÓN DE PRODUCCIÓN (PostgreSQL/Render) ---\n";
    
    // 1. Actualizar tabla tipoFormacion
    $conn->exec("DELETE FROM tipoFormacion");
    $tipos = [
        ['id' => 1, 'nombre' => 'Técnicos'],
        ['id' => 2, 'nombre' => 'Tecnólogos'],
        ['id' => 3, 'nombre' => 'Virtuales'],
        ['id' => 4, 'nombre' => 'Cursos'],
        ['id' => 5, 'nombre' => 'Auxiliares'],
        ['id' => 6, 'nombre' => 'Operarios'],
        ['id' => 7, 'nombre' => 'Especializaciones']
    ];
    $stmt = $conn->prepare("INSERT INTO tipoFormacion (id, nombre) VALUES (:id, :nombre)");
    foreach ($tipos as $t) {
        $stmt->execute($t);
        echo "Insertado en tipoFormacion: " . $t['nombre'] . "\n";
    }

    // 2. Mapeo de singular a plural para actualización de registros
    $mapeo = [
        'Técnico' => 'Técnicos',
        'Tecnólogo' => 'Tecnólogos',
        'Virtual' => 'Virtuales',
        'Curso' => 'Cursos',
        'Auxiliar' => 'Auxiliares',
        'Operario' => 'Operarios',
        'Especialización' => 'Especializaciones',
        'TECNICO' => 'Técnicos',
        'TECNOLOGO' => 'Tecnólogos',
        'ESPECIALIZACION' => 'Especializaciones',
        'CURSO' => 'Cursos',
        'CURSO VIRTUAL' => 'Virtuales'
    ];

    // 3. Actualizar tabla fichas
    foreach ($mapeo as $old => $new) {
        // Normal
        $stmtFichas = $conn->prepare("UPDATE fichas SET tipoFormacion = :new WHERE TRIM(UPPER(tipoFormacion)) = TRIM(UPPER(:old))");
        $stmtFichas->execute([':new' => $new, ':old' => $old]);
        $count = $stmtFichas->rowCount();
        if ($count > 0) echo "Fichas actualizadas ($old -> $new): $count\n";

        // Cerrado
        $stmtFichasCerrado = $conn->prepare("UPDATE fichas SET tipoFormacion = :new WHERE TRIM(UPPER(tipoFormacion)) = TRIM(UPPER(:old))");
        $stmtFichasCerrado->execute([':new' => $new . " - Cerrado", ':old' => $old . " - Cerrado"]);
        $countC = $stmtFichasCerrado->rowCount();
        if ($countC > 0) echo "Fichas cerradas actualizadas ($old - Cerrado -> $new - Cerrado): $countC\n";
    }

    // 4. Actualizar tabla programas_formacion
    foreach ($mapeo as $old => $new) {
        $stmtProgs = $conn->prepare("UPDATE programas_formacion SET nivel_formacion = :new WHERE TRIM(UPPER(nivel_formacion)) = TRIM(UPPER(:old))");
        $stmtProgs->execute([':new' => $new, ':old' => $old]);
        $countP = $stmtProgs->rowCount();
        if ($countP > 0) echo "Programas actualizados ($old -> $new): $countP\n";
    }

    echo "\n--- MIGRACIÓN COMPLETADA EXITOSAMENTE ---\n";

} catch (Exception $e) {
    echo "ERROR CRITICO: " . $e->getMessage() . "\n";
}
?>
