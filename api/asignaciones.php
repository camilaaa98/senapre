<?php
/**
 * Asignaciones API - Sistema de fechas individuales con horarios automáticos
 * Optimizado para PostgreSQL (Render) y SQLite (Local)
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    // Determinar motor de BD
    $isPg = strpos($database->getDbPath(), 'PostgreSQL') !== false;

    // Migración inicial / Asegurar estructura
    try {
        if ($isPg) {
            $check = $conn->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'asignacion_instructores')");
            $exists = $check->fetchColumn();
            
            if (!$exists) {
                // Sin claves foráneas explícitas para evitar conflictos de tipos (Long vs String) entre motores
                $conn->exec("CREATE TABLE asignacion_instructores (
                    id_asignacion SERIAL PRIMARY KEY,
                    id_usuario TEXT NOT NULL,
                    numero_ficha TEXT NOT NULL,
                    dias_formacion TEXT NOT NULL,
                    hora_inicio TEXT NOT NULL,
                    hora_fin TEXT NOT NULL
                )");
            } else {
                // Asegurar columnas para instalaciones existentes
                $cols = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'asignacion_instructores'")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('dias_formacion', $cols)) $conn->exec("ALTER TABLE asignacion_instructores ADD COLUMN dias_formacion TEXT DEFAULT ''");
                if (!in_array('hora_inicio', $cols)) $conn->exec("ALTER TABLE asignacion_instructores ADD COLUMN hora_inicio TEXT DEFAULT '06:00'");
                if (!in_array('hora_fin', $cols)) $conn->exec("ALTER TABLE asignacion_instructores ADD COLUMN hora_fin TEXT DEFAULT '18:00'");
            }
        } else {
            $conn->exec("CREATE TABLE IF NOT EXISTS asignacion_instructores (
                id_asignacion INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario TEXT NOT NULL,
                numero_ficha TEXT NOT NULL,
                dias_formacion TEXT NOT NULL,
                hora_inicio TEXT NOT NULL,
                hora_fin TEXT NOT NULL
            )");
        }
    } catch (Exception $e) {
        error_log("Migration Warning: " . $e->getMessage());
    }

    // GET - Listar asignaciones
    if ($method === 'GET') {
        // En PG, id_usuario en la tabla usuarios suele ser numérico (SERIAL/INT)
        $joinUsuario = $isPg ? "JOIN usuarios u ON a.id_usuario = CAST(u.id_usuario AS TEXT)" : "JOIN usuarios u ON a.id_usuario = u.id_usuario";

        $sql = "SELECT a.*, 
                       u.nombre || ' ' || u.apellido as nombre_instructor,
                       f.nombre_programa,
                       f.jornada
                FROM asignacion_instructores a
                $joinUsuario
                JOIN fichas f ON a.numero_ficha = f.numero_ficha
                ORDER BY a.numero_ficha, a.dias_formacion";
        
        $stmt = $conn->query($sql);
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $asignaciones]);
        exit;
    }

    // POST - Crear asignaciones (múltiples fechas)
    if ($method === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (empty($data['id_usuario']) || empty($data['numero_ficha'])) {
            throw new Exception('Instructor y Ficha son requeridos');
        }
        
        if (empty($data['fechas']) || !is_array($data['fechas'])) {
            throw new Exception('Debe seleccionar al menos una fecha');
        }
        
        $sql = "INSERT INTO asignacion_instructores 
                (id_usuario, numero_ficha, dias_formacion, hora_inicio, hora_fin)
                VALUES (:id_usuario, :numero_ficha, :fecha, :hora_inicio, :hora_fin)";
        
        $stmt = $conn->prepare($sql);
        $conn->beginTransaction();
        
        try {
            foreach ($data['fechas'] as $fecha) {
                // Verificar duplicados para esta fecha específica
                $checkSql = "SELECT COUNT(*) as count FROM asignacion_instructores 
                            WHERE id_usuario = :id_usuario 
                            AND numero_ficha = :numero_ficha 
                            AND dias_formacion = :fecha";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([
                    ':id_usuario' => (string)$data['id_usuario'],
                    ':numero_ficha' => (string)$data['numero_ficha'],
                    ':fecha' => (string)$fecha
                ]);
                
                if ($checkStmt->fetch()['count'] == 0) {
                    $stmt->execute([
                        ':id_usuario' => (string)$data['id_usuario'],
                        ':numero_ficha' => (string)$data['numero_ficha'],
                        ':fecha' => (string)$fecha,
                        ':hora_inicio' => $data['hora_inicio'] ?? '06:00',
                        ':hora_fin' => $data['hora_fin'] ?? '18:00'
                    ]);
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Asignaciones procesadas']);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        exit;
    }
    
    // DELETE - Eliminar asignaciones
    if ($method === 'DELETE') {
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare("DELETE FROM asignacion_instructores WHERE id_asignacion = ?");
            $stmt->execute([$_GET['id']]);
        } elseif (isset($_GET['id_usuario']) && isset($_GET['numero_ficha'])) {
            $stmt = $conn->prepare("DELETE FROM asignacion_instructores WHERE id_usuario = ? AND numero_ficha = ?");
            $stmt->execute([$_GET['id_usuario'], $_GET['numero_ficha']]);
        }
        echo json_encode(['success' => true, 'message' => 'Eliminado']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'debug_info' => 'Verifique que el instructor exista y no haya conflictos de tipos.'
    ]);
}
?>
