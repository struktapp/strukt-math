<?php

use Strukt\Counter;
use Strukt\Matrix;
use Strukt\Number;
use Strukt\Raise;
use Strukt\Range;

beforeEach(function (): void {
    Counter::resetAll();
});

test('numbers provide immutable arithmetic and comparisons', function (): void {
    $number = number(1000);

    expect($number->add(200)->yield())->toBe(1200)
        ->and($number->subtract(100)->yield())->toBe(900)
        ->and($number->times(2)->yield())->toBe(2000)
        ->and($number->parts(2)->yield())->toBe(500)
        ->and($number->mod(11)->yield())->toBe(10)
        ->and($number->raise(2)->yield())->toBe(1000000)
        ->and($number->ratio(1, 1))->toBe([500, 500])
        ->and($number->ratio(1, 3))->toBe([250, 750])
        ->and($number->gt(999))->toBeTrue()
        ->and($number->lte(1000))->toBeTrue()
        ->and($number->negate()->equals(-1000))->toBeTrue()
        ->and($number->format())->toBe('1,000.00');

    expect(Number::create(10.5167277)->round(2)->yield())->toBe(10.52)
        ->and(Number::create(1.5)->mod(1)->yield())->toBe(0.5)
        ->and($number->type())->toBe('integer');

    $number->reset();
    expect($number->yield())->toBe(0);
});

test('ranges validate and generate bounded values', function (): void {
    $range = ranger(10, 20);
    $values = $range->random(20);

    expect($range->valid(10))->toBeTrue()
        ->and($range->valid(20))->toBeTrue()
        ->and($range->valid(21))->toBeFalse()
        ->and($range->valid(Number::create(15.5)))->toBeTrue()
        ->and(count($values))->toBe(20)
        ->and($range->random(0))->toBe([]);

    foreach ($values as $value) {
        expect($range->valid($value))->toBeTrue();
    }

    expect(fn (): array => $range->random(-1))
        ->toThrow(InvalidArgumentException::class);
});

test('counters support named registries and preserve their start value', function (): void {
    $counter = counter(5, 'hits');

    $counter->up();
    $counter->up();
    $counter->down();

    expect(counters('hits'))->toBe($counter)
        ->and($counter->yield())->toBe(6)
        ->and($counter->equals(6))->toBeTrue();

    $counter->reset();
    expect($counter->yield())->toBe(0);
});

test('matrices transpose, multiply, render, and generate random values', function (): void {
    $left = Matrix::create([
        [1, 2, 3],
        [4, 5, 6],
        [7, 8, 9],
    ]);
    $right = Matrix::create([
        [11, 22, 33],
        [44, 55, 66],
        [77, 88, 99],
    ]);

    expect($left->multiply($right)->yield())->toBe([
        [330, 396, 462],
        [726, 891, 1056],
        [1122, 1386, 1650],
    ])
        ->and(Matrix::create([[1, 2, 3], [4, 5, 6]])->transpose()->yield())
        ->toBe([[1, 4], [2, 5], [3, 6]])
        ->and((string) Matrix::create([[1, 2], [3, 4]]))
        ->toBe("[1,2]\n[3,4]");

    $random = matrix(null)->random('2x3', 5)->yield();
    expect(count($random))->toBe(2)
        ->and(count($random[0]))->toBe(3);

    foreach ($random as $row) {
        foreach ($row as $value) {
            expect($value)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(5);
        }
    }

    expect(fn (): Matrix => Matrix::create([[1, 2]])->multiply(Matrix::create([[1, 2]])))
        ->toThrow(Raise::class);
});

test('math helper registry contains every global helper', function (): void {
    expect(helper('math'))->toContain(
        'number',
        'pipeline',
        'scoped',
        'monos',
        'monad',
        'ranger',
        'counter',
        'counters',
        'matrix',
    );
});
