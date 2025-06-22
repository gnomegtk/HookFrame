<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Classes\ExampleHandler;

$dotenv = Dotenv::create(__DIR__ . '/../');
$dotenv->safeLoad();

$rabbitHost = getenv('RABBITMQ_HOST');
$rabbitPort = (int) getenv('RABBITMQ_PORT');
$rabbitUser = getenv('RABBITMQ_USER');
$rabbitPass = getenv('RABBITMQ_PASSWORD');
$queueName  = getenv('RABBITMQ_QUEUE');
$retryLimit = (int) getenv('RETRY_LIMIT');

$conn    = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPass);
$channel = $conn->channel();
$channel->queue_declare($queueName, false, true, false, false);
$channel->queue_purge($queueName);
echo "🔄 Purged queue '{$queueName}'.\n";

$envelope = [
    'source'    => 'hookframe',
    'event'     => 'generic',
    'timestamp' => gmdate('c'),
    'payload'   => ['foo' => 'bar'] // intentionally missing 'id'
];

$channel->basic_publish(
    new AMQPMessage(json_encode($envelope), ['delivery_mode' => 2]),
    '',
    $queueName
);
echo "➤ Enqueued test message with missing 'id'.\n";

$handler = new ExampleHandler();

for ($i = 0; $i <= $retryLimit; $i++) {
    if ($i > 0) sleep(1);
    $msg = $channel->basic_get($queueName, false);
    if (!$msg) {
        echo "❌ No message found on attempt #{$i}\n";
        exit(1);
    }

    $body   = $msg->body;
    $parsed = json_decode($body, true);
    $retry  = $parsed['_retry'] ?? 0;

    echo "→ Attempt #{$i}, retry #{$retry}: $body\n";

    try {
        $handler->processMessage($body);
        $channel->basic_ack($msg->delivery_info['delivery_tag']);
        echo "✔ Unexpected success\n";
        break;
    } catch (\Exception $e) {
        echo "✖ Caught: {$e->getMessage()}\n";
        $channel->basic_ack($msg->delivery_info['delivery_tag']);

        $retry++;
        if ($retry <= $retryLimit) {
            $parsed['_retry'] = $retry;
            $channel->basic_publish(
                new AMQPMessage(json_encode($parsed), ['delivery_mode' => 2]),
                '',
                $queueName
            );
            echo "→ Requeued (_retry={$retry})\n";
        } else {
            echo "→ Discarded after max retries ({$retry})\n";
        }
    }
}

list($_, $remaining,) = $channel->queue_declare($queueName, true);
if ($remaining === 0) {
    echo "✔️ Retry flow passed: queue empty.\n";
} else {
    echo "❌ Retry flow failed: {$remaining} message(s) remain.\n";
}
