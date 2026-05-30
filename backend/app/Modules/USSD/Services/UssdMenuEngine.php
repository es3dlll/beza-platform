<?php

declare(strict_types=1);

namespace Modules\USSD\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UssdMenuEngine
{
    private const SESSION_TTL = 30; // seconds
    private const MAX_DEPTH = 3;
    private const MAX_SESSIONS_PER_HOUR = 10;

    private const MENU_MAIN = <<<'MENU'
مرحباً بك في بزة
1. الرصيد
2. آخر الحركات
3. أقرب وكيل
4. تغيير الرقم السري
5. اللغة
MENU;

    private const MENU_LANGUAGE = <<<'MENU'
اختر اللغة:
1. العربية
2. English
MENU;

    private const MENU_CONFIRM_PIN = 'أدخل الرقم السري الجديد (6 أرقام):';
    private const MENU_SUCCESS_PIN = '✅ تم تغيير الرقم السري بنجاح';
    private const MENU_FAILURE = '❌ عذراً، حدث خطأ. الرجاء المحاولة لاحقاً';
    private const MENU_TIMEOUT = 'انتهت المهلة. الرجاء الاتصال مرة أخرى';
    private const MENU_EXIT = 'شكراً لاستخدامك بزة';

    private const SESSION_PREFIX = 'ussd_session:';
    private const RATE_LIMIT_PREFIX = 'ussd_rate:';

    public function handle(string $sessionId, string $msisdn, string $text): array
    {
        if (!$this->checkRateLimit($msisdn)) {
            return $this->endResponse('⚠️ تجاوزت حد الاستخدام. الرجاء المحاولة بعد ساعة.');
        }

        $session = $this->getSession($sessionId);
        $menu = $session['menu'] ?? [];
        $lang = $session['lang'] ?? 'ar';
        $depth = count($menu);

        if ($text === '' || rtrim($text, '#') === '*123') {
            return $this->menuResponse(self::MENU_MAIN, $sessionId, 1);
        }

        // Parse input — strip trailing # used in USSD protocol
        $input = rtrim(trim($text), '#');
        $parts = explode('*', $input);
        $currentInput = end($parts);

        // Handle language toggle *123*2#
        if (str_starts_with($input, '*123*2')) {
            $subInput = str_replace('*123*2', '', $input);
            $subInput = rtrim($subInput, '#*');
            if ($subInput === '') {
                return $this->menuResponse(self::MENU_LANGUAGE, $sessionId, 1);
            }
            $subInput = ltrim($subInput, '*');
            if ($subInput === '1') {
                $session['lang'] = 'ar';
                $this->saveSession($sessionId, $session);
                return $this->endResponse('✅ تم تغيير اللغة إلى العربية');
            }
            if ($subInput === '2') {
                $session['lang'] = 'en';
                $this->saveSession($sessionId, $session);
                return $this->endResponse('✅ Language changed to English');
            }
        }

        if ($depth >= self::MAX_DEPTH) {
            return $this->endResponse(self::MENU_EXIT);
        }

        // Main menu options
        if ($depth === 0) {
            return match ($currentInput) {
                '1' => $this->handleBalance($msisdn, $sessionId),
                '2' => $this->handleMiniStatement($msisdn, $sessionId),
                '3' => $this->handleAgentLocator($msisdn, $sessionId),
                '4' => $this->menuResponse(self::MENU_CONFIRM_PIN, $sessionId, 1, ['action' => 'change_pin']),
                '5' => $this->menuResponse(self::MENU_LANGUAGE, $sessionId, 1),
                default => $this->menuResponse('⚠️ اختيار غير صحيح. الرجاء المحاولة مرة أخرى.', $sessionId, 0),
            };
        }

        // PIN change flow
        $lastAction = $session['action'] ?? null;
        if ($lastAction === 'change_pin') {
            if (strlen($currentInput) === 6 && ctype_digit($currentInput)) {
                try {
                    $this->changePin($msisdn, $currentInput);
                    return $this->endResponse(self::MENU_SUCCESS_PIN);
                } catch (\Throwable $e) {
                    Log::error('USSD PIN change failed', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);
                    return $this->endResponse(self::MENU_FAILURE);
                }
            }
            return $this->menuResponse('❌ الرقم السري يجب أن يكون 6 أرقام. حاول مرة أخرى:', $sessionId, 1, ['action' => 'change_pin']);
        }

        return $this->endResponse(self::MENU_EXIT);
    }

    private function handleBalance(string $msisdn, string $sessionId): array
    {
        try {
            $balance = $this->getWalletBalance($msisdn);
            $msg = "💰 رصيدك الحالي:\n{$balance['balance']} {$balance['currency']}";
            return $this->endResponse($msg);
        } catch (\Throwable $e) {
            Log::error('USSD balance failed', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);
            return $this->endResponse(self::MENU_FAILURE);
        }
    }

