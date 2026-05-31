<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AlertingService
{
    public function alertCriticalDiscrepancy(array $context): void
    {
        $this->sendToPagerDuty([
            'routing_key' => config('alerting.pagerduty_key'),
            'event_action' => 'trigger',
            'payload' => [
                'summary' => "فارق حرج في التسوية: {$context['account_code']}",
                'severity' => 'critical',
                'source' => 'beza-ledger',
                'component' => 'reconciliation',
                'custom_details' => $context,
            ],
        ]);

        $this->sendToSlack([
            'channel' => config('alerting.slack_channel_critical'),
            'text' => "🚨 فارق حرج في التسوية",
            'attachments' => [[
                'color' => 'danger',
                'fields' => [
                    ['title' => 'الحساب', 'value' => $context['account_code'], 'short' => true],
                    ['title' => 'المبلغ', 'value' => $context['difference_formatted'], 'short' => true],
                    ['title' => 'النوع', 'value' => $context['discrepancy_type'], 'short' => true],
                    ['title' => 'الوقت', 'value' => now()->format('Y-m-d H:i:s'), 'short' => true],
                ],
                'actions' => [[
                    'type' => 'button',
                    'text' => 'عرض التقرير',
                    'url' => $context['report_url'] ?? '#',
                    'style' => 'primary',
                ]],
            ]],
        ]);
    }

    public function alertCBSSubmissionFailed(array $context): void
    {
        $this->sendToSlack([
            'channel' => config('alerting.slack_channel_compliance'),
            'text' => "⚠️ فشل إرسال تقرير للمصرف المركزي",
            'attachments' => [[
                'color' => 'warning',
                'fields' => [
                    ['title' => 'معرف التقرير', 'value' => $context['report_id'], 'short' => true],
                    ['title' => 'النوع', 'value' => $context['report_type'], 'short' => true],
                    ['title' => 'السبب', 'value' => $context['reason'] ?? 'غير معروف', 'short' => false],
                ],
            ]],
        ]);
    }

    private function sendToPagerDuty(array $payload): void
    {
        if (!config('alerting.pagerduty_enabled', false)) {
            return;
        }

        try {
            Http::post('https://events.pagerduty.com/v2/enqueue', $payload);
        } catch (\Throwable $e) {
            Log::error('PAGERDUTY_SEND_FAILED', ['error' => $e->getMessage()]);
        }
    }

    private function sendToSlack(array $payload): void
    {
        if (!config('alerting.slack_enabled', false)) {
            return;
        }

        try {
            Http::post(config('alerting.slack_webhook_url'), $payload);
        } catch (\Throwable $e) {
            Log::error('SLACK_SEND_FAILED', ['error' => $e->getMessage()]);
        }
    }
}
