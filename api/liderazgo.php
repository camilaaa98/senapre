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
            if ($filtro === 'todos' || $filtro === 'principales' || $filtro === 'voceros') {
                $sqlPrincipales = "SELECT a.*, f.numero_ficha, f.jornada, a.tipo_poblacion as pob_a 
                                 FROM fichas f
                                 LEFT JOIN aprendices a ON TRIM(CAST(f.vocero_principal AS TEXT)) = TRIM(CAST(a.documento AS TEXT))
                                 WHERE f.vocero_principal IS NOT NULL AND f.vocero_principal != ''
                                 ORDER BY f.numero_ficha ASC";
                try {
                    $stmt = $conn->query($sqlPrincipales);
                    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($res as $row) {
                        $lideres[] = [
                            'documento' => $row['documento'], 'nombre' => $row['nombre'] ?? 'Sin Registro', 'apellido' => $row['apellido'] ?? '',
                            'correo' => $row['correo'] ?? 'No disponible', 'telefono' => $row['celular'] ?? 'N/A', 'tipo' => 'Vocero Principal',
                            'numero_ficha' => $row['numero_ficha'],
                            'estado' => $row['estado'] ?? 'DESCONOCIDO',
                            'poblacion' => $row['pob_a'] ?? 'Ninguna',
                            'jornada' => $row['jornada'] ?? 'N/A'
                        ];
                    }
                } catch(Exception $e) {
                    // Ignorar si la columna aún no existe
                }
            }

            // 1.5 Voceros Suplentes de Fichas
            if ($filtro === 'todos' || $filtro === 'suplentes') {
                $sqlSuplentes = "SELECT a.*, f.numero_ficha, f.jornada, a.tipo_poblacion as pob_a 
                                FROM fichas f
                                LEFT JOIN aprendices a ON TRIM(CAST(f.vocero_suplente AS TEXT)) = TRIM(CAST(a.documento AS TEXT))
                                WHERE f.vocero_suplente IS NOT NULL AND f.vocero_suplente != ''
                                ORDER BY f.numero_ficha ASC";
                try {
                    $stmt = $conn->query($sqlSuplentes);
                    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($res as $row) {
                        $lideres[] = [
                            'documento' => $row['documento'], 'nombre' => $row['nombre'] ?? 'Sin Registro', 'apellido' => $row['apellido'] ?? '',
                            'correo' => $row['correo'] ?? 'No disponible', 'telefono' => $row['celular'] ?? 'N/A', 'tipo' => 'Vocero Suplente',
                            'numero_ficha' => $row['numero_ficha'],
                            'estado' => $row['estado'] ?? 'DESCONOCIDO',
                            'poblacion' => $row['pob_a'] ?? 'Ninguna',
                            'jornada' => $row['jornada'] ?? 'N/A'
                        ];
                    }
                } catch (Exception $e) {}
            }

            // 2. Voceros de Enfoque Diferencial
            if ($filtro === 'todos' || $filtro === 'enfoque') {
                $sqlEnfoque = "SELECT a.*, v.tipo_poblacion as detalle, a.tipo_poblacion as pob_a 
                             FROM voceros_enfoque v
                             LEFT JOIN aprendices a ON TRIM(CAST(v.documento AS TEXT)) = TRIM(CAST(a.documento AS TEXT))
                             WHERE v.documento IS NOT NULL AND v.documento != ''";
                try {
                    $stmt = $conn->query($sqlEnfoque);
                    $resEnfoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($resEnfoque as $row) {
                        $lideres[] = [
                            'documento' => $row['documento'], 'nombre' => $row['nombre'] ?? 'Sin Registro', 'apellido' => $row['apellido'] ?? '',
                            'correo' => $row['correo'] ?? 'No disponible', 'telefono' => $row['celular'] ?? 'N/A', 'tipo' => 'Vocero Enfoque ' . $row['detalle'],
                            'numero_ficha' => $row['numero_ficha'] ?? 'N/A',
                            'estado' => $row['estado'] ?? 'DESCONOCIDO',
                            'poblacion' => $row['pob_a'] ?? $row['detalle'],
                            'detalle' => $row['detalle']
                        ];
                    }
                } catch (Exception $e) {}
            }

            // 3. Representantes por Ficha (DIURNA / MIXTA)
            if ($filtro === 'todos' || $filtro === 'representantes' || $filtro === 'representante') {
                $sqlRep = "SELECT a.*, f.numero_ficha, f.jornada 
                          FROM fichas f
                          LEFT JOIN aprendices a ON TRIM(CAST(f.vocero_representante AS TEXT)) = TRIM(CAST(a.documento AS TEXT))
                          WHERE f.vocero_representante IS NOT NULL AND f.vocero_representante != ''
                          ORDER BY f.numero_ficha ASC";
                try {
                    $stmt = $conn->query($sqlRep);
                    $resRep = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($resRep as $row) {
                        $jOrig = strtoupper($row['jornada'] ?? '');
                        $jRep = ($jOrig === 'DIURNA') ? 'DIURNA' : 'MIXTA';
                        
                        $lideres[] = [
                            'documento' => $row['documento'], 'nombre' => $row['nombre'] ?? 'Sin Registro', 'apellido' => $row['apellido'] ?? '',
                            'correo' => $row['correo'] ?? 'No disponible', 'telefono' => $row['celular'] ?? 'N/A', 'tipo' => 'Representante',
                            'numero_ficha' => $row['numero_ficha'],
                            'estado' => $row['estado'] ?? 'DESCONOCIDO',
                            'poblacion' => $row['tipo_poblacion'] ?? 'Ninguna',
                            'detalle' => $jRep,
                            'jornada' => $row['jornada'] ?? 'N/A'
                        ];
                    }
                } catch (Exception $e) {}
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
        
        // Estadísticas de Población (Mejorado con patrones completos)
        if ($action === 'getPoblacionStats') {
            $stats = [];
            
            // Patrones mejorados para cada categoría
            $categorias = [
                'mujer' => ['%mujer%', '%mujeres%', '%femenino%', '%femenina%', '%F%', '%muj%'],
                'indigena' => ['%indigena%', '%indígena%', '%etnia%', '%pueblos%', '%indígenas%', '%etnico%'],
                'narp' => ['%narp%', '%negro%', '%afro%', '%afrodescendiente%', '%raizal%', '%palenquero%', '%afro%'],
                'campesino' => ['%campesino%', '%campesina%', '%rural%', '%campo%', '%camp%'],
                'lgbtiq' => ['%lgbti%', '%lgbt%', '%trans%', '%gay%', '%lesbiana%', '%bisexual%', '%queer%', '%homosexual%', '+'],
                'discapacidad' => ['%discapacidad%', '%discapacitado%', '%discapacitada%', '%capacidad%', '%disc%']
            ];
            
            foreach ($categorias as $cat => $patterns) {
                // Construir consulta con múltiples patrones LIKE
                $sql = "SELECT COUNT(*) as total FROM aprendices WHERE UPPER(estado) = 'LECTIVA' AND (";
                $likeConditions = [];
                foreach ($patterns as $pattern) {
                    $likeConditions[] = "UPPER(tipo_poblacion) LIKE UPPER('" . $pattern . "')";
                }
                $sql .= implode(" OR ", $likeConditions) . ")";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $stats[$cat] = $stmt->fetch()['total'];
            }
            
            // También obtener voceros de enfoque
            $voceros = [];
            $sqlV = "SELECT v.tipo_poblacion as cat, u.nombre, u.apellido 
                     FROM voceros_enfoque v
                     JOIN aprendices u ON TRIM(CAST(v.documento AS TEXT)) = TRIM(CAST(u.documento AS TEXT))";
            try {
                $stmtV = $conn->query($sqlV);
                $resV = $stmtV->fetchAll(PDO::FETCH_ASSOC);
                foreach ($resV as $v) {
                    $voceros[strtolower($v['cat'])] = $v['nombre'] . ' ' . $v['apellido'];
                }
            } catch(Exception $e) {}

            echo json_encode(['success' => true, 'counts' => $stats, 'voceros' => $voceros]);
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

        // Obtener fichas activas para asignación
        if ($action === 'getFichasActivas') {
            $sql = "SELECT numero_ficha, nombre_programa, jornada FROM fichas WHERE UPPER(COALESCE(estado, 'ACTIVO')) IN ('ACTIVO', 'FORMACION', 'EN FORMACION', 'LECTIVA') OR estado = '' ORDER BY numero_ficha DESC";
            $stmt = $conn->query($sql);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        // Obtener aprendices en LECTIVA con filtros de población (patrones mejorados)
        if ($action === 'getAprendicesLectiva') {
            $categoria = $_GET['categoria'] ?? '';
            
            // SQL base compatible con producción
            $sql = "SELECT documento, nombre, apellido, numero_ficha, tipo_poblacion, correo, celular 
                     FROM aprendices a 
                     WHERE UPPER(a.estado) = 'LECTIVA'";
            
            // Patrones mejorados para cada categoría
            $patrones = [
                'mujer' => ['%mujer%', '%mujeres%', '%femenino%', '%femenina%', '%F%', '%muj%'],
                'indigena' => ['%indigena%', '%indígena%', '%etnia%', '%pueblos%', '%indígenas%', '%etnico%'],
                'narp' => ['%narp%', '%negro%', '%afro%', '%afrodescendiente%', '%raizal%', '%palenquero%', '%afro%'],
                'campesino' => ['%campesino%', '%campesina%', '%rural%', '%campo%', '%camp%'],
                'lgbtiq' => ['%lgbti%', '%lgbt%', '%trans%', '%gay%', '%lesbiana%', '%bisexual%', '%queer%', '%homosexual%', '+'],
                'discapacidad' => ['%discapacidad%', '%discapacitado%', '%discapacitada%', '%capacidad%', '%disc%']
            ];
            
            // Aplicar filtros usando patrones mejorados
            if (isset($patrones[$categoria])) {
                $patterns = $patrones[$categoria];
                $sql .= " AND (";
                $likeConditions = [];
                foreach ($patterns as $pattern) {
                    $likeConditions[] = "UPPER(a.tipo_poblacion) LIKE UPPER('" . $pattern . "')";
                }
                $sql .= implode(" OR ", $likeConditions) . ")";
            }
            
            // Ordenar y limitar para mejor rendimiento
            $sql .= " ORDER BY a.nombre, a.apellido LIMIT 50";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $aprendices]);
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

        // Obtener representantes
        if ($action === 'getRepresentantes') {
            $sql = "SELECT r.tipo_jornada, a.documento, a.nombre, a.apellido 
                     FROM representantes r
                     JOIN aprendices a ON r.documento = a.documento
                     ORDER BY r.tipo_jornada";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $representantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $representantes]);
            exit;
        }

        // Guardar vocero de enfoque diferencial
        if ($action === 'saveVoceroEnfoque') {
            $categoria = $data['categoria'] ?? '';
            $documento = $data['documento'] ?? '';
            
            if (empty($categoria) || empty($documento)) {
                throw new Exception('Datos incompletos');
            }
            
            // Verificar si ya existe un vocero para esa categoría
            $sqlCheck = "SELECT id FROM voceros_enfoque WHERE tipo_poblacion = :cat";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->execute([':cat' => $categoria]);
            $existing = $stmtCheck->fetch();
            
            if ($existing) {
                // Actualizar existente
                $sql = "UPDATE voceros_enfoque SET documento = :doc WHERE tipo_poblacion = :cat";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':doc' => $documento, ':cat' => $categoria]);
            } else {
                // Insertar nuevo
                $sql = "INSERT INTO voceros_enfoque (tipo_poblacion, documento) VALUES (:cat, :doc)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':cat' => $categoria, ':doc' => $documento]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Vocero de enfoque asignado correctamente']);
            exit;
        }

        // Guardar representante (diurna/mixta)
        if ($action === 'saveRepresentante') {
            $tipo_jornada = $data['tipo_jornada'] ?? ''; // 'diurna' o 'mixta'
            $documento = $data['documento'] ?? '';
            
            if (empty($tipo_jornada) || empty($documento)) {
                throw new Exception('Datos incompletos');
            }
            
            // Verificar si ya existe un representante para ese tipo
            $sqlCheck = "SELECT id FROM representantes WHERE tipo_jornada = :tipo";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->execute([':tipo' => $tipo_jornada]);
            $existing = $stmtCheck->fetch();
            
            if ($existing) {
                // Actualizar existente
                $sql = "UPDATE representantes SET documento = :doc, fecha_asignacion = CURRENT_DATE WHERE tipo_jornada = :tipo";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':doc' => $documento, ':tipo' => $tipo_jornada]);
            } else {
                // Insertar nuevo
                $sql = "INSERT INTO representantes (documento, tipo_jornada, fecha_asignacion) VALUES (:doc, :tipo, CURRENT_DATE)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':doc' => $documento, ':tipo' => $tipo_jornada]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Representante asignado correctamente']);
            exit;
        }

        // Eliminar de población
        if ($action === 'eliminarDePoblacion') {
            $documento = $data['documento'] ?? '';
            $categoria = $data['categoria'] ?? '';
            
            if (empty($documento) || empty($categoria)) {
                throw new Exception('Datos incompletos');
            }
            
            // Obtener el tipo_poblacion actual
            $sqlGet = "SELECT tipo_poblacion FROM aprendices WHERE documento = :doc";
            $stmtGet = $conn->prepare($sqlGet);
            $stmtGet->execute([':doc' => $documento]);
            $aprendiz = $stmtGet->fetch();
            
            if ($aprendiz && $aprendiz['tipo_poblacion']) {
                // Eliminar la categoría específica del tipo_poblacion
                $tipoPoblacion = $aprendiz['tipo_poblacion'];
                $patron = '';
                
                switch ($categoria) {
                    case 'mujer':
                        $patron = 'mujer|mujeres|femenino|femenina|F|muj';
                        break;
                    case 'indigena':
                        $patron = 'indigena|indígena|etnia|pueblos|indígenas|etnico';
                        break;
                    case 'narp':
                        $patron = 'narp|negro|afro|afrodescendiente|raizal|palenquero';
                        break;
                    case 'campesino':
                        $patron = 'campesino|campesina|rural|campo|camp';
                        break;
                    case 'lgbtiq':
                        $patron = 'lgbti|lgbt|trans|gay|lesbiana|bisexual|queer|homosexual|\\+';
                        break;
                    case 'discapacidad':
                        $patron = 'discapacidad|discapacitado|discapacitada|capacidad|disc';
                        break;
                }
                
                if ($patron) {
                    // Eliminar patrones específicos
                    $nuevoTipo = preg_replace("/\b($patron)\b/i", '', $tipoPoblacion);
                    $nuevoTipo = preg_replace('/,\s*,/', ',', $nuevoTipo); // Eliminar comas dobles
                    $nuevoTipo = trim($nuevoTipo, ', ');
                    
                    $sql = "UPDATE aprendices SET tipo_poblacion = :nuevoTipo WHERE documento = :doc";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':nuevoTipo' => $nuevoTipo, ':doc' => $documento]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Eliminado de la categoría correctamente']);
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
                ':c' => $data['correo'],
                ':t' => $data['telefono'],
                ':d' => $data['documento']
            ]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'eliminarRol') {
            if (empty($data['documento']) || empty($data['tipo'])) throw new Exception('Datos insuficientes');
            $doc = $data['documento'];
            $tipo = $data['tipo'];
            
            $sql = "";
            if ($tipo === 'Vocero Principal') $sql = "UPDATE fichas SET vocero_principal = NULL WHERE vocero_principal = :doc";
            else if ($tipo === 'Vocero Suplente') $sql = "UPDATE fichas SET vocero_suplente = NULL WHERE vocero_suplente = :doc";
            else if ($tipo === 'Vocero Enfoque') $sql = "DELETE FROM voceros_enfoque WHERE documento = :doc";
            else if ($tipo === 'Representante') $sql = "DELETE FROM representantes_jornada WHERE documento = :doc";
            
            if (!$sql) throw new Exception('Tipo de rol inválido: ' . $tipo);
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([':doc' => $doc]);
            echo json_encode(['success' => true]);
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

        // Asignación de Roles / Voceros
        if ($action === 'asignarRol') {
            if (empty($data['documento']) || empty($data['tipo_rol'])) throw new Exception('Datos incompletos para asignación');
            $doc = $data['documento'];
            $tipo = $data['tipo_rol']; // principal, suplente, enfoque, representante
            // Verificar que el documento exista en la tabla aprendices
            $stmtCheck = $conn->prepare("SELECT documento FROM aprendices WHERE documento = :doc");
            $stmtCheck->execute([':doc' => $doc]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('Documento no encontrado en aprendices');
            }
            if ($tipo === 'principal' || $tipo === 'suplente' || $tipo === 'representante') {
                if(empty($data['numero_ficha'])) throw new Exception('Ficha requerida');
                $ficha = $data['numero_ficha'];
                
                $columna = 'vocero_principal';
                if ($tipo === 'suplente') $columna = 'vocero_suplente';
                if ($tipo === 'representante') $columna = 'vocero_representante';

                $sql = "UPDATE fichas SET $columna = :doc WHERE numero_ficha = :ficha";
                
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':doc' => $doc, ':ficha' => $ficha]);
                } catch(Exception $e) {
                    // Asegurar que las columnas existen (Auto-fix schema)
                    try { $conn->exec("ALTER TABLE fichas ADD COLUMN vocero_principal TEXT"); } catch(Exception $ex) {}
                    try { $conn->exec("ALTER TABLE fichas ADD COLUMN vocero_suplente TEXT"); } catch(Exception $ex) {}
                    try { $conn->exec("ALTER TABLE fichas ADD COLUMN vocero_representante TEXT"); } catch(Exception $ex) {}
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':doc' => $doc, ':ficha' => $ficha]);
                }
                echo json_encode(['success' => true, 'message' => "Rol $tipo asignado a la ficha $ficha"]);
                exit;
            }

            if ($tipo === 'enfoque') {
                if(empty($data['categoria'])) throw new Exception('Categoría requerida');
                $cat = $data['categoria'];
                // Check if exists
                $chk = $conn->prepare("SELECT documento FROM voceros_enfoque WHERE tipo_poblacion = :c");
                $chk->execute([':c' => $cat]);
                if($chk->fetch()) {
                    $conn->prepare("UPDATE voceros_enfoque SET documento = :d WHERE tipo_poblacion = :c")->execute([':d'=>$doc, ':c'=>$cat]);
                } else {
                    $conn->prepare("INSERT INTO voceros_enfoque (tipo_poblacion, documento) VALUES (:c, :d)")->execute([':c'=>$cat, ':d'=>$doc]);
                }
                echo json_encode(['success' => true, 'message' => "Vocero diferencial asignado"]);
                exit;
            }
        }
        
        // Agregar aprendiz a una población
        if ($action === 'addPoblacion') {
            if (empty($data['documento']) || empty($data['tipo_poblacion'])) throw new Exception('Datos incompletos');
            $doc = $data['documento'];
            $pob_text = $data['tipo_poblacion'];
            
            // Se actualiza en la tabla de aprendices
            $sql = "UPDATE aprendices SET tipo_poblacion = :pob WHERE documento = :doc";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':pob' => $pob_text, ':doc' => $doc]);
            
            // También tratamos de marcarlo en la tabla vieja correspondiente si existe
            $pob_lower = strtolower($pob_text);
            if (in_array($pob_lower, ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'])) {
                try {
                    $conn->prepare("INSERT INTO $pob_lower (documento) VALUES (:doc)")->execute([':doc' => $doc]);
                } catch(Exception $e) {} // Ignorar si ya existe
            }
            
            echo json_encode(['success' => true, 'message' => "Aprendiz asignado a $pob_text"]);
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
