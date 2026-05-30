<?php

declare(strict_types=1);

return [
    'name' => 'Payroll Disbursement',
    'description' => 'Employer registration, batch payroll, CSV upload, salary disbursement',
    'status' => [
        'pending' => 'Pending Approval',
        'active' => 'Active',
        'suspended' => 'Suspended',
        'terminated' => 'Terminated',
    ],
    'batch_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'partially_failed' => 'Partially Failed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],
    'disbursement_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],
    'errors' => [
        'employer_not_found' => 'Employer not found: :id',
        'employer_suspended' => 'Employer account is suspended',
        'batch_not_found' => 'Payroll batch not found: :id',
        'insufficient_balance' => 'Insufficient payroll balance: required :required, remaining :remaining',
        'validation_error' => 'Payroll validation failed: :reason',
        'csv_error' => 'CSV parse error: :reason',
        'process_error' => 'Processing error: :reason',
    ],
];
