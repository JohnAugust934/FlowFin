<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_converts_brazilian_currency_string_to_cents(): void
    {
        $this->assertSame(123456, Money::toCents('R$ 1.234,56'));
        $this->assertSame(123456, Money::toCents('1.234,56'));
        $this->assertSame(1234, Money::toCents('12,34'));
        $this->assertSame(1000, Money::toCents('10,00'));
        $this->assertSame(500, Money::toCents('5'));
    }

    public function test_converts_dot_decimal_string_to_cents(): void
    {
        $this->assertSame(1234, Money::toCents('12.34'));
    }

    public function test_passes_through_integer_values(): void
    {
        $this->assertSame(1234, Money::toCents(1234));
    }

    public function test_returns_null_for_empty_input(): void
    {
        $this->assertNull(Money::toCents(null));
        $this->assertNull(Money::toCents(''));
        $this->assertNull(Money::toCents('   '));
        $this->assertNull(Money::toCents('R$'));
    }

    public function test_formats_cents_to_brazilian_number(): void
    {
        $this->assertSame('1.234,56', Money::format(123456));
        $this->assertSame('0,00', Money::format(0));
        $this->assertSame('', Money::format(null));
    }

    public function test_formats_cents_with_currency_prefix(): void
    {
        $this->assertSame('R$ 1.234,56', Money::formatBRL(123456));
        $this->assertSame('', Money::formatBRL(null));
    }
}
