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

            // Si no hay variable de entorno, intentar cargar desde archivo .env (Desarrollo Local)
            if (!$database_url) {
                $env_path = __DIR__ . '/../../.env';
                if (file_exists($env_path)) {
                    $lines = @file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines) {
                        foreach ($lines as $line) {
                            if (strpos(trim($line), '#') === 0) continue;
                            $parts = explode('=', $line, 2);
                            if (count($parts) === 2) {
                                list($name, $value) = $parts;
                                if (trim($name) == 'DATABASE_URL') {
                                    $database_url = trim($value);
                                    putenv("DATABASE_URL=$database_url");
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if ($database_url) {
                // Producción o Local con DATABASE_URL (PostgreSQL)
                $parsed = parse_url($database_url);
                $host    = $parsed['host'];
                $port    = $parsed['port'] ?? 5432;
                $db      = ltrim($parsed['path'], '/');
                $user    = $parsed['user'];
                $pass    = $parsed['pass'];

                $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
                if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
                    $dsn = "pgsql:host=$host;port=$port;dbname=$db"; // Quitar SSL para local
                }

                $this->conn = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => false,
                ]);
            } else {
                // FALLBACK A SQLITE (Desarrollo Local sin DATABASE_URL)
                $sqlite_path = __DIR__ . '/../database.sqlite';
                if (file_exists($sqlite_path)) {
                    $this->conn = new PDO("sqlite:$sqlite_path", null, null, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    
                    // Habilitar Llaves Foráneas en SQLite
                    $this->conn->exec('PRAGMA foreign_keys = ON;');
                } else {
                    throw new Exception("No se encontró base de datos (PostgreSQL DATABASE_URL o SQLite en api/database.sqlite)");
                }
            }

            return $this->conn;

        } catch (PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("Error de conexión a base de datos. Detalles: " . $e->getMessage());
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
