<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FeedbackController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'category' => 'required|string|in:ui_issue,feature_request,bug_report,compliance_question',
            'severity' => 'nullable|string|in:low,medium,high',
            'description' => 'required|string|max:2000',
            'screenshot_url' => 'nullable|url',
            'context' => 'nullable|array',
            'context.report_id' => 'nullable|string',
            'context.screen_name' => 'nullable|string',
            'context.browser_info' => 'nullable|string',
        ]);

        $feedback = Feedback::create([
            'user_id' => $validated['user_id'],
            'module' => 'ledger',
            'category' => $validated['category'],
            'severity' => $validated['severity'] ?? 'low',
            'description' => $validated['description'],
            'screenshot_url' => $validated['screenshot_url'] ?? null,
            'context' => $validated['context'] ?? null,
            'status' => 'new',
        ]);

        return response()->json([
            'message' => 'Feedback received',
            'feedback_id' => $feedback->id,
        ], 201);
    }
}
