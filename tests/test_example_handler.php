<?php
// tests/test_example_handler.php

require_once __DIR__ . '/../vendor/autoload.php';

use Classes\ExampleHandler;

// Simulate a valid envelope
$envelope = [
    'source'    => 'hookframe',
    'event'     => 'generic',
    'timestamp' => gmdate('c'),
    'payload'   => ['id' => 42, 'foo' => 'bar']
];

echo "🧪 Running ExampleHandler success test...\n";
$handler = new ExampleHandler();

try {
    $handler->processMessage(json_encode($envelope));
    echo "✔ Passed: no exception thrown.\n";
} catch (\Exception $e) {
    echo "❌ Failed: {$e->getMessage()}\n";
}
