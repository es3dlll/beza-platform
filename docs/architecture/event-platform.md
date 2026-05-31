# Event Platform Engineering Spec

## Infrastructure

```
RabbitMQ Cluster: 3 nodes (HA)
  - Management UI: https://rabbitmq.beza.com:15672
  - AMQPS: 5671 (TLS)
  - Memory high watermark: 0.6
  - Policy: ha-mode=all, ha-sync-mode=automatic

Kafka Cluster (Analytics): 3 brokers
  - Version: 3.5+
  - Partitions: 6 per topic
  - Replication factor: 3
  - Retention: 7 days (compact for keyed topics)
```

## Exchange Topology

```
┌──────────────────────────────────────────────────────────┐
│                     beza-events (topic)                    │
│  Routing keys: com.beza.*.*, com.beza.*.*.*               │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Q: notifications          RK: com.beza.*.completed      │
│  Q: analytics              RK: com.beza.*.*               │
│  Q: compliance             RK: com.beza.*.*               │
│  Q: fraud                  RK: com.beza.transfer.*        │
│  Q: audit                  RK: # (all events)             │
│  Q: dlq (dead letter)      RK: # (failed after 3 retries)│
│                                                          │
└──────────────────────────────────────────────────────────┘
```

## Queue Configuration

### Worker Queue Definition

```yaml
notifications:
  durable: true
  arguments:
    x-dead-letter-exchange: beza-dlq
    x-dead-letter-routing-key: notifications.dlq
    x-message-ttl: 86400000  # 24 hours
  bindings:
    - exchange: beza-events
      routing_key: "com.beza.#.completed"
      routing_key: "com.beza.transfer.received"
      routing_key: "com.beza.wallet.credited"

compliance:
  durable: true
  arguments:
    x-dead-letter-exchange: beza-dlq
    x-message-ttl: 604800000  # 7 days
  bindings:
    - exchange: beza-events
      routing_key: "com.beza.transfer.*"
      routing_key: "com.beza.wallet.*"
      routing_key: "com.beza.user.registered"
      routing_key: "com.beza.agent.*"

fraud:
  durable: true
  arguments:
    x-dead-letter-exchange: beza-dlq
    x-message-ttl: 3600000  # 1 hour
  bindings:
    - exchange: beza-events
      routing_key: "com.beza.transfer.sent"
      routing_key: "com.beza.wallet.credited"
      routing_key: "com.beza.agent.cash-*"
```

## Producer Pattern

```php
class RabbitMQProducer
{
    public function __construct(
        private AMQPChannel $channel,
        private string $exchange = 'beza-events',
    ) {}

    public function publish(CloudEvent $event): void
    {
        $message = new AMQPMessage(
            body: json_encode($event->toArray()),
            properties: [
                'content_type' => 'application/cloudevents+json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $event->id,
                'timestamp' => $event->time->timestamp,
                'app_id' => $event->source,
                'headers' => [
                    'ce-specversion' => '1.0',
                    'ce-id' => $event->id,
                    'ce-source' => $event->source,
                    'ce-type' => $event->type,
                    'ce-subject' => $event->subject,
                    'ce-time' => $event->time->toRfc3339String(),
                    'ce-tenant_id' => (string) $event->tenantId,
                ],
            ]
        );

        $this->channel->basic_publish(
            msg: $message,
            exchange: $this->exchange,
            routing_key: $event->type,
        );
    }
}
```

## Consumer Pattern

```php
class TransferNotificationConsumer
{
    public function __construct(
        private NotificationService $notifications,
        private UserRepository $users,
    ) {}

    public function handle(CloudEvent $event): void
    {
        $data = $event->data;

        match ($event->type) {
            'com.beza.transfer.received' => $this->notifications->sendPush(
                userId: $data['recipient_id'],
                title: 'تم استلام أموال',
                body: "تم استلام {$data['amount']} {$data['currency']} من {$this->users->find($data['sender_id'])->name}",
            ),
            'com.beza.transfer.sent' => $this->notifications->sendPush(
                userId: $data['sender_id'],
                title: 'تم إرسال الأموال',
                body: "تم إرسال {$data['amount']} {$data['currency']} إلى {$data['recipient_name']}",
            ),
            default => throw new UnknownEventTypeException($event->type),
        };
    }
}
```

## Dead Letter Queue Management

```
DLQ Message Format:
{
  "original_message": { ... },
  "error": "TimeoutException: Connection timed out",
  "retry_count": 3,
  "last_attempted": "2026-06-01T10:00:00Z",
  "queue": "notifications",
  "routing_key": "com.beza.transfer.received"
}

DLQ Monitoring:
  - Alert if DLQ depth > 100 messages
  - Ops reviews DLQ daily
  - Replay tool: select messages → requeue to original queue
  - Poison messages: after 3 replay attempts → archive to S3
```

## Event Schema Registry

```
Schema Storage: MySQL table or dedicated schema registry
Schema Format: JSON Schema (draft-07)

Validation:
  - Producer validates event before publish
  - Consumer validates event before processing
  - Schema changes: backward compatible only
  - Breaking change → new event type version

Schema Example: transfer.sent.json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "TransferSent",
  "type": "object",
  "required": ["amount", "currency", "sender_id", "recipient_id"],
  "properties": {
    "amount": { "type": "integer", "minimum": 0 },
    "currency": { "type": "string", "enum": ["SYP", "USD"] },
    "sender_id": { "type": "integer" },
    "recipient_id": { "type": "integer" },
    "fee": { "type": "integer", "minimum": 0 },
    "note": { "type": "string", "maxLength": 500 }
  }
}
```
