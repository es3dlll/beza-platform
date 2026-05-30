<?php

return [
    'name' => 'Savings Engine',
    'description' => 'Goal-based savings, auto-sweep, profit distribution',
    'goal_status' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'transaction_types' => [
        'contribution' => 'Contribution',
        'withdrawal' => 'Withdrawal',
        'profit' => 'Profit',
        'penalty' => 'Early Withdrawal Penalty',
        'auto_sweep' => 'Auto-Sweep',
    ],
    'errors' => [
        'goal_not_found' => 'Savings goal not found: :id',
        'goal_completed' => 'Savings goal is already completed',
        'insufficient_balance' => 'Insufficient savings balance',
        'invalid_amount' => 'Invalid contribution amount: minimum 1,000 SYP',
        'early_withdrawal_penalty' => 'Early withdrawal penalty of :penalty SYP applies',
    ],
];
