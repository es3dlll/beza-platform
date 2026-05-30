<?php

declare(strict_types=1);

return [
    'name' => 'Loyalty & Rewards',
    'description' => 'Points, tiers, cashback, and rewards',
    'tiers' => [
        'bronze' => 'Bronze',
        'silver' => 'Silver',
        'gold' => 'Gold',
        'platinum' => 'Platinum',
    ],
    'txn_types' => [
        'earned' => 'Earned',
        'redeemed' => 'Redeemed',
        'expired' => 'Expired',
        'adjusted' => 'Adjusted',
    ],
    'errors' => [
        'insufficient_points' => 'Insufficient points: required :required, available :available',
        'points_expired' => 'Points have expired',
        'reward_not_found' => 'Reward not found: :id',
    ],
];
