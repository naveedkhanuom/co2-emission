<?php

namespace Tests\Unit;

use App\Services\UnitConverter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    private UnitConverter $c;

    protected function setUp(): void
    {
        parent::setUp();
        $this->c = new UnitConverter();
    }

    public function test_energy_conversions(): void
    {
        $this->assertEqualsWithDelta(1000, $this->c->convert(1, 'MWh', 'kWh'), 1e-6);
        $this->assertEqualsWithDelta(1, $this->c->convert(1000, 'kWh', 'MWh'), 1e-6);
        $this->assertEqualsWithDelta(1_000_000, $this->c->convert(1, 'GWh', 'kWh'), 1e-6);
        $this->assertEqualsWithDelta(0.277778, $this->c->convert(1, 'MJ', 'kWh'), 1e-5);
    }

    public function test_mass_conversions(): void
    {
        $this->assertEqualsWithDelta(1000, $this->c->convert(1, 't', 'kg'), 1e-6);
        $this->assertEqualsWithDelta(1, $this->c->convert(1000, 'kg', 't'), 1e-6);
        $this->assertEqualsWithDelta(0.453592, $this->c->convert(1, 'lb', 'kg'), 1e-4);
    }

    public function test_volume_conversions(): void
    {
        $this->assertEqualsWithDelta(1000, $this->c->convert(1, 'm3', 'L'), 1e-6);
        $this->assertEqualsWithDelta(3.785412, $this->c->convert(1, 'gal_us', 'L'), 1e-4);
    }

    public function test_distance_conversions(): void
    {
        $this->assertEqualsWithDelta(1.609344, $this->c->convert(1, 'mi', 'km'), 1e-5);
        $this->assertEqualsWithDelta(1000, $this->c->convert(1, 'km', 'm'), 1e-6);
    }

    public function test_same_unit_returns_same_value(): void
    {
        $this->assertSame(42.0, $this->c->convert(42, 'kWh', 'kWh'));
    }

    public function test_free_text_aliases_are_understood(): void
    {
        // Users type all sorts of things; these should all normalise.
        $this->assertEqualsWithDelta(1000, $this->c->convert(1, 'tonnes', 'kg'), 1e-6);
        $this->assertEqualsWithDelta(1000, $this->c->convert(1, 'm³', 'litre'), 1e-6);
        $this->assertEqualsWithDelta(1000, $this->c->convert(1, 'MWH', 'KWH'), 1e-6);
    }

    public function test_incompatible_units_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->c->convert(1, 'kWh', 'kg');
    }

    public function test_unknown_unit_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->c->convert(1, 'bananas', 'kg');
    }

    public function test_try_convert_returns_null_on_mismatch(): void
    {
        $this->assertNull($this->c->tryConvert(1, 'kWh', 'kg'));
        $this->assertNull($this->c->tryConvert(1, 'kWh', 'bananas'));
        $this->assertEqualsWithDelta(1000, $this->c->tryConvert(1, 'MWh', 'kWh'), 1e-6);
    }

    public function test_compatibility_and_dimension_detection(): void
    {
        $this->assertTrue($this->c->areCompatible('MWh', 'kWh'));
        $this->assertFalse($this->c->areCompatible('MWh', 'kg'));
        $this->assertSame('energy', $this->c->dimensionOf('kWh'));
        $this->assertSame('mass', $this->c->dimensionOf('tonnes'));
        $this->assertNull($this->c->dimensionOf('bananas'));
    }
}
