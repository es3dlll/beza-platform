<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Controllers;

use App\Modules\EventBus\Services\EventBusHealthCheck;
use App\Modules\EventBus\Services\PoisonPillMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class EventBusController extends Controller
{
    public function __construct(
        private readonly EventBusHealthCheck $healthCheck,
        private readonly PoisonPillMonitor $poisonMonitor,
    ) {}

    public function health(): JsonResponse
    {
        return response()->json($this->healthCheck->check());
    }

    public function deadLetters(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $events = $this->poisonMonitor->getDeadLetterEvents($status);

        return response()->json([
            'data' => $events->values(),
            'stats' => $this->poisonMonitor->countByStatus(),
        ]);
    }

    public function retryDeadLetter(string $id): JsonResponse
    {
        $this->poisonMonitor->retryDeadLetter($id);

        return response()->json(['message' => 'Dead letter event queued for retry']);
    }
}
