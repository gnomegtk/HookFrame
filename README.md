# 🎣 HookFrame – A Simple Webhook‐Processing Framework

# HookFrame

**Minimal, flexible, and queue-ready.**

A lightweight PHP framework for receiving, queuing, and processing webhooks with retry support using RabbitMQ. Designed for extensibility, resilience, and simplicity—no assumptions about the payload.

1. Receives HTTP webhooks via `webhook.php`
2. Publishes them to RabbitMQ
3. Processes messages with a chain of handlers (`ExampleHandler`)
4. Supports automatic retries on handler failure

---

## 🚀 Features

- **Generic Webhook Receiver**: Accepts any HTTP request.  
- **RabbitMQ Integration**: Durable queues, manual ACK/NACK, persistent messages.  
- **Chain-of-Responsibility**: Wire up multiple handlers to filter, transform, or process envelopes.  
- **Retry Logic**: Automatic requeueing with `_retry` counter and configurable `RETRY_LIMIT`.  
- **No Payload Assumptions**: Handlers decide how to parse `payload`.  
- **PSR-4 Autoload**: Drop your classes in `classes/` and follow the `Classes\` namespace.  
- **Test Suite**: Unit test for handler logic and full retry-flow simulation.

---

## 📦 Installation

```bash
git clone https://github.com/gnomegtk/HookFrame.git
cd HookFrame
composer install
composer dump-autoload
cp .env.example .env
# edit .env as needed
```

---

## 🔗 Architecture

1. **HandlerInterface**  
   ```php
   interface HandlerInterface {
       public function setNextHandler(HandlerInterface $handler): HandlerInterface;
       public function handle(array $envelope): void;
   }


2. **Webhook Receiver**  
   - Publishes to RabbitMQ.

3. **ExampleHandler**  

---

## 🔁 Retry Logic

When a handler (e.g. `ExampleHandler`) throws an exception, the consumer should:

1. **ACK** the current message (to remove it).
2. **Increment** the `_retry` field in the JSON envelope.
3. **Re-publish** the envelope if `_retry <= RETRY_LIMIT`.
4. **Discard** (ACK without requeue) once `_retry > RETRY_LIMIT`.

---

## ⚙️ Configuration

You can configure via `.env` **or** system environment variables (`getenv()`).

```env
# .env file (example)
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_QUEUE=queue_example

RETRY_LIMIT=3
```
