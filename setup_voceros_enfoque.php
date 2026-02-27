<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Crear tabla para voceros de enfoque diferencial
    $conn->exec("CREATE TABLE IF NOT EXISTS voceros_enfoque (
        tipo_poblacion VARCHAR(50) PRIMARY KEY,
        documento VARCHAR(20),
        FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE SET NULL
    )");

    // Inicializar las 6 categorías si no existen
    $categorias = ['Mujer', 'Indígena', 'NARP', 'Campesino', 'LGBTIQ+', 'Discapacidad'];
    foreach ($categorias as $cat) {
        $conn->prepare("INSERT OR IGNORE INTO voceros_enfoque (tipo_poblacion, documento) VALUES (:cat, NULL)")
             ->execute([':cat' => $cat]);
    }

    // Opcional: Eliminar tabla victima si existe
    $conn->exec("DROP TABLE IF EXISTS victima");

    echo "✓ Infraestructura de Voceros Enfoque Diferencial creada.\n";
    echo "✓ Tabla 'victima' eliminada.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
