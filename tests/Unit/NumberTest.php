<?php

/** Coverage for the division fallback, aggregation, and sign helpers. */

use Strukt\Number;

test('partsOr returns the fallback only for a zero divisor', function (): void {
    expect(number(10)->partsOr(2)->yield())->toBe(5)
        ->and(number(10)->partsOr(0)->yield())->toBe(0)
        ->and(number(10)->partsOr(0, 7)->yield())->toBe(7)
        ->and(number(10)->partsOr(0, 2.5)->yield())->toBe(2.5)
        ->and(number(10)->partsOr(0.0, 3)->yield())->toBe(3);
});

test('partsOr accepts a Number fallback and preserves signed zeros', function (): void {
    expect(number(10)->partsOr(0, number(9))->yield())->toBe(9)
        ->and(number(10)->partsOr(-0.0, 4)->yield())->toBe(4);
});

test('parts still refuses to divide by zero', function (): void {
    number(1)->parts(0);
})->throws(DivisionByZeroError::class);

test('sum totals raw numerics and Number objects alike', function (): void {
    expect(Number::sum([1, 2, 3])->yield())->toBe(6)
        ->and(Number::sum([1, 2.5])->yield())->toBe(3.5)
        ->and(Number::sum([number(4), 5])->yield())->toBe(9)
        ->and(Number::sum([])->yield())->toBe(0);
});

test('sum keeps a float result when any input is a float', function (): void {
    expect(Number::sum([1, 2])->type())->toBe('integer')
        ->and(Number::sum([1, 2.5])->type())->toBe('double');
});

test('sum accepts any iterable', function (): void {
    $generator = (static function (): Generator {
        yield 1;
        yield 2;
        yield 3;
    })();

    expect(Number::sum($generator)->yield())->toBe(6)
        ->and(Number::sum(new ArrayIterator([2, 4]))->yield())->toBe(6);
});

test('abs returns a non negative magnitude', function (): void {
    expect(number(-4.5)->abs()->yield())->toBe(4.5)
        ->and(number(4.5)->abs()->yield())->toBe(4.5)
        ->and(number(0)->abs()->yield())->toBe(0)
        ->and(number(-7)->abs()->type())->toBe('integer');
});

test('ceil and floor round towards the extremes as integers', function (): void {
    expect(number(2.1)->ceil()->yield())->toBe(3)
        ->and(number(2.0)->ceil()->yield())->toBe(2)
        ->and(number(-2.1)->ceil()->yield())->toBe(-2)
        ->and(number(2.9)->floor()->yield())->toBe(2)
        ->and(number(2.0)->floor()->yield())->toBe(2)
        ->and(number(-2.9)->floor()->yield())->toBe(-3);
});

test('ceil and floor yield integers so they can index a sequence', function (): void {
    expect(number(2.1)->ceil()->type())->toBe('integer')
        ->and(number(2.9)->floor()->type())->toBe('integer');
});

test('ceil differs from round at the halfway point', function (): void {
    expect(number(2.5)->ceil()->yield())->toBe(3)
        ->and(number(2.5)->round()->yield())->toBe(3.0)
        ->and(number(2.4)->ceil()->yield())->toBe(3)
        ->and(number(2.4)->round()->yield())->toBe(2.0);
});

test('round always yields a float while ceil and floor always yield an integer', function (): void {
    expect(number(2)->round()->type())->toBe('double')
        ->and(number(2.4)->round()->type())->toBe('double')
        ->and(number(2)->ceil()->type())->toBe('integer')
        ->and(number(2.9)->floor()->type())->toBe('integer');
});

test('min and max compare against any number of values', function (): void {
    expect(number(5)->min(1, 3)->yield())->toBe(1)
        ->and(number(1)->min(5)->yield())->toBe(1)
        ->and(number(5)->max(1, 9)->yield())->toBe(9)
        ->and(number(9)->max(5)->yield())->toBe(9);
});

test('min and max accept Number arguments and no arguments at all', function (): void {
    expect(number(0)->max(number(1), 2)->yield())->toBe(2)
        ->and(number(0)->min(number(1), 2)->yield())->toBe(0)
        ->and(number(7)->max()->yield())->toBe(7)
        ->and(number(7)->min()->yield())->toBe(7);
});

test('min and max read as a clamp when chained onto a length', function (): void {
    $clamp = static fn (int $length): int => number($length)->max(1)->yield();

    expect($clamp(0))->toBe(1)
        ->and($clamp(5))->toBe(5);
});

test('equalsWithDelta accepts values that an exact comparison rejects', function (): void {
    $sum = number(0.1)->add(0.2);

    expect($sum->equals(0.3))->toBeFalse()
        ->and($sum->equalsWithDelta(0.3))->toBeTrue()
        ->and($sum->equalsWithDelta(0.3, 0.0))->toBeFalse()
        ->and($sum->equalsWithDelta(0.3, 0.01))->toBeTrue();
});

test('equalsWithDelta treats the delta as an absolute bound', function (): void {
    expect(number(5)->equalsWithDelta(3, 2))->toBeTrue()
        ->and(number(5)->equalsWithDelta(3, 1.9))->toBeFalse()
        ->and(number(5)->equalsWithDelta(7, -2))->toBeTrue();
});

test('compare orders numbers for use in sorters', function (): void {
    expect(number(5)->compare(3))->toBe(1)
        ->and(number(3)->compare(5))->toBe(-1)
        ->and(number(5)->compare(5))->toBe(0)
        ->and(number(5)->compare(5.0))->toBe(0)
        ->and(number(5)->compare(number(5)))->toBe(0);
});

test('compare drives the comparison methods consistently', function (): void {
    $number = number(5);

    expect($number->gt(4))->toBeTrue()
        ->and($number->gt(5))->toBeFalse()
        ->and($number->gte(5))->toBeTrue()
        ->and($number->lt(6))->toBeTrue()
        ->and($number->lt(5))->toBeFalse()
        ->and($number->lte(5))->toBeTrue();
});

test('log returns the natural logarithm and honours a base', function (): void {
    expect(number(1)->log()->yield())->toBe(0.0)
        ->and(number(10)->log()->yield())->toBe(log(10))
        ->and(number(8)->log(2)->yield())->toBe(3.0)
        ->and(number(100)->log(10)->yield())->toBe(2.0);
});

test('log10 and exp are the base ten and natural exponential functions', function (): void {
    expect(number(1000)->log10()->yield())->toBe(3.0)
        ->and(number(0)->exp()->yield())->toBe(1.0)
        ->and(number(1)->exp()->yield())->toBe(M_E)
        ->and(number(2)->log()->exp()->yield())->toBe(2.0);
});

test('a logarithm in another base is the exponent for that base', function (): void {
    $exponent = number(8)->log(2);

    expect($exponent->yield())->toBe(3.0)
        ->and(number(2)->raise($exponent)->yield())->toEqualWithDelta(8.0, 1e-9)
        ->and($exponent->exp()->equalsWithDelta(M_E ** 3))->toBeTrue();
});

test('log rejects a non positive base', function (): void {
    expect(fn () => number(10)->log(0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => number(10)->log(-2))->toThrow(InvalidArgumentException::class);
});
