<?php

declare(strict_types=1);

namespace App\Modules\Merchant\ValueObjects;

final readonly class QRToken
{
    private const DEFAULT_TTL = 600;

    public function __construct(
        private string $token,
        private string $invoiceId,
        private int $expiresAt,
        private string $hmac,
    ) {}

    public static function create(string $invoiceId, string $merchantId, int $amount, string $secret): self
    {
        $expiresAt = time() + self::DEFAULT_TTL;
        $payload = "{$invoiceId}:{$merchantId}:{$amount}:{$expiresAt}";
        $hmac = hash_hmac('sha256', $payload, $secret);

        return new self(
            token: base64_encode($payload),
            invoiceId: $invoiceId,
            expiresAt: $expiresAt,
            hmac: $hmac,
        );
    }

    public static function fromString(string $token, string $secret): self
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            throw new \App\Modules\Merchant\Exceptions\QRExpiredException('Invalid QR token encoding');
        }

        $parts = explode(':', $decoded);
        if (count($parts) !== 4) {
            throw new \App\Modules\Merchant\Exceptions\QRExpiredException('Malformed QR token');
        }

        [$invoiceId, $merchantId, $amount, $expiresAt] = $parts;
        $expectedHmac = hash_hmac('sha256', $decoded, $secret);

        return new self(
            token: $token,
            invoiceId: $invoiceId,
            expiresAt: (int) $expiresAt,
            hmac: $expectedHmac,
        );
    }

    public function isValid(string $secret): bool
    {
        if (time() > $this->expiresAt) {
            return false;
        }

        $decoded = base64_decode($this->token, true);
        $expected = hash_hmac('sha256', $decoded, $secret);

        return hash_equals($expected, $this->hmac);
    }

    public function token(): string { return $this->token; }
    public function invoiceId(): string { return $this->invoiceId; }
    public function expiresAt(): int { return $this->expiresAt; }
    public function remainingSeconds(): int { return max(0, $this->expiresAt - time()); }
}
