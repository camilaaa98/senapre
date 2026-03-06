<?php
require_once __DIR__ . '/../api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $conn->beginTransaction();
    
    echo "→ Sincronizando nombres desde tabla instructores a usuarios...\n";
    
    // 1. Sincronización general: Copiar Nombres y Apellidos de instructores a usuarios
    // Esto soluciona casos como ID 16 (MARIA ALEJANDRA)
    $stmtSync = $conn->query("
        SELECT id_usuario, nombres, apellidos 
        FROM instructores
    ");
    $registros = $stmtSync->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($registros as $reg) {
        $stmtUpd = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ? WHERE id_usuario = ? AND rol = 'instructor'");
        $stmtUpd->execute([$reg['nombres'], $reg['apellidos'], $reg['id_usuario']]);
    }
    
    echo "→ Aplicando correcciones manuales específicas...\n";
    
    // 2. Caso PEDRO PINZON ARDILA (ID 42)
    // El usuario indicó que el nombre es PEDRO y los apellidos PINZON ARDILA
    $stmtPedro = $conn->prepare("UPDATE usuarios SET nombre = 'PEDRO', apellido = 'PINZON ARDILA' WHERE id_usuario = 42");
    $stmtPedro->execute();
    $stmtPedroI = $conn->prepare("UPDATE instructores SET nombres = 'PEDRO', apellidos = 'PINZON ARDILA' WHERE id_usuario = 42");
    $stmtPedroI->execute();
    
    // 3. Caso MARIA ALEJANDRA (ID 16) - Asegurar por si la sincronización no fue suficiente
    // Ya debería estar bien por el paso 1, pero lo forzamos por seguridad
    $stmtMaria = $conn->prepare("UPDATE usuarios SET nombre = 'MARIA ALEJANDRA', apellido = 'CABRERA' WHERE id_usuario = 16");
    $stmtMaria->execute();
    $stmtMariaI = $conn->prepare("UPDATE instructores SET nombres = 'MARIA ALEJANDRA', apellidos = 'CABRERA' WHERE id_usuario = 16");
    $stmtMariaI->execute();

    // 4. Limpieza de ruido (ID 60 y similares)
    $stmtLimpiar = $conn->prepare("UPDATE usuarios SET apellido = 'QUINTERO' WHERE id_usuario = 60");
    $stmtLimpiar->execute();
    $stmtLimpiarI = $conn->prepare("UPDATE instructores SET apellidos = 'QUINTERO' WHERE id_usuario = 60");
    $stmtLimpiarI->execute();
    
    $conn->commit();
    echo "✓ Saneamiento completado exitosamente.\n";
    
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
