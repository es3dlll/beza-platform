<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use Illuminate\Support\Facades\Log;

final class NotificationService
{
    public function alertFinanceTeam(string $subject, array $context = []): void
    {
        $message = "[FINANCE ALERT] {$subject}" . (!empty($context) ? ' | ' . json_encode($context) : '');

        Log::channel('cfe')->warning($message);

        if ($slackWebhook = config('services.slack.finance_webhook')) {
            try {
                $this->sendSlack($slackWebhook, $subject, $context);
            } catch (\Throwable $e) {
                Log::error('NotificationService: Slack notification failed', ['error' => $e->getMessage()]);
            }
        }
    }

    public function alertOpsTeam(string $subject, array $context = []): void
    {
        $message = "[OPS ALERT] {$subject}" . (!empty($context) ? ' | ' . json_encode($context) : '');

        Log::channel('cfe')->error($message);

        if ($pagerDutyKey = config('services.pagerduty.integration_key')) {
            try {
                $this->sendPagerDuty($pagerDutyKey, $subject, $context);
            } catch (\Throwable $e) {
                Log::error('NotificationService: PagerDuty notification failed', ['error' => $e->getMessage()]);
            }
        }
    }

    public function sendCBSReportReady(string $reportCode, array $summary = []): void
    {
        $this->alertFinanceTeam("CBS Report Ready: {$reportCode}", [
            'report_code' => $reportCode,
            'summary' => $summary,
        ]);
    }

    private function sendSlack(string $webhook, string $subject, array $context): void
    {
        $payload = json_encode([
            'text' => $subject,
            'attachments' => [[
                'color' => ($context['severity'] ?? 'warning') === 'critical' ? 'danger' : 'warning',
                'fields' => collect($context)->map(fn ($v, $k) => [
                    'title' => $k,
                    'value' => is_scalar($v) ? (string) $v : json_encode($v),
                    'short' => true,
                ])->values()->toArray(),
            ]],
        ]);

        $ch = curl_init($webhook);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function sendPagerDuty(string $integrationKey, string $subject, array $context): void
    {
        $payload = json_encode([
            'routing_key' => $integrationKey,
            'event_action' => 'trigger',
            'payload' => [
                'summary' => $subject,
                'severity' => $context['severity'] ?? 'error',
                'source' => 'beza-ledger-reconciliation',
                'custom_details' => $context,
            ],
        ]);

        $ch = curl_init('https://events.pagerduty.com/v2/enqueue');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
