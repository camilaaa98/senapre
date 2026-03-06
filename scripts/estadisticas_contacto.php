<?php
require_once __DIR__ . '/api/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== ESTADÍSTICAS DE CONTACTO ===\n\n";

$total = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
$sinCorreo = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NULL OR correo = ''")->fetchColumn();
$sinCelular = $conn->query("SELECT COUNT(*) FROM aprendices WHERE celular IS NULL OR celular = ''")->fetchColumn();
$conCorreo = $total - $sinCorreo;
$conCelular = $total - $sinCelular;

echo "Total de aprendices: $total\n\n";
echo "CON DATOS:\n";
echo "  Con correo: $conCorreo (" . round(($conCorreo/$total)*100, 1) . "%)\n";
echo "  Con celular: $conCelular (" . round(($conCelular/$total)*100, 1) . "%)\n\n";
echo "SIN DATOS:\n";
echo "  Sin correo: $sinCorreo (" . round(($sinCorreo/$total)*100, 1) . "%)\n";
echo "  Sin celular: $sinCelular (" . round(($sinCelular/$total)*100, 1) . "%)\n";
?>
