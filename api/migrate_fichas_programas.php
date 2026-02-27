<?php
require_once __DIR__ . '/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "Iniciando migración de Tipo de Formación...\n";

    // 1. Obtener todos los programas
    $stmt = $conn->query("SELECT nombre_programa, nivel_formacion FROM programas_formacion");
    $programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    $updated = 0;

    foreach ($programas as $prog) {
        $nombre = $prog['nombre_programa'];
        $nivel = strtoupper(trim($prog['nivel_formacion']));
        $tipo = null;

        // Mapeo solicitado
        if (strpos($nivel, 'TECNOLOGO') !== false) {
            $tipo = 'Tecnólogos';
        } elseif (strpos($nivel, 'TECNICO') !== false) {
            $tipo = 'Técnicos';
        } elseif (strpos($nivel, 'AUXILIAR') !== false) {
            $tipo = 'Auxiliar';
        } elseif (strpos($nivel, 'OPERARIO') !== false) {
            $tipo = 'Operarios';
        }

        if ($tipo) {
            // Actualizar fichas de este programa
            $sql = "UPDATE fichas SET tipoFormacion = :tipo WHERE nombre_programa = :nombre"; // AND (tipoFormacion IS NULL OR tipoFormacion = '')? User said migrate, maybe overwrite is better to ensure consistency.
            // Let's overwrite to ensure it matches the program level.
            $updateStmt = $conn->prepare($sql);
            $updateStmt->execute([':tipo' => $tipo, ':nombre' => $nombre]);
            
            if ($updateStmt->rowCount() > 0) {
                # echo "Actualizado: $nombre -> $tipo (" . $updateStmt->rowCount() . " fichas)\n";
                $updated += $updateStmt->rowCount();
            }
        } else {
            # echo "Saltado: $nombre (Nivel: $nivel no mapeado)\n";
        }
        $count++;
    }

    echo "Migración completada.\n";
    echo "Programas procesados: $count\n";
    echo "Fichas actualizadas: $updated\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
