<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Events\BetaFeedbackReceived;
use App\Modules\Core\Models\BetaFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class BetaFeedbackController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|in:' . implode(',', BetaFeedback::CATEGORIES),
            'description' => 'required|string|max:500',
            'screenshot' => 'nullable|image|max:2048',
            'rating' => 'required|integer|min:1|max:5',
            'allow_followup' => 'boolean',
        ]);

        $userId = $request->user()?->id ?? 'anonymous';

        $duplicate = BetaFeedback::where('user_id', $userId)
            ->where('description', $validated['description'])
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'Duplicate feedback ignored'], 200);
        }

        $screenshotUrl = null;
        if ($request->hasFile('screenshot')) {
            $screenshotUrl = $request->file('screenshot')->store('beta-feedback', 'public');
        }

        $feedbackId = 'FDB-' . Str::ulid()->toBase32();

        $feedback = BetaFeedback::create([
            'feedback_id' => $feedbackId,
            'user_id' => $userId,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'screenshot_url' => $screenshotUrl,
            'rating' => $validated['rating'],
            'allow_followup' => $validated['allow_followup'] ?? false,
            'status' => 'new',
        ]);

        Event::dispatch(new BetaFeedbackReceived(
            feedbackId: $feedbackId,
            userId: $userId,
            category: $validated['category'],
            description: $validated['description'],
            screenshotUrl: $screenshotUrl,
            rating: $validated['rating'],
            allowFollowup: $validated['allow_followup'] ?? false,
            timestamp: now()->getTimestamp(),
        ));

        return response()->json([
            'message' => 'شكراً لملاحظاتك! تم استلامها بنجاح.',
            'ticket_id' => $feedbackId,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = BetaFeedback::query();

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($perPage)
        );
    }

    public function update(Request $request, string $feedbackId): JsonResponse
    {
        $feedback = BetaFeedback::where('feedback_id', $feedbackId)->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', BetaFeedback::STATUSES),
            'internal_notes' => 'nullable|array',
        ]);

        $feedback->update([
            'status' => $validated['status'],
            'internal_notes' => $validated['internal_notes'] ?? $feedback->internal_notes,
        ]);

        return response()->json($feedback->fresh());
    }

    public function export(): JsonResponse
    {
        $feedbacks = BetaFeedback::orderBy('created_at', 'desc')->get();

        $csv = "ticket_id,category,rating,status,description,created_at\n";
        foreach ($feedbacks as $fb) {
            $desc = str_replace('"', '""', $fb->description);
            $csv .= "\"{$fb->feedback_id}\",{$fb->category},{$fb->rating},{$fb->status},\"{$desc}\",{$fb->created_at}\n";
        }

        return response()->json(['csv' => $csv]);
    }
}
