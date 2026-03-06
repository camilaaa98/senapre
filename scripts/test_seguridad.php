<?php
require_once __DIR__ . '/api/config/Database.php';
$conn = Database::getInstance()->getConnection();

// Verificar que el correo del aprendiz NO tiene cuenta de usuario en el sistema
$stmt = $conn->prepare("SELECT id_usuario, nombre, apellido, correo, rol FROM usuarios 
    WHERE correo LIKE '%vargas%' OR correo LIKE '%heidy%' OR correo LIKE '%vargashiguera%'");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) === 0) {
    echo "CONFIRMADO: El correo 'vargashigueraheidydaniela@gmail.com'\n";
    echo "NO tiene cuenta de acceso en la tabla usuarios.\n";
    echo "Solo aparece como dato de contacto en la tabla aprendices.\n";
} else {
    echo "ALERTA — Se encontraron " . count($rows) . " registros en usuarios:\n";
    foreach ($rows as $r) {
        echo "  {$r['id_usuario']} | {$r['nombre']} {$r['apellido']} | {$r['correo']} | rol: {$r['rol']}\n";
    }
}
