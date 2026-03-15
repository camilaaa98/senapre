<?php
/**
 * Database - Conexión PostgreSQL para Render/Supabase
 * SENAPRE - Sistema de Asistencias SENA
 */
class Database {
    private static $instance = null;
    private $conn = null;

    private function __construct() {
        date_default_timezone_set('America/Bogota');
        setlocale(LC_TIME, 'es_CO.UTF-8', 'es_CO', 'esp');
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $database_url = getenv('DATABASE_URL');

            if ($database_url) {
                // Producción: Render + Supabase
                $parsed = parse_url($database_url);
                $host    = $parsed['host'];
                $port    = $parsed['port'] ?? 5432;
                $db      = ltrim($parsed['path'], '/');
                $user    = $parsed['user'];
                $pass    = $parsed['pass'];

                $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
                $this->conn = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => false,
                ]);
            } else {
                // Desarrollo local: PostgreSQL (configurar variables de entorno)
                // O usar archivo .env con DATABASE_URL
                throw new Exception("DATABASE_URL no configurada. Por favor configura la variable de entorno o usa el script de configuración: setup/configurar_postgresql.php");
            }

            return $this->conn;

        } catch (PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("Error de conexión a base de datos PostgreSQL. Configura DATABASE_URL o usa setup/configurar_postgresql.php");
        }
    }

    // Compatibilidad con el código existente
    public function getDbPath(): string {
        return getenv('DATABASE_URL') ? 'PostgreSQL (Producción)' : 'PostgreSQL (Local)';
    }

    // Método de consulta directa (alias: singleton)
    public function query(string $sql): PDOStatement {
        return $this->getConnection()->query($sql);
    }

    public function prepare(string $sql): PDOStatement {
        return $this->getConnection()->prepare($sql);
    }

    public function exec(string $sql): int|bool {
        return $this->getConnection()->exec($sql);
    }
}
?>
