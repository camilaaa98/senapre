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

        try {
            $nuevoDocumento = !empty($data['documento']) && $data['documento'] != $id ? $data['documento'] : null;

            // ----- CAMBIO DE DOCUMENTO (id_usuario) -----
            // PostgreSQL no permite UPDATE directo de PK con FKs, usamos clonar+borrar
            if ($nuevoDocumento) {
                // Verificar que el nuevo documento no exista
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario = :new_id");
                $checkStmt->execute([':new_id' => $nuevoDocumento]);
                if ($checkStmt->fetchColumn() > 0) {
                    throw new Exception("El documento $nuevoDocumento ya existe asignado a otro usuario.");
                }

                // Paso 1: renombrar correo temporalmente para liberar UNIQUE
                $conn->prepare("UPDATE usuarios SET correo = correo || '_tmp_$id' WHERE id_usuario = :id")
                     ->execute([':id' => $id]);

                // Paso 2: clonar con nuevo ID y datos actualizados
                $passwordInsert = !empty($data['password'])
                    ? password_hash($data['password'], PASSWORD_DEFAULT)
                    : null;
                $cloneStmt = $conn->prepare(
                    "INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado, creado_en)
                     SELECT :new_id, :nombre, :apellido, :correo,
                            COALESCE(:password_hash, password_hash), rol, :estado, creado_en
                     FROM usuarios WHERE id_usuario = :old_id"
                );
                $cloneStmt->execute([
                    ':new_id'       => $nuevoDocumento,
                    ':nombre'       => $data['nombre'],
                    ':apellido'     => $data['apellido'],
                    ':correo'       => $data['correo'],
                    ':password_hash'=> $passwordInsert,
                    ':estado'       => $data['estado'],
                    ':old_id'       => $id
                ]);

                // Paso 3: actualizar FKs en todas las tablas relacionadas
                foreach ([
                    'instructores'            => 'id_usuario',
                    'administracion'          => 'id_usuario',
                    'administrador'           => 'id_usuario',
                    'asignacion_instructores' => 'id_usuario',
                    'horarios_formacion'      => 'id_instructor',
                    'asistencias'             => 'id_instructor',
                    'logs'                    => 'id_usuario',
                ] as $tabla => $col) {
                    try {
                        $conn->prepare("UPDATE $tabla SET $col = :new_id WHERE $col = :old_id")
                             ->execute([':new_id' => $nuevoDocumento, ':old_id' => $id]);
                    } catch (Exception $e2) { /* tabla puede no tener esa FK */ }
                }
                // biometria_usuarios usa TEXT
                try {
                    $conn->prepare("UPDATE biometria_usuarios SET id_usuario = :new_id WHERE id_usuario = :old_id")
                         ->execute([':new_id' => (string)$nuevoDocumento, ':old_id' => (string)$id]);
                } catch (Exception $e2) {}

                // Paso 4: borrar registro viejo
                $conn->prepare("DELETE FROM usuarios WHERE id_usuario = :old_id")->execute([':old_id' => $id]);

                $effectiveId = $nuevoDocumento;

            } else {
                // Sin cambio de documento: UPDATE normal
                $updateFields = ['nombre = :nombre', 'apellido = :apellido', 'correo = :correo', 'estado = :estado'];
                $params = [':nombre' => $data['nombre'], ':apellido' => $data['apellido'],
                           ':correo' => $data['correo'], ':estado' => $data['estado'], ':id' => $id];
                if (!empty($data['password'])) {
                    $updateFields[] = 'password_hash = :password';
                    $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                $sql = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id_usuario = :id";
                $conn->prepare($sql)->execute($params);
                $effectiveId = $id;
            }

            // ----- ACTUALIZAR TABLA SATÉLITE (instructores / administrador) -----
            $rolStmt = $conn->prepare("SELECT rol FROM usuarios WHERE id_usuario = :id");
            $rolStmt->execute([':id' => $effectiveId]);
            $currentRol = $rolStmt->fetch()['rol'];
            $telefono = $data['celular'] ?? null;

            if ($currentRol === 'instructor') {
                $conn->prepare("UPDATE instructores SET nombres=:nombre, apellidos=:apellido,
                    correo=:correo, telefono=:telefono, estado=:estado WHERE id_usuario=:id")
                     ->execute([':nombre'=>$data['nombre'],':apellido'=>$data['apellido'],
                                ':correo'=>$data['correo'],':telefono'=>$telefono,
                                ':estado'=>$data['estado'],':id'=>$effectiveId]);
            } elseif (in_array($currentRol, ['director','administrativo','coordinador','admin','administrador'])) {
                try {
                    $conn->prepare("UPDATE administrador SET nombres=:nombre, apellidos=:apellido,
                        correo=:correo, estado=:estado WHERE id_usuario=:id")
                         ->execute([':nombre'=>$data['nombre'],':apellido'=>$data['apellido'],
                                    ':correo'=>$data['correo'],':estado'=>$data['estado'],
                                    ':id'=>$effectiveId]);
                } catch (Exception $e2) {}
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'nuevo_id' => $effectiveId
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
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
