<?php
require_once __DIR__ . '/api/config/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== AUDITORÍA DE VOCEROS Y FICHAS ===\n\n";

// 1. Ver algunos usuarios con rol vocero
echo "--- Muestra de Usuarios Voceros ---\n";
$stmt = $conn->query("SELECT id_usuario, nombre, apellido, correo, rol FROM usuarios WHERE rol LIKE '%vocero%' LIMIT 10");
$voceros = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($voceros as $v) {
    echo "ID: {$v['id_usuario']} | Rol: {$v['rol']} | Nombre: {$v['nombre']} {$v['apellido']} | Correo: {$v['correo']}\n";
}

// 2. Buscar si el ID del usuario existe en fichas como vocero_principal o suplente
echo "\n--- Cruce Usuarios vs Fichas ---\n";
foreach ($voceros as $v) {
    $doc = $v['id_usuario'];
    $stmt = $conn->prepare("SELECT numero_ficha, 'Principal' as tipo FROM fichas WHERE vocero_principal = :doc 
                            UNION 
                            SELECT numero_ficha, 'Suplente' as tipo FROM fichas WHERE vocero_suplente = :doc");
    $stmt->execute([':doc' => $doc]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fichas) > 0) {
        foreach ($fichas as $f) {
            echo "MATCH: Usuario {$v['nombre']} ({$doc}) es {$f['tipo']} de Ficha {$f['numero_ficha']}\n";
        }
    } else {
        echo "ALERTA: Usuario {$v['nombre']} ({$doc}) NO está asignado a ninguna ficha como vocero.\n";
    }
}

// 3. Revisar una ficha específica (la de la captura)
$fichaPrueba = '2995479';
echo "\n--- Detalle Ficha $fichaPrueba ---\n";
$stmt = $conn->prepare("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE numero_ficha = :ficha");
$stmt->execute([':ficha' => $fichaPrueba]);
$fData = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($fData);

if ($fData) {
    echo "Verificando si existen estos documentos en la tabla 'usuarios':\n";
    $p = $fData['vocero_principal'];
    $s = $fData['vocero_suplente'];
    
    $stmt = $conn->prepare("SELECT id_usuario, nombre, rol FROM usuarios WHERE id_usuario IN (:p, :s)");
    $stmt->execute([':p' => $p, ':s' => $s]);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
