<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents('php://input');
    file_put_contents(__DIR__ . '/js_errors.log', date('Y-m-d H:i:s') . ' - ' . $data . PHP_EOL, FILE_APPEND);
    echo "OK";
}
