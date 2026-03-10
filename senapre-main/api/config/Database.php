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
                // Desarrollo local: SQLite (sin cambios)
                $db_file = __DIR__ . '/../../database/Asistnet.db';
                if (!file_exists($db_file)) {
                    throw new Exception("CRITICO: No se encuentra la BD en: $db_file");
                }
                $this->conn = new PDO("sqlite:$db_file");
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->conn->exec('PRAGMA foreign_keys = ON;');
                $this->conn->exec('PRAGMA busy_timeout = 30000;');
            }

            return $this->conn;

        } catch (PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("Error de conexión a base de datos");
        }
    }

    // Compatibilidad con el código existente
    public function getDbPath(): string {
        return getenv('DATABASE_URL') ? 'PostgreSQL (Supabase)' : 'SQLite (local)';
    }

    // Método de consulta directa (alias: singleton)
    public function query(string $sql): PDOStatement {
        return $this->getConnection()->query($sql);
    }

    public function prepare(string $sql): PDOStatement {
        return $this->getConnection()->prepare($sql);
    }

    public function exec(string $sql): int|false {
        return $this->getConnection()->exec($sql);
    }
}
?>
