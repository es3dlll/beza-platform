export enum Currency {
  SYP = 'SYP',
  USD = 'USD',
  EUR = 'EUR',
  TRY = 'TRY',
}

export class Money {
  private readonly _fils: number;
  readonly currency: Currency;

  constructor(fils: number, currency: Currency = Currency.SYP) {
    if (fils < 0) throw new Error('المبلغ لا يمكن أن يكون سالباً');
    this._fils = fils;
    this.currency = currency;
  }

  static fromFils(fils: number, currency: Currency = Currency.SYP): Money {
    return new Money(fils, currency);
  }

  static fromSYP(amount: number, currency: Currency = Currency.SYP): Money {
    return new Money(Math.round(amount * 1000), currency);
  }

  get fils(): number {
    return this._fils;
  }

  get syp(): number {
    return this._fils / 1000;
  }

  add(other: Money): Money {
    this.assertSameCurrency(other);
    return new Money(this._fils + other._fils, this.currency);
  }

  subtract(other: Money): Money {
    this.assertSameCurrency(other);
    return new Money(this._fils - other._fils, this.currency);
  }

  multiply(multiplier: number): Money {
    return new Money(Math.round(this._fils * multiplier), this.currency);
  }

  isGreaterThan(other: Money): boolean {
    this.assertSameCurrency(other);
    return this._fils > other._fils;
  }

  isLessThan(other: Money): boolean {
    this.assertSameCurrency(other);
    return this._fils < other._fils;
  }

  equals(other: Money): boolean {
    return this._fils === other._fils && this.currency === other.currency;
  }

  format(): string {
    const value = this.syp.toLocaleString('ar-SA', {
      minimumFractionDigits: 3,
      maximumFractionDigits: 3,
    });
    const symbols: Record<Currency, string> = {
      [Currency.SYP]: 'ل.س',
      [Currency.USD]: '$',
      [Currency.EUR]: '€',
      [Currency.TRY]: '₺',
    };
    return `${value} ${symbols[this.currency]}`;
  }

  private assertSameCurrency(other: Money): void {
    if (this.currency !== other.currency) {
      throw new Error(
        `عملة غير متطابقة: متوقع ${this.currency}، مستلم ${other.currency}`,
      );
    }
  }
}
