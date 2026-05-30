<?php

return [
    'name' => 'Bills Engine',
    'description' => 'Utility, telecom, and government bill payments',
    'categories' => [
        'telecom' => 'Telecom',
        'utility' => 'Utility',
        'government' => 'Government',
        'installment' => 'Installment',
    ],
    'status' => [
        'pending_inquiry' => 'Pending Inquiry',
        'inquired' => 'Inquired',
        'pending_payment' => 'Pending Payment',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],
    'errors' => [
        'already_paid' => 'Bill already paid: :id',
        'not_found' => 'Biller or account not found: :account',
        'payment_failed' => 'Biller rejected the payment: :reason',
        'inquiry_failed' => 'Bill inquiry failed: :reason',
        'invalid_amount' => 'Bill amount mismatch: expected :expected, got :actual',
        'account_format_invalid' => 'Account format invalid for biller: :account (expected :format)',
        'retry_exceeded' => 'Too many retries for account: :account. Wait 30 minutes.',
    ],
];
