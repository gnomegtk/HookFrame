<?php
// classes/ExampleHandler.php

namespace Classes;

class ExampleHandler implements HandlerInterface
{
    /** @var HandlerInterface|null */
    private $nextHandler;

    /**
     * @inheritDoc
     */
    public function setNextHandler(HandlerInterface $handler): HandlerInterface
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    /**
     * Parses a raw JSON envelope and dispatches to handle().
     *
     * @param string $message JSON-encoded envelope
     * @throws \InvalidArgumentException on JSON error
     * @throws \RuntimeException         on missing required data
     */
    public function processMessage(string $message): void
    {
        $env = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        $this->handle($env);
    }

    /**
     * @inheritDoc
     */
    public function handle(array $envelope): void
    {
        // 1) Filter by source
        if (($envelope['source'] ?? '') !== 'hookframe') {
            echo "→ Skipping (source ≠ hookframe)\n";
            return;
        }

        // 2) Filter by event
        if (($envelope['event'] ?? '') !== 'generic') {
            echo "→ Skipping (event ≠ generic)\n";
            return;
        }

        // 3) Validate payload
        $data = $envelope['payload'] ?? [];
        if (empty($data['id'])) {
            throw new \RuntimeException("Payload missing required 'id'");
        }

        // 4) Example processing logic
        echo "→ Processed payload ID {$data['id']}\n";
    }
}
