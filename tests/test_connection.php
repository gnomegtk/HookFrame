<?php
// tests/test_connection.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;

// 1) Load .env (Dotenv v3 syntax)
$dotenv = Dotenv::create(__DIR__ . '/../');
$dotenv->safeLoad();

// 2) Prepare log
$logFile = __DIR__ . '/../logs/test_connection.log';
function logMessage($msg) {
    global $logFile;
    $dir = dirname($logFile);
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    file_put_contents($logFile, "[" . gmdate('Y-m-d H:i:s') . " GMT] $msg\n", FILE_APPEND);
}

logMessage("Test started: RabbitMQ connection check...");

// 3) Attempt connection
try {
    $connection = new AMQPStreamConnection(
        getenv('RABBITMQ_HOST'),
        (int) getenv('RABBITMQ_PORT'),
        getenv('RABBITMQ_USER'),
        getenv('RABBITMQ_PASSWORD')
    );
    logMessage("✔️ Connection successful");
    echo "✔️ RabbitMQ connection successful!\n";
    $connection->close();
    exit(0);
} catch (\Exception $e) {
    logMessage("❌ Connection failed: " . $e->getMessage());
    echo "❌ RabbitMQ connection failed! See logs/test_connection.log\n";
    exit(1);
}
