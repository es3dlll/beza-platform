<?php

declare(strict_types=1);

namespace App\Modules\Bills\Controllers;

use App\Modules\Bills\Models\Bill;
use App\Modules\Bills\Models\ScheduledPayment;
use App\Modules\Bills\Services\BillPaymentProcessor;
use App\Modules\Bills\Services\BillPaymentScheduler;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class BillController extends Controller
{
    public function __construct(
        private readonly BillPaymentProcessor $paymentProcessor,
        private readonly BillPaymentScheduler $scheduler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Bill::query();
        if ($userId = $request->get('user_id')) {
            $query->byUser($userId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($providerId = $request->get('bill_provider_id')) {
            $query->where('bill_provider_id', $providerId);
        }
        return response()->json(['data' => $query->orderBy('due_date', 'desc')->get()]);
    }

    public function show(string $id): JsonResponse
    {
        $bill = Bill::with('provider')->find($id);
        if (!$bill) {
            return response()->json(['error' => 'الفاتورة غير موجودة'], 404);
        }
        return response()->json(['data' => $bill]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string|exists:users,id',
            'bill_provider_id' => 'required|string|exists:bill_providers,id',
            'account_number' => 'required|string|max:100',
            'amount_fils' => 'required|integer|min:1',
            'due_date' => 'required|date',
        ]);

        $bill = Bill::create($validated);
        return response()->json(['data' => $bill], 201);
    }

    public function pay(string $id, Request $request): JsonResponse
    {
        $bill = Bill::with('provider')->find($id);
        if (!$bill) {
            return response()->json(['error' => 'الفاتورة غير موجودة'], 404);
        }

        $userId = $request->get('user_id', $bill->user_id);
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'المستخدم غير موجود'], 404);
        }

        try {
            $result = $this->paymentProcessor->payBill($bill, $user);
            return response()->json([
                'data' => $result,
                'receipt' => $result->receipt_reference,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function preview(string $id): JsonResponse
    {
        $bill = Bill::with('provider')->find($id);
        if (!$bill) {
            return response()->json(['error' => 'الفاتورة غير موجودة'], 404);
        }
        return response()->json([
            'data' => $bill,
            'preview' => [
                'amount_fils' => $bill->amount_fils,
                'due_date' => $bill->due_date?->toDateString(),
                'is_overdue' => $bill->isOverdue(),
                'can_be_paid' => $bill->canBePaid(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        $total = Bill::count();
        $paid = Bill::paid()->count();
        $pending = Bill::pending()->count();
        $overdue = Bill::overdue()->count();

        return response()->json([
            'data' => [
                'total' => $total,
                'paid' => $paid,
                'pending' => $pending,
                'overdue' => $overdue,
                'overdue_rate' => $total > 0 ? round(($overdue / $total) * 100, 1) : 0,
            ],
        ]);
    }

    public function schedules(Request $request): JsonResponse
    {
        $query = ScheduledPayment::with('provider');
        if ($userId = $request->get('user_id')) {
            $query->byUser($userId);
        }
        return response()->json(['data' => $query->orderBy('next_execution_date')->get()]);
    }

    public function createSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string|exists:users,id',
            'bill_provider_id' => 'required|string|exists:bill_providers,id',
            'account_number' => 'required|string|max:100',
            'amount_fils' => 'required|integer|min:1',
            'recurrence' => 'required|in:monthly,quarterly,yearly',
            'recurrence_day' => 'required|integer|min:1|max:28',
            'next_execution_date' => 'nullable|date',
        ]);

        $user = User::find($validated['user_id']);
        if (!$user) {
            return response()->json(['error' => 'المستخدم غير موجود'], 404);
        }

        $schedule = $this->scheduler->createSchedule($user, $validated);
        return response()->json(['data' => $schedule], 201);
    }

    public function toggleSchedule(string $id): JsonResponse
    {
        $schedule = $this->scheduler->toggleSchedule($id);
        if (!$schedule) {
            return response()->json(['error' => 'الجدولة غير موجودة'], 404);
        }
        return response()->json(['data' => $schedule]);
    }

    public function processDueSchedules(): JsonResponse
    {
        $due = $this->scheduler->getDueSchedules();
        $processed = 0;
        $errors = 0;

        foreach ($due as $schedule) {
            $user = User::find($schedule->user_id);
            if (!$user) {
                $errors++;
                continue;
            }
            try {
                $result = $this->paymentProcessor->processScheduledPayment($schedule, $user);
                if ($result) $processed++;
                else $errors++;
            } catch (\RuntimeException) {
                $errors++;
            }
        }

        return response()->json([
            'data' => [
                'processed' => $processed,
                'errors' => $errors,
                'total_due' => count($due),
            ],
        ]);
    }
}
