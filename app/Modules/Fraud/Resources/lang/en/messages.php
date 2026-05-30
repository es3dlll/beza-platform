<?php

return [
    'name' => 'Fraud Engine',
    'description' => 'Fraud detection, blacklist management, and case review',
    'decision' => [
        'allow' => 'Allowed',
        'flag' => 'Flagged',
        'block' => 'Blocked',
        'review' => 'Review Required',
    ],
    'severity' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ],
    'case_status' => [
        'open' => 'Open',
        'under_review' => 'Under Review',
        'resolved' => 'Resolved',
        'dismissed' => 'Dismissed',
    ],
    'errors' => [
        'transaction_blocked' => 'Transaction automatically blocked by fraud detection',
        'review_required' => 'Transaction flagged for manual fraud review',
        'device_blocked' => 'Device is blacklisted due to previous fraud activity',
        'ip_blocked' => 'IP address is associated with fraudulent activity',
        'rapid_successive' => 'Multiple rapid transactions detected; rate-limited as precaution',
        'case_not_found' => 'Fraud case not found: :id',
    ],
];
