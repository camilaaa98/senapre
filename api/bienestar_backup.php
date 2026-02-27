<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // ACCIONES GET
    if ($method === 'GET') {
        
        // Obtener responsable del área
        if ($action === 'getResponsable') {
            $sql = "SELECT ar.*, u.nombre, u.apellido, u.correo 
                    FROM area_responsables ar
                    JOIN usuarios u ON ar.id_usuario = u.id_usuario
                    WHERE ar.area = 'voceros_y_representantes'
                    ORDER BY ar.fecha_asignacion DESC LIMIT 1";
            $stmt = $conn->query($sql);
            echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            exit;
        }

        // Obtener consolidado de líderes (Voceros y Representantes)
        if ($action === 'getLideres') {
            $filtro = $_GET['filtro'] ?? 'todos'; // principales, suplentes, enfoque, representantes
            
            $lideres = [];

            // 1. Voceros Principales y Suplentes de Fichas
            if ($filtro === 'todos' || $filtro === 'principales' || $filtro === 'suplentes') {
                $sqlFichas = "SELECT 'ficha' as origen, f.numero_ficha, f.nombre_programa, 
                              vp.documento as doc_p, vp.nombre as nom_p, vp.apellido as ape_p, vp.correo as cor_p, vp.telefono as tel_p,
                              vs.documento as doc_s, vs.nombre as nom_s, vs.apellido as ape_s, vs.correo as cor_s, vs.telefono as tel_s
                              FROM fichas f
                              LEFT JOIN aprendices vp ON f.vocero_principal = vp.documento
                              LEFT JOIN aprendices vs ON f.vocero_suplente = vs.documento";
                $stmt = $conn->query($sqlFichas);
                $resFichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($resFichas as $row) {
                    if (($filtro === 'todos' || $filtro === 'principales') && $row['doc_p']) {
                        $lideres[] = [
                            'documento' => $row['doc_p'], 'nombre' => $row['nom_p'], 'apellido' => $row['ape_p'],
                            'correo' => $row['cor_p'], 'telefono' => $row['tel_p'], 'tipo' => 'Vocero Principal',
                            'detalle' => "Ficha: " . $row['numero_ficha']
                        ];
                    }
                    if (($filtro === 'todos' || $filtro === 'suplentes') && $row['doc_s']) {
                        $lideres[] = [
                            'documento' => $row['doc_s'], 'nombre' => $row['nom_s'], 'apellido' => $row['ape_s'],
                            'correo' => $row['cor_s'], 'telefono' => $row['tel_s'], 'tipo' => 'Vocero Suplente',
                            'detalle' => "Ficha: " . $row['numero_ficha']
                        ];
                    }
                }
            }

            // 2. Voceros de Enfoque Diferencial
            if ($filtro === 'todos' || $filtro === 'enfoque') {
                $sqlEnfoque = "SELECT v.tipo_poblacion, a.documento, a.nombre, a.apellido, a.correo, a.telefono, a.numero_ficha
                               FROM voceros_enfoque v
                               JOIN aprendices a ON v.documento = a.documento";
                $stmt = $conn->query($sqlEnfoque);
                $resEnfoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($resEnfoque as $row) {
                    $lideres[] = [
                        'documento' => $row['documento'], 'nombre' => $row['nombre'], 'apellido' => $row['apellido'],
                        'correo' => $row['correo'], 'telefono' => $row['telefono'], 'tipo' => 'Vocero Enfoque',
                        'detalle' => "Población: " . $row['tipo_poblacion']
                    ];
                }
            }

            // 3. Representantes de Jornada
            if ($filtro === 'todos' || $filtro === 'representantes') {
                $sqlRep = "SELECT r.jornada, a.documento, a.nombre, a.apellido, a.correo, a.telefono, a.numero_ficha
                           FROM representantes_jornada r
                           JOIN aprendices a ON r.documento = a.documento";
                $stmt = $conn->query($sqlRep);
                $resRep = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($resRep as $row) {
                    $lideres[] = [
                        'documento' => $row['documento'], 'nombre' => $row['nombre'], 'apellido' => $row['apellido'],
                        'correo' => $row['correo'], 'telefono' => $row['telefono'], 'tipo' => 'Representante',
                        'detalle' => "Jornada: " . $row['jornada']
                    ];
                }
            }

            echo json_encode(['success' => true, 'data' => $lideres]);
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
            $stmt->execute([':user' => $data['id_usuario'], ':area' => $data['area'] ?? 'voceros_y_representantes']);
            
            echo json_encode(['success' => true, 'message' => 'Responsable asignado']);
            exit;
        }

        // Crear reunión de Bienestar
        if ($action === 'crearReunion') {
            $sql = "INSERT INTO bienestar_reuniones (titulo, descripcion, fecha, lugar, tipo_convocatoria, creado_por) 
                    VALUES (:titulo, :desc, :fecha, :lugar, :tipo, :user)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':titulo' => $data['titulo'],
                ':desc' => $data['descripcion'],
                ':fecha' => $data['fecha'],
                ':lugar' => $data['lugar'],
                ':tipo' => $data['tipo_convocatoria'],
                ':user' => $data['id_usuario']
            ]);
            
            $idReunion = $conn->lastInsertId();
            
            // Aquí se podría disparar la lógica de mensajería masiva automáticamente
            
            echo json_encode(['success' => true, 'message' => 'Reunión creada', 'id' => $idReunion]);
            exit;
        }

        // Registrar asistencia (Biométrica o Manual)
        if ($action === 'registrarAsistencia') {
            $id_reunion = $data['id_reunion'];
            $id_aprendiz = $data['id_aprendiz'];
            $estado = $data['estado'] ?? 'asistio';
            
            // Verificar si ya existe el registro
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

            // LÓGICA DE PÉRDIDA DE ROL POR 3 INASISTENCIAS
            if ($estado === 'ausente') {
                verificarPerdidaRol($id_aprendiz, $conn);
            }

            echo json_encode(['success' => true, 'message' => 'Asistencia registrada']);
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

    if ($fallas >= 3) {
        // REVOCAR ROLES
        // 1. En Fichas (Voceros)
        $conn->prepare("UPDATE fichas SET vocero_principal = NULL WHERE vocero_principal = :doc")->execute([':doc' => $documento]);
        $conn->prepare("UPDATE fichas SET vocero_suplente = NULL WHERE vocero_suplente = :doc")->execute([':doc' => $documento]);
        
        // 2. En Enfoque Diferencial
        $conn->prepare("UPDATE voceros_enfoque SET documento = NULL WHERE documento = :doc")->execute([':doc' => $documento]);
        
        // 3. En Representantes de Jornada
        $conn->prepare("DELETE FROM representantes_jornada WHERE documento = :doc")->execute([':doc' => $documento]);
        
        return true;
    }
    return false;
}
?>
