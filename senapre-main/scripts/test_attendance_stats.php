<?php
require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Calculate Total Expected Attendance
    // Find all unique sessions (date, ficha)
    // For each session, add the number of active apprentices in that ficha
    $sql_expected = "
        SELECT SUM(ficha_counts.cnt) as total_expected
        FROM (
            SELECT DISTINCT a.fecha, ap.id_ficha
            FROM asistencias a
            JOIN aprendices ap ON a.id_aprendiz = ap.id_aprendiz
        ) as sessions
        JOIN (
            SELECT id_ficha, COUNT(*) as cnt
            FROM aprendices
            WHERE estado = 1
            GROUP BY id_ficha
        ) as ficha_counts ON sessions.id_ficha = ficha_counts.id_ficha
    ";

    $stmt = $conn->query($sql_expected);
    $total_expected = $stmt->fetch()['total_expected'];

    // 2. Calculate Total Present
    // Count all attendance records that are 'entrada' or 'completa'
    // Assuming 'asistencias' table only contains records for people who attended (or we filter by type)
    // If 'asistencias' contains 'fallas', we must exclude them.
    // Based on AsistenciasController, valid types are 'entrada', 'completa'.
    $sql_present = "SELECT COUNT(*) as total_present FROM asistencias WHERE tipo IN ('entrada', 'completa')";
    $stmt = $conn->query($sql_present);
    $total_present = $stmt->fetch()['total_present'];

    // Debug counts
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM aprendices");
    echo "Total Aprendices in DB: " . $stmt->fetch()['cnt'] . "\n";

    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM asistencias");
    echo "Total Asistencias in DB: " . $stmt->fetch()['cnt'] . "\n";

    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM fichas");
    echo "Total Fichas in DB: " . $stmt->fetch()['cnt'] . "\n";

    echo "Total Expected: " . ($total_expected ?? 0) . "\n";
    echo "Total Present: " . ($total_present ?? 0) . "\n";

    if ($total_expected > 0) {
        $percentage = ($total_present / $total_expected) * 100;
        echo "Percentage: " . round($percentage, 1) . "%\n";
    } else {
        echo "Percentage: 0% (No sessions found)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
