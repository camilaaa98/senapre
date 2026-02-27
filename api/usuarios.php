<?php
/**
 * Usuarios API - CRUD con tablas separadas para instructores y administradores
 * Crea automáticamente login en tabla usuarios
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Listar usuarios con paginación y filtros
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $rol = isset($_GET['rol']) ? $_GET['rol'] : '';
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        
        // Construir query con filtros - uniendo instructores y administradores
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(u.nombre LIKE :search OR u.apellido LIKE :search OR u.correo LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($rol)) {
            if (strpos($rol, ',') !== false) {
                $roles = explode(',', $rol);
                $placeholders = [];
                foreach ($roles as $i => $r) {
                    $key = ":rol$i";
                    $placeholders[] = $key;
                    $params[$key] = strtolower(trim($r));
                }
                $where[] = "LOWER(u.rol) IN (" . implode(',', $placeholders) . ")";
            } else {
                $where[] = "LOWER(u.rol) = :rol";
                $params[':rol'] = strtolower($rol);
            }
        }
        
        if (!empty($estado)) {
            $where[] = "u.estado = :estado";
            $params[':estado'] = $estado;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM usuarios u $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Obtener datos paginados
        // Obtener datos paginados o todos con JOIN para traer info específica y teléfono
        $selectFields = "u.id_usuario, u.rol, 
                         COALESCE(a.nombres, i.nombres, u.nombre) as nombre,
                         COALESCE(a.apellidos, i.apellidos, u.apellido) as apellido,
                         COALESCE(a.correo, i.correo, u.correo) as correo,
                         COALESCE(a.estado, i.estado, u.estado) as estado,
                         COALESCE(a.telefono, i.telefono) as telefono";
        
        $joins = "LEFT JOIN administrador a ON u.id_usuario = a.id_usuario
                  LEFT JOIN instructores i ON u.id_usuario = i.id_usuario";

        if ($limit === -1) {
            $sql = "SELECT $selectFields FROM usuarios u $joins $whereClause ORDER BY u.apellido, u.nombre";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        } else {
            $sql = "SELECT $selectFields FROM usuarios u $joins $whereClause ORDER BY u.apellido, u.nombre LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $usuarios = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $usuarios,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ($limit === -1) ? 1 : ceil($total / $limit)
            ]
        ]);
        exit;
    }
    
    // POST - Crear usuario
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos requeridos
        if (empty($data['nombre']) || empty($data['apellido']) || empty($data['correo']) || empty($data['rol']) || empty($data['documento'])) {
            throw new Exception('Nombre, apellido, correo, rol y documento son requeridos');
        }
        
        // Usar documento como ID de usuario
        $id_usuario = $data['documento'];
        
        // Generar contraseña automática si no se proporciona
        $password = isset($data['password']) && !empty($data['password']) 
            ? $data['password'] 
            : substr(str_replace('@', '', $data['correo']), 0, 8); // Primeros 8 caracteres del correo
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $conn->beginTransaction();
        
        try {
            // 1. Crear login en tabla usuarios
            $sqlUsuario = "INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                          VALUES (:id_usuario, :nombre, :apellido, :correo, :password, :rol, :estado)";
            
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->execute([
                ':id_usuario' => $id_usuario,
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':correo' => $data['correo'],
                ':password' => $passwordHash,
                ':rol' => $data['rol'],
                ':estado' => $data['estado'] ?? 'activo'
            ]);
            
            // 2. Insertar en tabla específica según rol
            if ($data['rol'] === 'instructor') {
                // Insertar en tabla instructores (usando nombres de columnas correctos)
                $sqlInstructor = "INSERT INTO instructores (id_usuario, nombres, apellidos, correo, telefono, estado)
                                 VALUES (:id_usuario, :nombre, :apellido, :correo, :celular, :estado)";
                
                $stmtInstructor = $conn->prepare($sqlInstructor);
                $stmtInstructor->execute([
                    ':id_usuario' => $id_usuario,
                    ':nombre' => $data['nombre'],
                    ':apellido' => $data['apellido'],
                    ':correo' => $data['correo'],
                    ':celular' => $data['celular'] ?? null,
                    ':estado' => $data['estado'] ?? 'activo'
                ]);
            } elseif (in_array($data['rol'], ['director', 'administrativo', 'coordinador', 'admin', 'administrador'])) {
                // Verificar si tabla administrador existe, si no, crearla
                $conn->exec("CREATE TABLE IF NOT EXISTS administrador (
                    id_usuario INTEGER PRIMARY KEY,
                    nombres TEXT NOT NULL,
                    apellidos TEXT NOT NULL,
                    correo TEXT NOT NULL,
                    telefono TEXT,
                    estado TEXT DEFAULT 'activo',
                    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
                )");

                // Insertar en tabla administrador
                $sqlAdmin = "INSERT INTO administrador (id_usuario, nombres, apellidos, correo, telefono, estado)
                            VALUES (:id_usuario, :nombre, :apellido, :correo, :celular, :estado)";
                
                $stmtAdmin = $conn->prepare($sqlAdmin);
                $stmtAdmin->execute([
                    ':id_usuario' => $id_usuario,
                    ':nombre' => $data['nombre'],
                    ':apellido' => $data['apellido'],
                    ':correo' => $data['correo'],
                    ':celular' => $data['celular'] ?? null,
                    ':estado' => $data['estado'] ?? 'activo'
                ]);
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'id' => $id_usuario,
                'password_generado' => $password // Devolver contraseña generada
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
        exit;
    }
    
    // PUT - Actualizar usuario completo
    if ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($id)) {
            throw new Exception('ID de usuario requerido');
        }
        
        // Datos básicos requeridos
        if (empty($data['nombre']) || empty($data['apellido']) || empty($data['correo'])) {
            throw new Exception('Nombre, apellido y correo son requeridos');
        }

        $conn->beginTransaction();
        
        // Deshabilitar foreign keys temporalmente para permitir cambio de id_usuario
        $conn->exec('PRAGMA foreign_keys = OFF');
        
        try {
            // 1. Preparar query para tabla usuarios
            $updateFields = [
                'nombre = :nombre',
                'apellido = :apellido',
                'correo = :correo',
                'estado = :estado'
            ];
            
            $params = [
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':correo' => $data['correo'],
                ':estado' => $data['estado'],
                ':id' => $id
            ];

            // Si viene contraseña, agregarla
            if (!empty($data['password'])) {
                $updateFields[] = 'password_hash = :password';
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Si viene documento y es diferente al ID actual (Cambio de ID)
            if (!empty($data['documento']) && $data['documento'] != $id) {
                // Verificar si el nuevo ID ya existe
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = :new_id");
                $checkStmt->execute([':new_id' => $data['documento']]);
                if ($checkStmt->fetchColumn() > 0) {
                    throw new Exception("El documento " . $data['documento'] . " ya existe asignado a otro usuario.");
                }

                // Agregar el cambio de id_usuario a los campos a actualizar
                $updateFields[] = 'id_usuario = :new_id';
                $params[':new_id'] = $data['documento'];
            }

            // 1. PRIMERO: Actualizar tabla usuarios
            $sql = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id_usuario = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            // 2. DESPUÉS: Si hubo cambio de documento, actualizar tablas relacionadas
            if (!empty($data['documento']) && $data['documento'] != $id) {
                $nuevoId = $data['documento'];
                
                // Obtener rol actual
                $rolStmt = $conn->prepare("SELECT rol FROM usuarios WHERE id_usuario = :id");
                $rolStmt->execute([':id' => $nuevoId]);
                $currentRolData = $rolStmt->fetch();
                
                if ($currentRolData && $currentRolData['rol'] === 'instructor') {
                    // Actualizar instructores
                    $updateInst = $conn->prepare("UPDATE instructores SET id_usuario = :new_id WHERE id_usuario = :old_id");
                    $updateInst->execute([':new_id' => $nuevoId, ':old_id' => $id]);
                }
                
                if ($currentRolData && (in_array($currentRolData['rol'], ['director', 'administrativo', 'coordinador', 'admin', 'administrador']))) {
                    // Actualizar administrador si existe la tabla
                    $tableCheck = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='administrador'");
                    if ($tableCheck->fetch()) {
                        $updateAdmin = $conn->prepare("UPDATE administrador SET id_usuario = :new_id WHERE id_usuario = :old_id");
                        $updateAdmin->execute([':new_id' => $nuevoId, ':old_id' => $id]);
                    }
                }
                
                // Actualizar asignacion_instructores
                $updateAsig = $conn->prepare("UPDATE asignacion_instructores SET id_usuario = :new_id WHERE id_usuario = :old_id");
                $updateAsig->execute([':new_id' => $nuevoId, ':old_id' => $id]);
                
                // Actualizar biometria si existe
                $bioCheck = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='biometria'");
                if ($bioCheck->fetch()) {
                    $updateBio = $conn->prepare("UPDATE biometria SET id_usuario = :new_id WHERE id_usuario = :old_id AND tipo = 'usuario'");
                    $updateBio->execute([':new_id' => $nuevoId, ':old_id' => $id]);
                }
                
                // Usar el nuevo ID para las siguientes operaciones
                $effectiveId = $nuevoId;
            } else {
                $effectiveId = $id;
            }
            
            // Si cambiamos el ID, el :id para las siguientes consultas DEBE ser el nuevo ID?
            // NO, las siguientes consultas usan el ID para buscar el registro a actualizar en tablas satélite.
            // Si el ID de usuario cambió, el ID en tablas satélite DEBE haber cambiado también por ON UPDATE CASCADE.
            // Si no hay ON UPDATE CASCADE, fallaría arriba.
            // Asumimos que si pasó el execute arriba, el ID ya cambió.
            // Entonces para actualizar la tabla satélite, ¿qué ID usamos en el WHERE?
            // Si hubo CASCADE, usamos el NUEVO ID.
            // Si NO hubo CASCADE (y no falló porque no hay FKs?), usamos el VIEJO ID... espera, si no hay FKs no importa.
            // PERO instructores TIENE FK.
            // Si SQLite tiene activado FKs (PRAGMA foreign_keys=ON esta en Database.php), y fallaria si no hay CASCADE.
            // Si funcionó, usamos el NUEVO ID para buscar en `instructores`.
            
            $effectiveId = (!empty($data['documento']) && $data['documento'] != $id) ? $data['documento'] : $id;
            
            // Obtener rol para saber qué tabla satélite actualizar (usando el ID efectivo)
            $rolStmt = $conn->prepare("SELECT rol FROM usuarios WHERE id_usuario = :id");
            $rolStmt->execute([':id' => $effectiveId]);
            $currentRol = $rolStmt->fetch()['rol'];
            
            // Datos para tablas satélite (nombres en plural)
            $telefono = $data['celular'] ?? null;

            if ($currentRol === 'instructor') {
                $sqlInst = "UPDATE instructores SET 
                            nombres = :nombre,
                            apellidos = :apellido,
                            correo = :correo,
                            telefono = :telefono,
                            estado = :estado
                            WHERE id_usuario = :id";
                // Aquí el update de tabla instructores también depende de si ID cambió.
                // Si FK cascade funcionó, el registro en 'instructores' ya tiene el NUEVO ID.
                // Así que WHERE id_usuario = :new_id es correcto.
                
                $stmtInst = $conn->prepare($sqlInst);
                $stmtInst->execute([
                    ':nombre' => $data['nombre'],
                    ':apellido' => $data['apellido'],
                    ':correo' => $data['correo'],
                    ':telefono' => $telefono,
                    ':estado' => $data['estado'],
                    ':id' => $effectiveId
                ]);
            } elseif (in_array($currentRol, ['director', 'administrativo', 'coordinador', 'admin', 'administrador'])) {
                $sqlAdmin = "UPDATE administrador SET 
                            nombres = :nombre,
                            apellidos = :apellido,
                            correo = :correo,
                            telefono = :telefono,
                            estado = :estado
                            WHERE id_usuario = :id";
                $stmtAdmin = $conn->prepare($sqlAdmin);
                $stmtAdmin->execute([
                    ':nombre' => $data['nombre'],
                    ':apellido' => $data['apellido'],
                    ':correo' => $data['correo'],
                    ':telefono' => $telefono,
                    ':estado' => $data['estado'],
                    ':id' => $effectiveId
                ]);
            }
            
            $conn->commit();
            
            // Rehabilitar foreign keys
            $conn->exec('PRAGMA foreign_keys = ON');
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario actualizado correctamente'
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            
            // Rehabilitar foreign keys incluso en caso de error
            $conn->exec('PRAGMA foreign_keys = ON');
            
            throw $e;
        }
        
        exit;
    }
    
    // DELETE - Eliminar usuario
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($id)) {
            throw new Exception('ID de usuario requerido');
        }
        
        $conn->beginTransaction();
        
        try {
            // Obtener rol antes de eliminar
            $rolStmt = $conn->prepare("SELECT rol FROM usuarios WHERE id_usuario = :id");
            $rolStmt->execute([':id' => $id]);
            $usuario = $rolStmt->fetch();
            
            if ($usuario) {
                // Eliminar de tabla específica primero
                if ($usuario['rol'] === 'instructor') {
                    $stmt = $conn->prepare("DELETE FROM instructores WHERE id_usuario = :id");
                    $stmt->execute([':id' => $id]);
                } elseif (in_array($usuario['rol'], ['director', 'administrativo', 'coordinador', 'admin', 'administrador'])) {
                    $stmt = $conn->prepare("DELETE FROM administrador WHERE id_usuario = :id");
                    $stmt->execute([':id' => $id]);
                }
                
                // Eliminar de tabla usuarios
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
                $stmt->execute([':id' => $id]);
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
