<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\OpenFinance\Models\OpenFinanceApp;
use Modules\OpenFinance\Models\OpenFinanceConsent;
use Modules\OpenFinance\Models\OpenFinanceAccessToken;
use Modules\OpenFinance\Models\OpenFinanceWebhook;
use Modules\OpenFinance\Models\OpenFinanceWebhookDelivery;
use Modules\OpenFinance\Models\OpenFinancePayment;
use Modules\OpenFinance\Enums\ConsentStatus;
use Modules\OpenFinance\Exceptions\AppNotFoundException;
use Modules\OpenFinance\Exceptions\ConsentExpiredException;
use Modules\OpenFinance\Exceptions\InvalidScopeException;

final class OpenFinanceService
{
    private array $validScopes = [
        'accounts:read','accounts:write','transactions:read',
        'wallet:read','wallet:write','profile:read',
        'payments:initiate','webhooks:manage',
    ];

    public function registerApp(string $userId, string $name, string $redirectUris, array $scopes): OpenFinanceApp
    {
        foreach ($scopes as $s) { if (!in_array($s, $this->validScopes)) throw new InvalidScopeException($s); }
        return OpenFinanceApp::create([
            'id' => (string) Str::ulid(), 'user_id' => $userId, 'name' => $name,
            'redirect_uris' => $redirectUris, 'client_id' => 'cli_' . Str::random(32),
            'client_secret' => 'sec_' . Str::random(64), 'scopes' => $scopes, 'is_active' => true,
        ]);
    }

    public function createConsent(string $userId, string $appId, array $scopes, int $ttlDays = 90): OpenFinanceConsent
    {
        $app = OpenFinanceApp::find($appId);
        if (!$app) throw new AppNotFoundException($appId);
        foreach ($scopes as $s) { if (!in_array($s, $this->validScopes)) throw new InvalidScopeException($s); }
        return OpenFinanceConsent::create([
            'id' => (string) Str::ulid(), 'user_id' => $userId, 'app_id' => $appId,
            'granted_scopes' => $scopes, 'status' => ConsentStatus::ACTIVE->value,
            'expires_at' => now()->addDays($ttlDays),
        ]);
    }

    public function generateToken(string $consentId): OpenFinanceAccessToken
    {
        $consent = OpenFinanceConsent::findOrFail($consentId);
        if ($consent->expires_at->isPast() || $consent->status === ConsentStatus::REVOKED->value) {
            if ($consent->expires_at->isPast()) $consent->update(['status' => ConsentStatus::EXPIRED->value]);
            throw new ConsentExpiredException;
        }
        return OpenFinanceAccessToken::create([
            'id' => (string) Str::ulid(), 'consent_id' => $consentId,
            'token' => 'of_' . Str::random(64), 'scopes' => $consent->granted_scopes,
            'expires_at' => now()->addHours(2),
        ]);
    }

    public function revokeConsent(string $consentId): void
    {
        $consent = OpenFinanceConsent::findOrFail($consentId);
        $consent->update(['status' => ConsentStatus::REVOKED->value, 'revoked_at' => now()]);
    }

    // ─── Payment Initiation API ───

    public function initiatePayment(string $consentId, string $userId, string $paymentType, string $recipientId, int $amount, ?string $description = null, ?string $idempotencyKey = null): OpenFinancePayment
    {
        if ($idempotencyKey) {
            $existing = OpenFinancePayment::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) return $existing;
        }

        $payment = OpenFinancePayment::create([
            'id' => (string) Str::ulid(),
            'consent_id' => $consentId,
            'user_id' => $userId,
            'payment_type' => $paymentType,
            'recipient_id' => $recipientId,
            'amount' => $amount,
            'currency' => 'SYP',
            'description' => $description,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
        ]);

        // In production, this calls WalletService or CFE
        $payment->update(['status' => 'completed', 'completed_at' => now()]);

        $this->dispatchWebhookEvent($payment->consent->app_id ?? null, 'payment.completed', $payment->toArray());

        return $payment;
    }

    // ─── Account Information API ───

    public function listAccounts(string $userId): iterable
    {
        return \Modules\Ledger\Models\LedgerAccount::where('user_id', $userId)->get();
    }

    public function accountTransactions(string $accountId, int $perPage = 15): iterable
    {
        return \Modules\Ledger\Models\LedgerEntry::where('account_id', $accountId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // ─── Wallet API (B2B) ───

    public function createWallet(string $userId, string $name, string $currency = 'SYP'): \Modules\Wallet\Models\Wallet
    {
        return \Modules\Wallet\Models\Wallet::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'name' => $name,
            'currency' => $currency,
            'balance' => 0,
            'status' => 'active',
        ]);
    }

    // ─── Webhooks ───

    public function registerWebhook(string $appId, string $url, array $events): OpenFinanceWebhook
    {
        return OpenFinanceWebhook::create([
            'id' => (string) Str::ulid(),
            'app_id' => $appId,
            'url' => $url,
            'secret' => 'whsec_' . Str::random(32),
            'events' => $events,
            'is_active' => true,
        ]);
    }

    public function dispatchWebhookEvent(?string $appId, string $event, array $payload): void
    {
        if (!$appId) return;

        $webhooks = OpenFinanceWebhook::where('app_id', $appId)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            $delivery = OpenFinanceWebhookDelivery::create([
                'id' => (string) Str::ulid(),
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $payload,
                'status' => 'pending',
                'attempts' => 0,
            ]);

            try {
                $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);
                $response = Http::timeout(10)
                    ->withHeaders([
                        'X-Webhook-Signature' => $signature,
                        'X-Webhook-Event' => $event,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($webhook->url, $payload);

                $delivery->update([
                    'status' => $response->successful() ? 'delivered' : 'failed',
                    'attempts' => $delivery->attempts + 1,
                    'last_attempt_at' => now(),
                    'response_body' => $response->body(),
                    'succeeded_at' => $response->successful() ? now() : null,
                ]);
            } catch (\Throwable $e) {
                $delivery->update([
                    'status' => 'failed',
                    'attempts' => $delivery->attempts + 1,
                    'last_attempt_at' => now(),
                    'response_body' => $e->getMessage(),
                ]);
                Log::warning('Webhook delivery failed', ['webhook_id' => $webhook->id, 'error' => $e->getMessage()]);
            }
        }
    }

    public function listWebhooks(string $appId): iterable
    {
        return OpenFinanceWebhook::where('app_id', $appId)->get();
    }

    public function listWebhookDeliveries(string $webhookId, int $perPage = 25): iterable
    {
        return OpenFinanceWebhookDelivery::where('webhook_id', $webhookId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // ─── Rate limiting / Developer tiers ───

    public function getDeveloperTier(string $userId): string
    {
        $appCount = OpenFinanceApp::where('user_id', $userId)->count();
        $totalPayments = OpenFinancePayment::where('user_id', $userId)->count();

        if ($appCount >= 3 || $totalPayments >= 1000) return 'enterprise';
        if ($appCount >= 1 || $totalPayments >= 100) return 'business';
        return 'starter';
    }

    public function getRateLimit(string $tier): int
    {
        return match ($tier) {
            'enterprise' => 100,
            'business' => 30,
            default => 10,
        };
    }

    // ─── Basic helpers ───

    public function listApps(string $userId): iterable { return OpenFinanceApp::where('user_id', $userId)->get(); }
    public function listConsents(string $userId): iterable { return OpenFinanceConsent::where('user_id', $userId)->with('app')->get(); }
    public function listPayments(string $userId): iterable { return OpenFinancePayment::where('user_id', $userId)->orderByDesc('created_at')->get(); }
}
