<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Contracts;

interface AsyncEvent
{
    public function getEventType(): string;
    public function getEventVersion(): string;
    public function getPayload(): array;
    public function getSource(): string;
}
