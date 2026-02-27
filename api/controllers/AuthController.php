<?php
/**
 * Authentication Controller
 * Handles user authentication and session management
 */
class AuthController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($data) {
        try {
            if (empty($data['correo']) || empty($data['password'])) {
                return $this->response(400, 'Correo y contraseña son requeridos');
            }
            
            // Buscar usuario por correo
            $sql = "SELECT * FROM usuarios WHERE correo = :correo";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':correo' => $data['correo']]);
            
            $usuario = $stmt->fetch();

            if (!$usuario) {
                return $this->response(401, 'Credenciales inválidas');
            }

            // Validar estado
            $estadoValido = ($usuario['estado'] == 1 || $usuario['estado'] === '1' || strtolower($usuario['estado']) === 'activo');

            if (!$estadoValido) {
                return $this->response(401, 'Usuario inactivo');
            }

            // Verificar contraseña
            $passwordValido = password_verify($data['password'], $usuario['password_hash']) || 
                             ($data['password'] === $usuario['password_hash']);

            if (!$passwordValido) {
                return $this->response(401, 'Credenciales inválidas');
            }
            
            // Si es instructor o administrador, obtener datos básicos
            $instructorData = null;
            if ($usuario['rol'] === 'instructor') {
                $sqlInstructor = "SELECT * FROM instructores WHERE id_usuario = :id";
                $stmtInstructor = $this->conn->prepare($sqlInstructor);
                $stmtInstructor->execute([':id' => $usuario['id_usuario']]);
                $instructorData = $stmtInstructor->fetch();
            }

            // Obtener áreas de responsabilidad en Bienestar (para administrativos)
            $bienestarData = [];
            if (in_array($usuario['rol'], ['director', 'administrativo', 'coordinador', 'admin', 'administrador'])) {
                $sqlBienestar = "SELECT area FROM area_responsables WHERE id_usuario = :id";
                $stmtBienestar = $this->conn->prepare($sqlBienestar);
                $stmtBienestar->execute([':id' => $usuario['id_usuario']]);
                $bienestarData = $stmtBienestar->fetchAll(PDO::FETCH_COLUMN);
            }

            // Datos específicos si es VOCERO
            $voceroScopes = [];
            if ($usuario['rol'] === 'vocero') {
                $doc = $usuario['id_usuario'];
                
                // 1. ¿Es vocero principal?
                $stmt = $this->conn->prepare("SELECT numero_ficha FROM fichas WHERE vocero_principal = :doc");
                $stmt->execute([':doc' => $doc]);
                $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($f as $row) {
                    $voceroScopes[] = ['tipo' => 'principal', 'ficha' => $row['numero_ficha']];
                }

                // 2. ¿Es vocero suplente?
                $stmt = $this->conn->prepare("SELECT numero_ficha FROM fichas WHERE vocero_suplente = :doc");
                $stmt->execute([':doc' => $doc]);
                $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($f as $row) {
                    $voceroScopes[] = ['tipo' => 'suplente', 'ficha' => $row['numero_ficha']];
                }

                // 3. ¿Es vocero de enfoque?
                $stmt = $this->conn->prepare("SELECT tipo_poblacion FROM voceros_enfoque WHERE documento = :doc");
                $stmt->execute([':doc' => $doc]);
                $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($f as $row) {
                    $voceroScopes[] = ['tipo' => 'enfoque', 'poblacion' => $row['tipo_poblacion']];
                }

                // 4. ¿Es representante?
                $stmt = $this->conn->prepare("SELECT jornada FROM representantes_jornada WHERE documento = :doc");
                $stmt->execute([':doc' => $doc]);
                $f = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($f as $row) {
                    $voceroScopes[] = ['tipo' => 'representante', 'jornada' => $row['jornada']];
                }
            }

            // Registrar login en logs (BD)
            $this->logAction($usuario['id_usuario'], 'Inicio de sesión');

            // Preparar respuesta
            $response = [
                'id_usuario' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'correo' => $usuario['correo'],
                'rol' => $usuario['rol'],
                'instructor_data' => $instructorData,
                'bienestar_data' => $bienestarData,
                'vocero_scope' => !empty($voceroScopes) ? $voceroScopes[0] : null,
                'vocero_scopes' => $voceroScopes
            ];

            return $this->response(200, 'Login exitoso', $response);

        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return $this->response(500, 'Error en el servidor');
        }
    }

    private function logAction($userId, $action) {
        try {
            $sql = "INSERT INTO logs (id_usuario, accion, fecha) VALUES (:id, :accion, datetime('now', 'localtime'))";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id' => $userId,
                ':accion' => $action
            ]);
        } catch (Exception $e) {
            error_log("Log Error (Non-fatal): " . $e->getMessage());
        }
    }

    private function response($code, $message, $data = null) {
        http_response_code($code);
        $response = [
            'success' => ($code >= 200 && $code < 300),
            'message' => $message
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return json_encode($response);
    }
}
?>
