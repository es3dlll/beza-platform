import { describe, it, expect } from 'vitest';
import { Money, Currency } from './money';

describe('Money', () => {
  it('can create from fils', () => {
    const money = Money.fromFils(1500);
    expect(money.fils).toBe(1500);
    expect(money.currency).toBe(Currency.SYP);
  });

  it('can create from SYP', () => {
    const money = Money.fromSYP(1.5);
    expect(money.fils).toBe(1500);
  });

  it('throws for negative amount', () => {
    expect(() => Money.fromFils(-100)).toThrow('المبلغ لا يمكن أن يكون سالباً');
  });

  it('can add two amounts', () => {
    const result = Money.fromFils(1000).add(Money.fromFils(2000));
    expect(result.fils).toBe(3000);
  });

  it('can subtract amounts', () => {
    const result = Money.fromFils(5000).subtract(Money.fromFils(2000));
    expect(result.fils).toBe(3000);
  });

  it('throws on subtract resulting in negative', () => {
    expect(() => Money.fromFils(1000).subtract(Money.fromFils(2000))).toThrow();
  });

  it('throws on currency mismatch', () => {
    const syp = Money.fromFils(1000, Currency.SYP);
    const usd = Money.fromFils(1000, Currency.USD);
    expect(() => syp.add(usd)).toThrow();
  });

  it('formats with SYP symbol', () => {
    const formatted = Money.fromFils(1500500).format();
    expect(formatted).toContain('ل.س');
  });

  it('can compare amounts', () => {
    const small = Money.fromFils(500);
    const large = Money.fromFils(1000);
    expect(large.isGreaterThan(small)).toBe(true);
    expect(small.isLessThan(large)).toBe(true);
    expect(small.equals(Money.fromFils(500))).toBe(true);
  });

  it('can multiply', () => {
    const result = Money.fromFils(300).multiply(3);
    expect(result.fils).toBe(900);
  });
});
