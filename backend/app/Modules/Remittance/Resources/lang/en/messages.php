<?php

declare(strict_types=1);

return [
    'name' => 'Remittance Engine',
    'description' => 'Cross-border remittance management for inbound diaspora and outbound transfers',
    'purpose_codes' => [
        'FAMILY_SUPPORT' => 'Family Support',
        'SALARY' => 'Salary',
        'EDUCATION' => 'Education',
        'MEDICAL' => 'Medical',
        'SAVINGS' => 'Savings',
        'INVESTMENT' => 'Investment',
        'BUSINESS' => 'Business',
        'CHARITY' => 'Charity',
        'OTHER' => 'Other',
    ],
    'payout_methods' => [
        'wallet' => 'Wallet',
        'agent' => 'Agent Cash Pickup',
        'bank' => 'Bank Account',
    ],
    'status' => [
        'pending' => 'Pending',
        'screening' => 'Under Compliance Screening',
        'screening_failed' => 'Screening Rejected',
        'quoted' => 'Quoted',
        'awaiting_payment' => 'Awaiting Payment',
        'paid_in' => 'Paid In',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'expired' => 'Expired',
    ],
    'errors' => [
        'corridor_unavailable' => 'Remittance corridor :corridor is unavailable or inactive',
        'sanctions_hit' => 'Transaction flagged by sanctions screening',
        'limit_exceeded' => 'Remittance limit exceeded: :limit_type (attempted :amount, limit :limit)',
        'beneficiary_not_found' => 'Beneficiary :beneficiary not found',
        'purpose_required' => 'A valid purpose code is required for this remittance',
        'source_of_funds_required' => 'Source of funds declaration is required',
        'receiving_country_blocked' => 'Receiving country :country is blocked for remittances',
        'beneficiary_kyc_incomplete' => 'Beneficiary :beneficiary has incomplete KYC verification',
    ],
    'success' => [
        'created' => 'Remittance order created successfully',
        'screened' => 'Remittance screening completed',
        'quoted' => 'Remittance quote generated',
        'paid_in' => 'Payment confirmed, processing remittance',
        'completed' => 'Remittance completed successfully',
        'refunded' => 'Remittance refunded',
    ],
];
