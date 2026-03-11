<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // ACCIONES GET
    if ($method === 'GET') {
        
        if ($action === 'getResponsable') {
            $area = $_GET['area'] ?? 'liderazgo';
            $sql = "SELECT ar.id_usuario, u.nombre, u.apellido, u.correo 
                    FROM area_responsables ar
                    JOIN usuarios u ON ar.id_usuario = u.id_usuario
                    WHERE ar.area = :area
                    ORDER BY ar.id DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':area' => $area]);
            $resp = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $resp]);
            exit;
        }

        // Obtener consolidado de líderes (Liderazgo)
        if ($action === 'getLideres') {
            $filtro = $_GET['filtro'] ?? 'todos'; // principales, suplentes, enfoque, representantes
            
            $lideres = [];

            // 1. Voceros Principales de Fichas
            if ($filtro === 'todos' || $filtro === 'principales') {
                $sqlPrincipales = "SELECT f.numero_ficha, a.documento, a.nombre, a.apellido, a.correo, a.celular, a.tipo_poblacion
                                 FROM fichas f
                                 JOIN aprendices a ON f.vocero_principal = a.documento";
                $stmt = $conn->query($sqlPrincipales);
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($res as $row) {
                    $lideres[] = [
                        'documento' => $row['documento'], 'nombre' => $row['nombre'], 'apellido' => $row['apellido'],
                        'correo' => $row['correo'], 'telefono' => $row['celular'], 'tipo' => 'Vocero Principal',
                        'detalle' => $row['numero_ficha'],
                        'poblacion' => $row['tipo_poblacion'] ?? 'Ninguna'
                    ];
                }
            }

            // 1.5 Voceros Suplentes de Fichas
            if ($filtro === 'todos' || $filtro === 'suplentes') {
                $sqlSuplentes = "SELECT f.numero_ficha, a.documento, a.nombre, a.apellido, a.correo, a.celular, a.tipo_poblacion
                                 FROM fichas f
                                 JOIN aprendices a ON f.vocero_suplente = a.documento";
                $stmt = $conn->query($sqlSuplentes);
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($res as $row) {
                    $lideres[] = [
                        'documento' => $row['documento'], 'nombre' => $row['nombre'], 'apellido' => $row['apellido'],
                        'correo' => $row['correo'], 'telefono' => $row['celular'], 'tipo' => 'Vocero Suplente',
                        'detalle' => $row['numero_ficha'],
                        'poblacion' => $row['tipo_poblacion'] ?? 'Ninguna'
                    ];
                }
            }

            // 2. Voceros de Enfoque Diferencial
            if ($filtro === 'todos' || $filtro === 'enfoque') {
                $sqlEnfoque = "SELECT v.tipo_poblacion as cat_pob, a.documento, a.nombre, a.apellido, a.correo, a.celular, a.numero_ficha, a.tipo_poblacion as pob_a
                               FROM voceros_enfoque v
                               JOIN aprendices a ON v.documento = a.documento";
                $stmt = $conn->query($sqlEnfoque);
                $resEnfoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($resEnfoque as $row) {
                    $lideres[] = [
                        'documento' => $row['documento'], 'nombre' => $row['nombre'], 'apellido' => $row['apellido'],
                        'correo' => $row['correo'], 'telefono' => $row['celular'], 'tipo' => 'Vocero Enfoque ' . $row['cat_pob'],
                        'detalle' => $row['numero_ficha'],
                        'poblacion' => $row['pob_a'] ?? $row['cat_pob']
                    ];
                }
            }

            // 3. Representantes de Jornada
            if ($filtro === 'todos' || $filtro === 'representantes') {
                $sqlRep = "SELECT r.jornada, a.documento, a.nombre, a.apellido, a.correo, a.celular, a.numero_ficha, a.tipo_poblacion as pob_a
                           FROM representantes_jornada r
                           JOIN aprendices a ON r.documento = a.documento";
                $stmt = $conn->query($sqlRep);
                $resRep = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($resRep as $row) {
                    $lideres[] = [
                        'documento' => $row['documento'], 'nombre' => $row['nombre'], 'apellido' => $row['apellido'],
                        'correo' => $row['correo'], 'telefono' => $row['celular'], 'tipo' => 'Representante ' . $row['jornada'],
                        'detalle' => $row['numero_ficha'],
                        'poblacion' => $row['pob_a'] ?? 'Ninguna'
                    ];
                }
            }

            echo json_encode(['success' => true, 'data' => $lideres]);
            exit;
        }

        // Obtener historial de asistencia de un aprendiz específico
        if ($action === 'getHistorialAsistencia') {
            $doc = $_GET['documento'];
            $sql = "SELECT ba.*, br.titulo, br.fecha as fecha_reunion
                    FROM bienestar_asistencia ba
                    JOIN bienestar_reuniones br ON ba.id_reunion = br.id
                    WHERE ba.id_aprendiz = :doc
                    ORDER BY br.fecha DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':doc' => $doc]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }


        // Obtener lista de reuniones
        if ($action === 'getReuniones') {
            $sql = "SELECT * FROM bienestar_reuniones ORDER BY fecha DESC";
            $stmt = $conn->query($sql);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // Obtener asistencia de una reunión específica
        if ($action === 'getReunionAsistencia') {
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception('ID de reunión requerido');
            $sql = "SELECT * FROM bienestar_asistencia WHERE id_reunion = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }
    }

    // ACCIONES POST
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        // Asignar responsable del área
        if ($action === 'setResponsable') {
            if (empty($data['id_usuario'])) throw new Exception('Usuario no proporcionado');
            
            $sql = "INSERT INTO area_responsables (id_usuario, area) VALUES (:user, :area)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':user' => $data['id_usuario'], ':area' => $data['area'] ?? 'liderazgo']);
            
            echo json_encode(['success' => true, 'message' => 'Responsable asignado']);
            exit;
        }

        // Guardar nueva reunión y procesar notificaciones
        if ($action === 'saveReunion') {
            if (empty($data['titulo']) || empty($data['fecha'])) throw new Exception('Datos incompletos');
            
            // Intentamos insertar con todos los campos (Titulo, Fecha, Hora, Lugar)
            $sql = "INSERT INTO bienestar_reuniones (titulo, fecha, hora, lugar) 
                    VALUES (:t, :f, :h, :l)";
            
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':t' => $data['titulo'],
                    ':f' => $data['fecha'],
                    ':h' => $data['hora'] ?? '08:00',
                    ':l' => $data['lugar'] ?? 'SENA'
                ]);
            } catch (Exception $e) {
                // Fallback si las columnas hora o lugar no existen en producción aún
                $stmt = $conn->prepare("INSERT INTO bienestar_reuniones (titulo, fecha) VALUES (:t, :f)");
                $stmt->execute([
                    ':t' => $data['titulo'],
                    ':f' => $data['fecha']
                ]);
            }
            
            $idReunion = $conn->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Reunión creada', 'id' => $idReunion]);
            exit;
        }

        // Actualizar datos de contacto de un líder (Aprendiz)
        if ($action === 'updateLider') {
            if (empty($data['documento'])) throw new Exception('Documento requerido');
            
            $sql = "UPDATE aprendices SET correo = :c, celular = :t WHERE documento = :d";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':c' => $data['correo'] ?? null,
                ':t' => $data['telefono'] ?? null,
                ':d' => $data['documento']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Datos actualizados']);
            exit;
        }

        // Registrar asistencia (Biométrica o Manual)
        if ($action === 'registrarAsistencia') {
            $id_reunion = $data['id_reunion'];
            $id_aprendiz = $data['id_aprendiz'];
            $estado = $data['estado'] ?? 'asistio';
            
            // 1. REGLA DE VOCERÍA: Si un suplente asiste, el principal de su ficha se marca como "justificado"
            if ($estado === 'asistio') {
                // Verificar si es un suplente
                $sqlSuplente = "SELECT numero_ficha, vocero_principal FROM fichas WHERE vocero_suplente = :doc";
                $stmtSup = $conn->prepare($sqlSuplente);
                $stmtSup->execute([':doc' => $id_aprendiz]);
                $ficha = $stmtSup->fetch(PDO::FETCH_ASSOC);

                if ($ficha && $ficha['vocero_principal']) {
                    $principal = $ficha['vocero_principal'];
                    // Registrar o actualizar al principal como JUSTIFICADO por suplencia
                    $conn->prepare("DELETE FROM bienestar_asistencia WHERE id_reunion = :r AND id_aprendiz = :a")
                         ->execute([':r' => $id_reunion, ':a' => $principal]);
                    $conn->prepare("INSERT INTO bienestar_asistencia (id_reunion, id_aprendiz, estado, nota) 
                                    VALUES (:r, :a, 'justificado', 'Cubierto por Suplente')")
                         ->execute([':r' => $id_reunion, ':a' => $principal]);
                }
            }

            // 2. Registrar/Actualizar la asistencia actual
            $check = $conn->prepare("SELECT id FROM bienestar_asistencia WHERE id_reunion = :r AND id_aprendiz = :a");
            $check->execute([':r' => $id_reunion, ':a' => $id_aprendiz]);
            
            if ($check->fetch()) {
                $sql = "UPDATE bienestar_asistencia SET estado = :e, fecha_registro = CURRENT_TIMESTAMP 
                        WHERE id_reunion = :r AND id_aprendiz = :a";
            } else {
                $sql = "INSERT INTO bienestar_asistencia (id_reunion, id_aprendiz, estado, fecha_registro) 
                        VALUES (:r, :a, :e, CURRENT_TIMESTAMP)";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([':r' => $id_reunion, ':a' => $id_aprendiz, ':e' => $estado]);

            // 3. Verificar fallas acumuladas
            $infoFallas = verificarPerdidaRol($id_aprendiz, $conn);

            echo json_encode([
                'success' => true, 
                'message' => 'Asistencia registrada',
                'alerta_fallas' => $infoFallas['fallas'] >= 3,
                'total_fallas' => $infoFallas['fallas']
            ]);
            exit;
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Verifica cuántas inasistencias injustificadas tiene un aprendiz en bienestar y revoca rol si son >= 3
 */
function verificarPerdidaRol($documento, $conn) {
    $sql = "SELECT COUNT(*) as fallas FROM bienestar_asistencia 
            WHERE id_aprendiz = :doc AND estado = 'ausente' 
            AND id NOT IN (SELECT id_asistencia FROM bienestar_excusas WHERE estado_excusa = 'validada')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc' => $documento]);
    $fallas = $stmt->fetch()['fallas'];

    $revocado = false;
    if ($fallas >= 3) {
        // REVOCAR ROLES
        $conn->prepare("UPDATE fichas SET vocero_principal = NULL WHERE vocero_principal = :doc")->execute([':doc' => $documento]);
        $conn->prepare("UPDATE fichas SET vocero_suplente = NULL WHERE vocero_suplente = :doc")->execute([':doc' => $documento]);
        $conn->prepare("UPDATE voceros_enfoque SET documento = NULL WHERE documento = :doc")->execute([':doc' => $documento]);
        $conn->prepare("DELETE FROM representantes_jornada WHERE documento = :doc")->execute([':doc' => $documento]);
        $revocado = true;
    }
    return ['fallas' => $fallas, 'revocado' => $revocado];
}
?>
