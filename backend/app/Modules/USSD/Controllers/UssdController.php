<?php

declare(strict_types=1);

namespace Modules\USSD\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\USSD\Services\UssdMenuEngine;

class UssdController extends Controller
{
    public function __construct(
        private readonly UssdMenuEngine $menuEngine,
    ) {}

    /**
     * Handle USSD request from Syriatel gateway.
     *
     * Expected POST payload:
     * {
     *   "session_id": "uuid",
     *   "msisdn": "9639XXXXXXXX",
     *   "text": "*123#",
     *   "service_code": "*123#"
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:64',
            'msisdn' => 'required|string|regex:/^963\d{9}$/',
            'text' => 'required|string|max:200',
            'service_code' => 'sometimes|string|max:20',
        ]);

        $result = $this->menuEngine->handle(
            sessionId: $validated['session_id'],
            msisdn: $validated['msisdn'],
            text: $validated['text'],
        );

        return response()->json($result);
    }

    /**
     * Callback endpoint for async USSD responses (SMS delivery, etc).
     */
    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
            'status' => 'required|string|in:success,failed,timeout',
        ]);

        return response()->json(['status' => 'received']);
    }
}
