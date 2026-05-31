<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Services;

use App\Modules\EventBus\Contracts\EventConsumer;
use RuntimeException;

final class ConsumerRegistry
{
    private array $consumers = [];
    private array $routing = [];

    public function register(string $name, EventConsumer $consumer, array $eventPatterns): void
    {
        $this->consumers[$name] = $consumer;

        foreach ($eventPatterns as $pattern) {
            $this->routing[$pattern][] = $name;
        }
    }

    public function getConsumersForEvent(string $eventType): array
    {
        $matched = [];

        foreach ($this->routing as $pattern => $consumerNames) {
            if ($this->patternMatches($pattern, $eventType)) {
                foreach ($consumerNames as $name) {
                    if (!in_array($name, $matched, true)) {
                        $matched[] = $name;
                    }
                }
            }
        }

        return array_map(fn (string $name) => $this->consumers[$name], $matched);
    }

    public function getConsumer(string $name): EventConsumer
    {
        return $this->consumers[$name] ?? throw new RuntimeException("Consumer {$name} not registered");
    }

    public function getConsumers(): array
    {
        return $this->consumers;
    }

    private function patternMatches(string $pattern, string $eventType): bool
    {
        if ($pattern === $eventType) {
            return true;
        }

        if (str_contains($pattern, '#')) {
            $prefix = str_replace('#', '', $pattern);
            return str_starts_with($eventType, $prefix);
        }

        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('\*', '[^.]+', preg_quote($pattern, '/')) . '$/';
            return (bool) preg_match($regex, $eventType);
        }

        return false;
    }
}
