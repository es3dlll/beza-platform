<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Modules\OpenFinance\Models\OpenFinanceApp;
use Modules\OpenFinance\Models\OpenFinanceConsent;
use Modules\OpenFinance\Models\OpenFinanceAccessToken;
use Modules\OpenFinance\Enums\ConsentStatus;
use Modules\OpenFinance\Exceptions\AppNotFoundException;
use Modules\OpenFinance\Exceptions\ConsentExpiredException;
use Modules\OpenFinance\Exceptions\InvalidScopeException;

class OpenFinanceService
{
    private array $validScopes = ['accounts:read','accounts:write','transactions:read','wallet:read','wallet:write','profile:read'];

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

    public function listApps(string $userId): iterable { return OpenFinanceApp::where('user_id', $userId)->get(); }

    public function listConsents(string $userId): iterable { return OpenFinanceConsent::where('user_id', $userId)->with('app')->get(); }
}
