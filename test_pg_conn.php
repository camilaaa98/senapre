<?php
// Test script for PostgreSQL connection
echo "Testing PostgreSQL connection formats...\n";

$dbName = "senapre";
$user = "postgres";

$tests = [
    "DSN only, no pass in DSN" => ["pgsql:host=localhost;dbname=$dbName", $user, ""],
    "DSN with pass parameter" => ["pgsql:host=localhost;dbname=$dbName;user=$user;password="],
    "DSN with user parameter, null pass" => ["pgsql:host=localhost;dbname=$dbName;user=$user", null, null],
    "DSN with user/pass in constructor" => ["pgsql:host=localhost;dbname=$dbName", $user, null],
    "127.0.0.1" => ["pgsql:host=127.0.0.1;dbname=$dbName", $user, ""],
];

foreach ($tests as $name => $params) {
    echo "\nTesting: $name\n";
    try {
        if (count($params) == 1) {
            $conn = new PDO($params[0]);
        } else {
            $conn = new PDO($params[0], $params[1], $params[2]);
        }
        echo "✅ SUCCESS!\n";
        break;
    } catch (Exception $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n";
    }
}
?>
