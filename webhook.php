<?php
// webhook.php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 1) Load .env if present (otherwise use real env vars)
$dotenv = Dotenv::create(__DIR__);
$dotenv->safeLoad();

// 2) Connect to RabbitMQ
$conn    = new AMQPStreamConnection(
    getenv('RABBITMQ_HOST'),
    getenv('RABBITMQ_PORT'),
    getenv('RABBITMQ_USER'),
    getenv('RABBITMQ_PASSWORD')
);
$channel = $conn->channel();
$channel->queue_declare(
    getenv('RABBITMQ_QUEUE'),
    false, // passive
    true,  // durable
    false, // exclusive
    false  // auto_delete
);

// 3) Read raw request body (no JSON assumption)
$rawBody = file_get_contents('php://input');

// 4) Build a generic envelope
$envelope = [
    'source'    => getenv('WEBHOOK_SOURCE') ?: 'hookframe',
    'event'     => getenv('WEBHOOK_EVENT')  ?: 'event',    // default event name
    'timestamp' => gmdate('c'),
    'payload'   => $rawBody
];

// 5) Publish to RabbitMQ
$msg = new AMQPMessage(
    json_encode($envelope),
    ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
);
$channel->basic_publish($msg, '', getenv('RABBITMQ_QUEUE'));

// 6) Acknowledge receipt
echo "OK";
