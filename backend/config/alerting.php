<?php

return [
    'pagerduty_enabled' => env('ALERTING_PAGERDUTY_ENABLED', false),
    'pagerduty_key' => env('ALERTING_PAGERDUTY_KEY', ''),
    'slack_enabled' => env('ALERTING_SLACK_ENABLED', false),
    'slack_webhook_url' => env('ALERTING_SLACK_WEBHOOK_URL', ''),
    'slack_channel_critical' => env('ALERTING_SLACK_CHANNEL_CRITICAL', '#finance-critical'),
    'slack_channel_compliance' => env('ALERTING_SLACK_CHANNEL_COMPLIANCE', '#compliance-alerts'),
];
