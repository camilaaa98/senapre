<?php
require_once 'api/config/Database.php';

$db = Database::getInstance()->getConnection();

// Buscar a Erik
$sql = "SELECT u.*, GROUP_CONCAT(ar.area) as areas FROM usuarios u 
          LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
          WHERE u.correo LIKE '%erik%' OR u.id_usuario = '1117506963' 
          GROUP BY u.id_usuario";

$stmt = $db->prepare($sql);
$stmt->execute();
$erik = $stmt->fetch(PDO::FETCH_ASSOC);

if ($erik) {
    echo "=== DATOS DE ERIK ===\n";
    echo "ID: " . $erik['id_usuario'] . "\n";
    echo "Nombre: " . $erik['nombre'] . " " . $erik['apellido'] . "\n";
    echo "Rol: " . $erik['rol'] . "\n";
    echo "Áreas: " . $erik['areas'] . "\n";
    
    // Verificar si es jefe de bienestar
    $esJefeBienestar = strpos($erik['areas'], 'jefe_bienestar') !== false;
    echo "Es Jefe de Bienestar: " . ($esJefeBienestar ? 'SÍ' : 'NO') . "\n";
} else {
    echo "❌ Erik no encontrado en el sistema\n";
}
?>
