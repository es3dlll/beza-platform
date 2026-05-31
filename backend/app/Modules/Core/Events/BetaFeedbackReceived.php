<?php

declare(strict_types=1);

namespace App\Modules\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class BetaFeedbackReceived
{
    use Dispatchable;

    public function __construct(
        public readonly string $feedbackId,
        public readonly string $userId,
        public readonly string $category,
        public readonly string $description,
        public readonly ?string $screenshotUrl,
        public readonly int $rating,
        public readonly bool $allowFollowup,
        public readonly int $timestamp,
    ) {}
}
