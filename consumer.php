<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Classes\ExampleHandler;

// Load environment variables safely
$dotenv = Dotenv::create(__DIR__);
$dotenv->safeLoad();

$rabbitHost     = getenv('RABBITMQ_HOST');
$rabbitPort     = (int) getenv('RABBITMQ_PORT');
$rabbitUser     = getenv('RABBITMQ_USER');
$rabbitPassword = getenv('RABBITMQ_PASSWORD');
$queueName      = getenv('RABBITMQ_QUEUE');
$retryLimit     = (int) getenv('RETRY_LIMIT');

try {
    $conn    = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword);
    $channel = $conn->channel();
    $channel->queue_declare($queueName, false, true, false, false);

    $handler = new ExampleHandler();

    echo "⏳ Waiting for messages on '{$queueName}'...\n";

    $channel->basic_consume($queueName, '', false, false, false, false, function (AMQPMessage $msg) use ($handler, $channel, $queueName, $retryLimit) {
        $body = $msg->body;
        try {
            $handler->processMessage($body);
            $msg->ack();
            echo "✔ Processed and ACK’d\n";
        } catch (\Exception $e) {
            echo "✖ Handler error: {$e->getMessage()}\n";
            $msg->ack();

            $env   = json_decode($body, true);
            $count = ($env['_retry'] ?? 0) + 1;

            if ($count <= $retryLimit) {
                $env['_retry'] = $count;
                $channel->basic_publish(
                    new AMQPMessage(json_encode($env), ['delivery_mode' => 2]),
                    '',
                    $queueName
                );
                echo "→ Requeued (_retry={$count})\n";
            } else {
                echo "→ Discarded after max retries ({$retryLimit})\n";
            }
        }
    });

    while ($channel->is_consuming()) {
        $channel->wait();
    }
} catch (\Exception $e) {
    echo "✖ Connection error: {$e->getMessage()}\n";
    exit(1);
}
