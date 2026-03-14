<?php
require_once 'api/config/Database.php';

$db = Database::getInstance()->getConnection();

echo "=== BÚSQUEDA DE ERIK ===\n\n";

// Búsqueda 1: Por documento
echo "1. Buscando por documento 1117506963...\n";
$sql1 = "SELECT * FROM usuarios WHERE id_usuario = '1117506963'";
$stmt1 = $db->prepare($sql1);
$stmt1->execute();
$result1 = $stmt1->fetch(PDO::FETCH_ASSOC);

if ($result1) {
    echo "✅ Encontrado por documento:\n";
    echo "   ID: " . $result1['id_usuario'] . "\n";
    echo "   Nombre: " . $result1['nombre'] . " " . $result1['apellido'] . "\n";
    echo "   Rol: " . $result1['rol'] . "\n";
    echo "   Correo: " . $result1['correo'] . "\n";
    echo "   Estado: " . $result1['estado'] . "\n";
} else {
    echo "❌ No encontrado por documento\n";
}

echo "\n";

// Búsqueda 2: Por nombre Erik
echo "2. Buscando por nombre Erik...\n";
$sql2 = "SELECT * FROM usuarios WHERE nombre LIKE '%Erik%' OR apellido LIKE '%Erik%'";
$stmt2 = $db->prepare($sql2);
$stmt2->execute();
$results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Resultados por nombre:\n";
foreach ($results2 as $result) {
    echo "   - " . $result['nombre'] . " " . $result['apellido'] . " (" . $result['id_usuario'] . ")\n";
}

echo "\n";

// Búsqueda 3: Por correo con Erik
echo "3. Buscando por correo con Erik...\n";
$sql3 = "SELECT * FROM usuarios WHERE correo LIKE '%erik%'";
$stmt3 = $db->prepare($sql3);
$stmt3->execute();
$results3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Resultados por correo:\n";
foreach ($results3 as $result) {
    echo "   - " . $result['correo'] . " (" . $result['id_usuario'] . ")\n";
}

echo "\n";

// Búsqueda 4: Todos los administrativos
echo "4. Todos los usuarios administrativos:\n";
$sql4 = "SELECT u.*, GROUP_CONCAT(ar.area) as areas FROM usuarios u 
          LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
          WHERE u.rol = 'administrativo' 
          GROUP BY u.id_usuario";
$stmt4 = $db->prepare($sql4);
$stmt4->execute();
$results4 = $stmt4->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Usuarios administrativos:\n";
foreach ($results4 as $result) {
    echo "   - " . $result['nombre'] . " " . $result['apellido'] . " (" . $result['id_usuario'] . ") - Áreas: " . ($result['areas'] ?: 'Sin áreas') . "\n";
}

// Verificar áreas de bienestar
echo "\n5. Áreas de bienestar configuradas:\n";
$sql5 = "SELECT DISTINCT area FROM area_responsables";
$stmt5 = $db->prepare($sql5);
$stmt5->execute();
$areas = $stmt5->fetchAll(PDO::FETCH_COLUMN);

echo "📋 Áreas disponibles:\n";
foreach ($areas as $area) {
    echo "   - " . $area . "\n";
}
?>