    private function handleMiniStatement(string $msisdn, string $sessionId): array
    {
        try {
            $txns = $this->getRecentTransactions($msisdn, 5);
            if (empty($txns)) {
                return $this->endResponse('📭 لا توجد حركات حديثة');
            }

            $lines = ['📋 آخر 5 حركات:'];
            foreach ($txns as $i => $tx) {
                $sign = $tx['type'] === 'credit' ? '+' : '-';
                $lines[] = ($i + 1) . ". {$sign}{$tx['amount']} {$tx['currency']} - {$tx['description']}";
            }
            return $this->endResponse(implode("\n", $lines));
        } catch (\Throwable $e) {
            Log::error('USSD mini-statement failed', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);
            return $this->endResponse(self::MENU_FAILURE);
        }
    }

    private function handleAgentLocator(string $msisdn, string $sessionId): array
    {
        try {
            $agents = $this->findNearestAgents($msisdn, 3);
            if (empty($agents)) {
                return $this->endResponse('📍 لا يوجد وكلاء قريبون حالياً');
            }

            $lines = ['📍 أقرب 3 وكلاء:'];
            foreach ($agents as $i => $a) {
                $lines[] = ($i + 1) . ". {$a['shop_name']} - {$a['governorate']}\n   📞 {$a['phone']}";
            }

            $msg = implode("\n", $lines);
            return $this->endResponse($msg);
        } catch (\Throwable $e) {
            Log::error('USSD agent locator failed', ['msisdn' => $msisdn, 'error' => $e->getMessage()]);
            return $this->endResponse(self::MENU_FAILURE);
        }
    }

    private function menuResponse(string $text, string $sessionId, int $depth, array $extra = []): array
    {
        $menu = $this->getSession($sessionId)['menu'] ?? [];
        $menu[] = $depth;
        $this->saveSession($sessionId, array_merge($extra, ['menu' => $menu]));
        return [
            'session_id' => $sessionId,
            'action' => 'menu',
            'text' => $text,
            'type' => 'response',
        ];
    }

    private function endResponse(string $text): array
    {
        return [
            'session_id' => '',
            'action' => 'end',
            'text' => $text,
            'type' => 'response',
        ];
    }

    private function getSession(string $sessionId): ?array
    {
        $data = Cache::get(self::SESSION_PREFIX . $sessionId);
        return is_array($data) ? $data : null;
    }

    private function saveSession(string $sessionId, array $data): void
    {
        Cache::put(self::SESSION_PREFIX . $sessionId, $data, now()->addSeconds(self::SESSION_TTL));
    }

    private function checkRateLimit(string $msisdn): bool
    {
        $key = self::RATE_LIMIT_PREFIX . $msisdn;
        $count = (int) Cache::get($key, 0);
        if ($count >= self::MAX_SESSIONS_PER_HOUR) {
            return false;
        }
        Cache::put($key, $count + 1, now()->addHour());
        return true;
    }

    private function getWalletBalance(string $msisdn): array
    {
        $user = \App\Models\User::where('phone', $msisdn)->first();
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        $wallet = \Modules\Wallet\Models\Wallet::where('user_id', $user->id)
            ->where('currency', 'SYP')
            ->first();
        if (!$wallet) {
            return ['balance' => 0, 'currency' => 'SYP'];
        }
        return ['balance' => $wallet->balance, 'currency' => 'SYP'];
    }

    private function getRecentTransactions(string $msisdn, int $limit): array
    {
        $user = \App\Models\User::where('phone', $msisdn)->first();
        if (!$user) {
            return [];
        }
        $wallet = \Modules\Wallet\Models\Wallet::where('user_id', $user->id)
            ->where('currency', 'SYP')
            ->first();
        if (!$wallet) {
            return [];
        }
        return \Modules\Wallet\Models\WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn($t) => [
                'type' => $t->type,
                'amount' => $t->amount,
                'currency' => 'SYP',
                'description' => $t->description ?? '-',
            ])
            ->toArray();
    }

    private function findNearestAgents(string $msisdn, int $limit): array
    {
        return \Modules\Agent\Models\Agent::where('status', 'approved')
            ->take($limit)
            ->get(['shop_name', 'governorate', 'phone'])
            ->toArray();
    }

    private function changePin(string $msisdn, string $newPin): void
    {
        $user = \App\Models\User::where('phone', $msisdn)->first();
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        \Modules\Auth\Services\PinService::changePin($user, $newPin);
    }
}
