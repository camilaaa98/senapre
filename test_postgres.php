<?php
// Force LOCAL DATABASE_URL from .env
$env_lines = @file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$database_url = null;
if ($env_lines) {
    foreach ($env_lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) == 'DATABASE_URL') {
            $database_url = trim($parts[1]);
            break;
        }
    }
}

echo "Testing with DATABASE_URL: $database_url\n";

if ($database_url) {
    try {
        $parsed = parse_url($database_url);
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? 5432;
        $db = ltrim($parsed['path'] ?? '', '/');
        $user = $parsed['user'] ?? '';
        $pass = $parsed['pass'] ?? '';

        $dsn = "pgsql:host=$host;port=$port;dbname=$db";
        $conn = new PDO($dsn, $user, $pass);
        echo "✅ Connected to Postgres!\n";
        
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
        $stmt = $conn->query($sql);
        while ($row = $stmt->fetch()) {
            echo "- " . $row['table_name'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No DATABASE_URL found in .env\n";
}
