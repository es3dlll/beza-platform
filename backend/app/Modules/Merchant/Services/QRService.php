<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Modules\Merchant\ValueObjects\QRToken;

final class QRService
{
    private const HMAC_SECRET_KEY = 'merchant-qr-hmac-key';

    public function generate(string $invoiceId, string $merchantId, int $amount): QRToken
    {
        return QRToken::create($invoiceId, $merchantId, $amount, self::HMAC_SECRET_KEY);
    }

    public function validate(string $token, string $invoiceId): bool
    {
        try {
            $qrToken = QRToken::fromString($token, self::HMAC_SECRET_KEY);

            if ($qrToken->invoiceId() !== $invoiceId) {
                return false;
            }

            return $qrToken->isValid(self::HMAC_SECRET_KEY);
        } catch (\Throwable) {
            return false;
        }
    }
}
