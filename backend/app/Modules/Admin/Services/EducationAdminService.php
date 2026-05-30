<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Support\Str;
use Modules\Education\Models\EducationFee;
use Modules\Education\Models\EducationInstitution;
use Modules\Education\Models\EducationStudent;
use Modules\Education\Services\EducationService;

final class EducationAdminService
{
    public function __construct(
        private readonly EducationService $education,
    ) {}

    public function dashboard(): array
    {
        $totalInstitutions = EducationInstitution::count();
        $totalStudents = EducationStudent::count();
        $feesCollected = (int) EducationFee::where('status', 'paid')->sum('paid_amount');

        $overdueByInstitution = EducationInstitution::withCount([
            'students' => fn($q) => $q->whereHas('fees', fn($f) => $f->whereIn('status', ['pending', 'partial'])->where('due_date', '<', now()->toDateString())),
        ])->get()->map(fn($i) => [
            'id' => $i->id, 'name' => $i->name, 'overdue_students' => $i->students_count,
        ]);

        return [
            'total_institutions' => $totalInstitutions,
            'total_students' => $totalStudents,
            'total_fees_collected' => $feesCollected,
            'overdue_by_institution' => $overdueByInstitution,
        ];
    }

    public function institutions(): iterable
    {
        return EducationInstitution::all();
    }

    public function institutionDetail(string $id): array
    {
        $institution = EducationInstitution::with('students.fees')->findOrFail($id);
        $recentPayments = EducationFee::whereHas('student', fn($q) => $q->where('institution_id', $id))
            ->where('status', 'paid')->with('student')->latest()->take(20)->get();
        return [
            'institution' => $institution,
            'recent_payments' => $recentPayments,
        ];
    }

    public function createInstitution(array $data): EducationInstitution
    {
        return EducationInstitution::create([
            'id' => (string) Str::ulid(),
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'code' => $data['code'] ?? null,
            'type' => $data['type'] ?? 'school',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'is_active' => true,
        ]);
    }

    public function updateInstitution(string $id, array $data): void
    {
        EducationInstitution::where('id', $id)->update($data);
    }

    public function listOverdueStudents(?string $institutionId): iterable
    {
        $q = EducationFee::whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->with('student.institution');
        if ($institutionId) {
            $q->whereHas('student', fn($s) => $s->where('institution_id', $institutionId));
        }
        return $q->orderByDesc('due_date')->get();
    }

    public function collectionReport(string $institutionId, ?string $from, ?string $to): array
    {
        $q = EducationFee::whereHas('student', fn($s) => $s->where('institution_id', $institutionId));
        if ($from) $q->whereDate('created_at', '>=', $from);
        if ($to) $q->whereDate('created_at', '<=', $to);

        $totalAmount = (int) $q->clone()->sum('amount');
        $collected = (int) $q->clone()->where('status', 'paid')->sum('paid_amount');
        $pending = (int) $q->clone()->where('status', 'pending')->sum('amount');
        $collectionRate = $totalAmount > 0 ? round(($collected / $totalAmount) * 100, 2) : 0;

        return [
            'total_amount' => $totalAmount,
            'collected' => $collected,
            'pending' => $pending,
            'collection_rate' => $collectionRate,
            'institution_id' => $institutionId,
        ];
    }
}
