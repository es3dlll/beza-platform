<?php

declare(strict_types=1);

namespace Modules\Education\Services;

use Illuminate\Support\Str;
use Modules\Education\Models\EducationInstitution;
use Modules\Education\Models\EducationStudent;
use Modules\Education\Models\EducationFee;
use Modules\Education\Enums\EducationFeeStatus;
use Modules\Education\Exceptions\InstitutionNotFoundException;
use Modules\Education\Exceptions\StudentNotFoundException;
use Modules\Education\Exceptions\FeeAlreadyPaidException;

final class EducationService
{
    public function listInstitutions(): iterable { return EducationInstitution::where('is_active', true)->get(); }

    public function registerStudent(string $userId, string $institutionId, string $studentId, string $fullName, string $fullNameAr, ?string $grade = null): EducationStudent
    {
        $inst = EducationInstitution::find($institutionId);
        if (!$inst) throw new InstitutionNotFoundException($institutionId);
        return EducationStudent::create([
            'id' => (string) Str::ulid(), 'user_id' => $userId, 'institution_id' => $institutionId,
            'student_id' => $studentId, 'full_name' => $fullName, 'full_name_ar' => $fullNameAr,
            'grade' => $grade, 'status' => 'active',
        ]);
    }

    public function createFee(string $studentId, string $feeType, int $amount, string $dueDate): EducationFee
    {
        $student = EducationStudent::find($studentId);
        if (!$student) throw new StudentNotFoundException($studentId);
        return EducationFee::create([
            'id' => (string) Str::ulid(), 'student_id' => $studentId, 'fee_type' => $feeType,
            'amount' => $amount, 'due_date' => $dueDate, 'status' => EducationFeeStatus::PENDING->value,
        ]);
    }

    public function payFee(string $feeId, int $amount): EducationFee
    {
        $fee = EducationFee::findOrFail($feeId);
        if ($fee->status === EducationFeeStatus::PAID->value) throw new FeeAlreadyPaidException;
        $paid = $fee->paid_amount + $amount;
        $fee->update([
            'paid_amount' => $paid,
            'status' => $paid >= $fee->amount ? EducationFeeStatus::PAID->value : EducationFeeStatus::PARTIAL->value,
            'receipt_number' => $paid >= $fee->amount ? 'EDU-' . strtoupper(Str::random(10)) : $fee->receipt_number,
            'paid_at' => $paid >= $fee->amount ? now() : $fee->paid_at,
        ]);
        return $fee;
    }

    public function studentFees(string $studentId): iterable { return EducationFee::where('student_id', $studentId)->orderByDesc('due_date')->get(); }

    public function institutionDashboard(string $institutionId): array
    {
        $inst = EducationInstitution::find($institutionId);
        if (!$inst) throw new InstitutionNotFoundException($institutionId);
        $feesQuery = EducationFee::whereHas('student', fn($q) => $q->where('institution_id', $institutionId));
        $totalFees = $feesQuery->clone()->where('status', EducationFeeStatus::PAID->value);
        $collected = (int) $totalFees->sum('paid_amount');
        $pending = (int) $feesQuery->clone()->where('status', EducationFeeStatus::PENDING->value)->count();
        $overdue = (int) $feesQuery->clone()->whereIn('status', [EducationFeeStatus::PENDING->value, EducationFeeStatus::PARTIAL->value])->where('due_date', '<', now()->toDateString())->count();
        $totalAmount = (int) $feesQuery->clone()->sum('amount');
        $collectionRate = $totalAmount > 0 ? round(($collected / $totalAmount) * 100, 2) : 0.0;
        $recent = $feesQuery->clone()->with('student:id,full_name')->latest()->take(10)->get()->toArray();
        return compact('collected', 'pending', 'overdue', 'totalAmount', 'collectionRate', 'recent');
    }

    public function bulkCreateFees(string $institutionId, array $fees): array
    {
        $inst = EducationInstitution::find($institutionId);
        if (!$inst) throw new InstitutionNotFoundException($institutionId);
        $studentIds = EducationStudent::where('institution_id', $institutionId)->pluck('id')->toArray();
        $studentLookup = EducationStudent::where('institution_id', $institutionId)->pluck('id', 'student_id');
        $created = 0;
        $errors = [];
        foreach ($fees as $i => $fee) {
            $student = EducationStudent::find($fee['student_id'] ?? '');
            if (!$student) {
                $errors[] = ['row' => $i, 'student_id' => $fee['student_id'] ?? '', 'error' => 'Student not found'];
                continue;
            }
            if (!in_array($student->id, $studentIds, true)) {
                $errors[] = ['row' => $i, 'student_id' => $fee['student_id'], 'error' => 'Student does not belong to this institution'];
                continue;
            }
            if (!isset($fee['fee_type']) || !isset($fee['amount']) || !isset($fee['due_date'])) {
                $errors[] = ['row' => $i, 'student_id' => $fee['student_id'] ?? '', 'error' => 'Missing required fields: fee_type, amount, due_date'];
                continue;
            }
            EducationFee::create([
                'id' => (string) Str::ulid(), 'student_id' => $student->id,
                'fee_type' => $fee['fee_type'], 'amount' => (int) $fee['amount'],
                'due_date' => $fee['due_date'], 'status' => EducationFeeStatus::PENDING->value,
            ]);
            $created++;
        }
        return ['created' => $created, 'errors' => $errors];
    }

    public function overdueFees(string $institutionId): iterable
    {
        $inst = EducationInstitution::find($institutionId);
        if (!$inst) throw new InstitutionNotFoundException($institutionId);
        return EducationFee::whereHas('student', fn($q) => $q->where('institution_id', $institutionId))
            ->whereIn('status', [EducationFeeStatus::PENDING->value, EducationFeeStatus::PARTIAL->value])
            ->where('due_date', '<', now()->toDateString())
            ->with('student:id,full_name,student_id')
            ->orderByDesc('due_date')
            ->get();
    }

    public function generateReceipt(string $feeId): array
    {
        $fee = EducationFee::with('student.institution')->findOrFail($feeId);
        return [
            'receipt_number' => $fee->receipt_number ?? 'EDU-' . strtoupper(Str::random(10)),
            'student_name' => $fee->student->full_name,
            'amount' => $fee->amount,
            'paid_amount' => $fee->paid_amount,
            'date' => ($fee->paid_at ?? $fee->created_at)->toDateString(),
            'institution_code' => $fee->student->institution->code,
            'fee_type' => $fee->fee_type,
            'status' => $fee->status,
        ];
    }
}
