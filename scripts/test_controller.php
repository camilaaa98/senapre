<?php
require_once __DIR__ . '/../api/controllers/DashboardController.php';

try {
    $controller = new DashboardController();
    $stats = $controller->getStats();

    echo "Dashboard Stats Result:\n";
    print_r($stats);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
