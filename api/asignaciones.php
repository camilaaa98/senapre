<?php
/**
 * Asignaciones API - Sistema de fechas individuales con horarios automáticos
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

    // Verificar y actualizar estructura de tabla
    $isPg = strpos($database->getDbPath(), 'PostgreSQL') !== false;
    try {
        if ($isPg) {
            $check = $conn->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'asignacion_instructores')");
            $exists = $check->fetchColumn();
            
            if (!$exists) {
                $conn->exec("CREATE TABLE asignacion_instructores (
                    id_asignacion SERIAL PRIMARY KEY,
                    id_usuario TEXT NOT NULL,
                    numero_ficha TEXT NOT NULL,
                    dias_formacion TEXT NOT NULL,
                    hora_inicio TEXT NOT NULL,
                    hora_fin TEXT NOT NULL,
                    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
                    FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha)
                )");
            } else {
                // Migración: Asegurar columnas
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
                hora_fin TEXT NOT NULL,
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
                FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha)
            )");
        }
    } catch (Exception $e) {
        error_log("Migration error: " . $e->getMessage());
    }

    // GET - Listar asignaciones
    if ($method === 'GET') {
        $isPg = strpos($database->getDbPath(), 'PostgreSQL') !== false;
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
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos
        if (empty($data['id_usuario']) || empty($data['numero_ficha'])) {
            throw new Exception('Instructor y Ficha son requeridos');
        }
        
        if (empty($data['fechas']) || !is_array($data['fechas'])) {
            throw new Exception('Debe seleccionar al menos una fecha');
        }
        
        if (empty($data['hora_inicio']) || empty($data['hora_fin'])) {
            throw new Exception('Horario requerido');
        }
        
        // Preparar statement para inserción
        $sql = "INSERT INTO asignacion_instructores 
                (id_usuario, numero_ficha, dias_formacion, hora_inicio, hora_fin)
                VALUES (:id_usuario, :numero_ficha, :fecha, :hora_inicio, :hora_fin)";
        
        $stmt = $conn->prepare($sql);
        
        $conn->beginTransaction();
        
        try {
            // Insertar un registro por cada fecha seleccionada
            foreach ($data['fechas'] as $fecha) {
                // Verificar si ya existe asignación para esta fecha
                $checkSql = "SELECT COUNT(*) as count FROM asignacion_instructores 
                            WHERE id_usuario = :id_usuario 
                            AND numero_ficha = :numero_ficha 
                            AND dias_formacion = :fecha";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([
                    ':id_usuario' => $data['id_usuario'],
                    ':numero_ficha' => $data['numero_ficha'],
                    ':fecha' => $fecha
                ]);
                
                $exists = $checkStmt->fetch()['count'] > 0;
                
                if (!$exists) {
                    $stmt->execute([
                        ':id_usuario' => $data['id_usuario'],
                        ':numero_ficha' => $data['numero_ficha'],
                        ':fecha' => $fecha,
                        ':hora_inicio' => $data['hora_inicio'],
                        ':hora_fin' => $data['hora_fin']
                    ]);
                }
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => count($data['fechas']) . ' asignación(es) creada(s) exitosamente'
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
        exit;
    }
    
    // DELETE - Eliminar asignaciones
    if ($method === 'DELETE') {
        // Puede eliminar por ID individual o por instructor+ficha
        if (isset($_GET['id'])) {
            // Eliminar asignación específica
            $stmt = $conn->prepare("DELETE FROM asignacion_instructores WHERE id_asignacion = ?");
            $stmt->execute([$_GET['id']]);
            $message = 'Asignación eliminada';
        } elseif (isset($_GET['id_usuario']) && isset($_GET['numero_ficha'])) {
            // Eliminar todas las asignaciones de un instructor en una ficha
            $stmt = $conn->prepare("DELETE FROM asignacion_instructores 
                                    WHERE id_usuario = ? AND numero_ficha = ?");
            $stmt->execute([$_GET['id_usuario'], $_GET['numero_ficha']]);
            $message = 'Todas las asignaciones eliminadas';
        } else {
            throw new Exception('ID o instructor+ficha requeridos');
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
