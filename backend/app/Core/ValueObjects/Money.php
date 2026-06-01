<?php

namespace App\Core\ValueObjects;

class Money
{
    public function __construct(
        private readonly int $amount,  // بالفلس (bigint)
        private readonly Currency $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('المبلغ لا يمكن أن يكون سالباً');
        }
    }

    public static function fromFloat(float $amount, string $currencyCode): self
    {
        $currency = Currency::fromCode($currencyCode);
        $fils = (int) round($amount * (10 ** $currency->decimals()));

        return new self($fils, $currency);
    }

    public static function fromFils(int $fils, string $currencyCode): self
    {
        return new self($fils, Currency::fromCode($currencyCode));
    }

    public function fils(): int
    {
        return $this->amount;
    }

    public function toFloat(): float
    {
        return $this->amount / (10 ** $this->currency->decimals());
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        if ($this->currency->code() !== $other->currency->code()) {
            throw new \InvalidArgumentException('لا يمكن جمع عملتين مختلفتين');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency->code() !== $other->currency->code()) {
            throw new \InvalidArgumentException('لا يمكن طرح عملتين مختلفتين');
        }

        if ($this->amount < $other->amount) {
            throw new \InvalidArgumentException('الرصيد غير كافٍ');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function __toString(): string
    {
        return number_format($this->toFloat(), $this->currency->decimals()) . ' ' . $this->currency->symbol();
    }
}
