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

class EducationService
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
}
