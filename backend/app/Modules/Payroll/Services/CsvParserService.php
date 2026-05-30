<?php

declare(strict_types=1);

namespace Modules\Payroll\Services;

use Modules\Payroll\Exceptions\PayrollValidationException;

class CsvParserService
{
    private const REQUIRED_HEADERS = ['employee_name', 'phone', 'amount'];

    public function parse(string $csvContent, string $delimiter = ','): array
    {
        $lines = explode("\n", trim($csvContent));
        if (count($lines) < 2) {
            throw new PayrollValidationException('CSV must contain a header row and at least one data row');
        }

        $headers = str_getcsv($lines[0], $delimiter);
        $headers = array_map('trim', $headers);
        $this->validateHeaders($headers);

        $employees = [];
        $lineNumber = 1;

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                continue;
            }
            $lineNumber++;

            $row = str_getcsv($line, $delimiter);
            if (count($row) !== count($headers)) {
                throw new PayrollValidationException("Line {$lineNumber}: column count mismatch");
            }

            $data = array_combine($headers, $row);
            $this->validateRow($data, $lineNumber);
            $employees[] = $data;
        }

        return $employees;
    }

    private function validateHeaders(array $headers): void
    {
        foreach (self::REQUIRED_HEADERS as $required) {
            if (!in_array($required, $headers, true)) {
                throw new PayrollValidationException("Missing required CSV column: {$required}");
            }
        }
    }

    private function validateRow(array $row, int $lineNumber): void
    {
        if (empty(trim($row['employee_name'] ?? ''))) {
            throw new PayrollValidationException("Line {$lineNumber}: employee_name is required");
        }

        $phone = trim($row['phone'] ?? '');
        if (empty($phone)) {
            throw new PayrollValidationException("Line {$lineNumber}: phone is required");
        }

        $amount = trim($row['amount'] ?? '');
        if (!is_numeric($amount) || (int) $amount <= 0) {
            throw new PayrollValidationException("Line {$lineNumber}: amount must be a positive number");
        }
    }
}
