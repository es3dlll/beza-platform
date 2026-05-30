<?php

declare(strict_types=1);

namespace Modules\Payroll\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Payroll\Exceptions\PayrollBatchNotFoundException;
use Modules\Payroll\Exceptions\EmployerNotFoundException;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\Employer;
use Modules\Payroll\Models\PayrollDisbursement;
use Modules\Payroll\Repositories\PayrollBatchRepository;
use Modules\Payroll\Repositories\EmployeeRecordRepository;

final class SalaryCertificateService
{
    public function __construct(
        private readonly PayrollBatchRepository $batchRepository,
        private readonly EmployeeRecordRepository $employeeRepository,
    ) {}

    public function generate(string $batchId, string $employeePhone): \Illuminate\Http\Response
    {
        $batch = $this->findBatchOrFail($batchId);
        $employer = Employer::findOrFail($batch->employer_id);

        $disbursement = PayrollDisbursement::where('payroll_batch_id', $batchId)
            ->where('employee_phone', $employeePhone)
            ->firstOrFail();

        $record = $this->employeeRepository->findByPhone($batch->employer_id, $employeePhone);

        $data = [
            'certificate_no' => 'SC-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'issue_date' => now()->format('Y-m-d'),
            'employer' => $employer->company_name,
            'employer_ar' => $employer->company_name_ar,
            'employee_name' => $disbursement->employee_name,
            'employee_phone' => $disbursement->employee_phone,
            'national_id' => $record?->national_id ?? '—',
            'job_title' => $record?->job_title ?? '—',
            'salary' => number_format($disbursement->amount, 0),
            'salary_in_words' => $this->numberToArabicWords($disbursement->amount),
            'period' => $batch->period_month,
            'currency' => 'SYP',
            'batch_reference' => $batch->batch_reference,
            'status' => $disbursement->status,
            'processed_at' => $disbursement->processed_at?->format('Y-m-d H:i') ?? '—',
        ];

        $pdf = Pdf::loadView('payroll::salary-certificate', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("salary-certificate-{$data['certificate_no']}.pdf");
    }

    private function numberToArabicWords(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        $units = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مئة', 'مئتان', 'ثلاثمئة', 'أربعمئة', 'خمسمئة', 'ستمئة', 'سبعمئة', 'ثمانمئة', 'تسعمئة'];

        $result = '';

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $result .= ($thousands === 1 ? 'ألف' : ($thousands === 2 ? 'ألفان' : $units[$thousands] . ' آلاف')) . ' ';
            $number %= 1000;
        }

        if ($number >= 100) {
            $h = intdiv($number, 100);
            $result .= $hundreds[$h] . ' ';
            $number %= 100;
        }

        if ($number >= 20) {
            $t = intdiv($number, 10);
            $result .= $tens[$t] . ' ';
            $number %= 10;
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10] . ' ';
            $number = 0;
        }

        if ($number > 0) {
            $result .= $units[$number] . ' ';
        }

        return trim($result) . ' ليرة سورية فقط لا غير';
    }

    private function findBatchOrFail(string $id): PayrollBatch
    {
        $batch = $this->batchRepository->findById($id);
        if (!$batch) {
            throw new PayrollBatchNotFoundException($id);
        }
        return $batch;
    }
}
