<?php
// Configurador de PostgreSQL para desarrollo local
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar PostgreSQL - SenApre</title>
    <style>
        body { font-family: 'Inter', sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input, select { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #39A900; }
        .btn { background: #39A900; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #2d7d00; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-info { background: #e0f2fe; border-left: 4px solid #0ea5e9; color: #0369a1; }
        .alert-success { background: #dcfce7; border-left: 4px solid #39A900; color: #166534; }
        .alert-error { background: #fef2f2; border-left: 4px solid #ef4444; color: #dc2626; }
    </style>
</head>
<body>
    <h1>🔧 Configurar PostgreSQL para SenApre</h1>
    
    <div class="alert alert-info">
        <strong>ℹ️ Información:</strong> Este script te ayudará a configurar tu conexión PostgreSQL y crear las tablas necesarias para el sistema de liderazgo.
    </div>

    <?php
    if ($_POST['configurar']) {
        $host = $_POST['host'] ?? 'localhost';
        $port = $_POST['port'] ?? '5432';
        $database = $_POST['database'] ?? 'Senapre';
        $user = $_POST['user'] ?? 'postgres';
        $password = $_POST['password'] ?? '';
        
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$database";
            $conn = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            echo "<div class='alert alert-success'>✅ Conexión exitosa a PostgreSQL</div>";
            
            // Crear tablas
            $sqlFile = __DIR__ . '/crear_tablas_postgresql.sql';
            $sql = file_get_contents($sqlFile);
            
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $conn->exec($statement);
                    } catch (Exception $e) {
                        // Ignorar errores de duplicados
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "<div class='alert alert-error'>⚠️ Error: " . $e->getMessage() . "</div>";
                        }
                    }
                }
            }
            
            echo "<div class='alert alert-success'>✅ Tablas creadas exitosamente</div>";
            echo "<p><a href='../liderazgo-poblacion.html' class='btn'>Ir a Población</a></p>";
            
        } catch (Exception $e) {
            echo "<div class='alert alert-error'>❌ Error de conexión: " . $e->getMessage() . "</div>";
        }
    }
    ?>

    <form method="post">
        <div class="form-group">
            <label for="host">Host:</label>
            <input type="text" id="host" name="host" value="localhost" required>
        </div>
        
        <div class="form-group">
            <label for="port">Puerto:</label>
            <input type="text" id="port" name="port" value="5432" required>
        </div>
        
        <div class="form-group">
            <label for="database">Base de datos:</label>
            <input type="text" id="database" name="database" value="Senapre" required>
        </div>
        
        <div class="form-group">
            <label for="user">Usuario:</label>
            <input type="text" id="user" name="user" value="postgres" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" name="configurar" class="btn">Configurar y Crear Tablas</button>
    </form>
    
    <div class="alert alert-info" style="margin-top: 30px;">
        <h3>📋 Instrucciones:</h3>
        <ol>
            <li>Asegúrate de que PostgreSQL esté instalado y corriendo</li>
            <li>Crea la base de datos "Senapre" si no existe</li>
            <li>Ingresa tus credenciales de PostgreSQL</li>
            <li>Haz clic en "Configurar y Crear Tablas"</li>
            <li>El sistema creará automáticamente las tablas necesarias</li>
        </ol>
        
        <h4>🔧 Comandos útiles (si usas Windows con WAMP):</h4>
        <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;">
# Crear base de datos
createdb Senapre

# Acceder a PostgreSQL
psql -U postgres -d Senapre

# Crear usuario (opcional)
CREATE USER senapre_user WITH PASSWORD 'tu_password';
GRANT ALL PRIVILEGES ON DATABASE Senapre TO senapre_user;
        </pre>
    </div>
</body>
</html>
