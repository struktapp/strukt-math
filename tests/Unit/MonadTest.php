<?php

/** Coverage for the immutable monad and the laws it must satisfy. */

use Strukt\Monad;

class MonadSubclass extends Monad
{
}

test('of lifts a value and join reveals it', function (): void {
    expect(Monad::of(7)->join())->toBe(7)
        ->and(monad('x')->join())->toBe('x')
        ->and(Monad::of(null)->join())->toBeNull();
});

test('bind composes a step that returns a plain value', function (): void {
    expect(Monad::of(2)->bind(static fn(int $v): int => $v * 3)->join())->toBe(6);
});

test('bind flattens a step that returns a monad', function (): void {
    $doubled = (static fn(int $v): Monad => Monad::of($v * 2));

    expect(Monad::of(5)->bind($doubled)->join())->toBe(10)
        ->and(Monad::of(5)->bind($doubled)->bind($doubled)->join())->toBe(20);
});

test('bind threads a value through a sequence of steps', function (): void {
    $result = Monad::of(12)
        ->bind(static fn(int $c): Monad => Monad::of(3)->map(static fn(int $m) => $m * 2))
        ->bind(static fn(int $mx): Monad => Monad::of($mx + 12))
        ->join();

    expect($result)->toBe(18);
});

test('map transforms without changing the shape', function (): void {
    expect(Monad::of(2)->map(static fn(int $v): int => $v + 1)->join())->toBe(3)
        ->and(Monad::of(2)->map(static fn(int $v): int => $v + 1)->map(static fn(int $v): int => $v * 2)->join())->toBe(6);
});

test('map is bind composed with of and does not flatten a monad', function (): void {
    $step = (static fn(int $v): int => $v * 10);

    $viaBind = Monad::of(2)->bind(static fn(int $v): Monad => Monad::of($step($v)))->join();
    $viaMap  = Monad::of(2)->map($step)->join();

    // Unlike bind, map nests rather than flattening.
    $nested = Monad::of(Monad::of(1))->map(static fn(mixed $v): mixed => $v)->join();

    expect($viaBind)->toBe(20)
        ->and($viaMap)->toBe(20)
        ->and($nested)->toBeInstanceOf(Monad::class)
        ->and($nested->join())->toBe(1);
});

test('a monad is immutable and can be reused', function (): void {
    $base    = Monad::of(2);
    $doubled = $base->map(static fn(int $v): int => $v * 2);
    $tripled = $base->map(static fn(int $v): int => $v * 3);

    expect($base->join())->toBe(2)
        ->and($doubled->join())->toBe(4)
        ->and($tripled->join())->toBe(6)
        ->and($base->join())->toBe(2)
        ->and($base->map(static fn(int $v): int => $v * 2)->join())->toBe(4);
});

test('bind is strict so a step error surfaces where it is written', function (): void {
    $calls    = 0;
    $counting = static function (int $v) use (&$calls): Monad {
        $calls++;

        return Monad::of($v);
    };

    $monad = Monad::of(1)->bind($counting)->bind($counting);

    expect($calls)->toBe(2);

    // Composing does not re-run anything when the result is reused.
    expect($monad->join())->toBe(1)
        ->and($monad->join())->toBe(1)
        ->and($calls)->toBe(2);
});

test('left identity: of(a) bind f is the same as f(a)', function (): void {
    $f = (static fn(int $v): Monad => Monad::of($v + 1)->map(static fn(int $x): int => $x * 2));

    expect(Monad::of(3)->bind($f)->join())->toBe($f(3)->join())
        ->and(Monad::of(3)->bind($f)->join())->toBe(8);
});

test('right identity: m bind of is the same as m', function (): void {
    $monad = Monad::of(42)->map(static fn(int $v): int => $v + 1);

    expect($monad->bind(static fn(mixed $v): Monad => Monad::of($v))->join())->toBe($monad->join())
        ->and($monad->bind(static fn(mixed $v): Monad => Monad::of($v))->join())->toBe(43);
});

test('associativity: grouping of bind does not change the result', function (): void {
    $f = (static fn(int $v): Monad => Monad::of($v + 1));
    $g = (static fn(int $v): Monad => Monad::of($v * 2));
    $h = (static fn(int $v): Monad => Monad::of($v - 3));

    $left  = Monad::of(10)->bind($f)->bind($g)->bind($h)->join();
    $right = Monad::of(10)->bind(static fn(int $v): Monad => $f($v)->bind($g)->bind($h))->join();

    expect($left)->toBe(19)
        ->and($right)->toBe(19);
});

test('a monad holds any value, not just numbers', function (): void {
    $total  = Monad::of([1, 2, 3])->map(static fn(array $v): int => array_sum($v))->join();
    $text   = Monad::of('ab')->map(static fn(string $v): string => strtoupper($v))->join();
    $object = Monad::of(new stdClass())->map(static fn(stdClass $v): stdClass => $v)->join();

    expect($total)->toBe(6)
        ->and($text)->toBe('AB')
        ->and($object)->toBeInstanceOf(stdClass::class);
});

test('a monad can carry null through every step', function (): void {
    $result = Monad::of(null)
        ->map(static fn(?int $v): ?int => $v)
        ->bind(static fn(?int $v): Monad => Monad::of($v))
        ->join();

    expect($result)->toBeNull();
});

test('the wrapped value is read on demand, not recomputed', function (): void {
    $monad = Monad::of([1, 2, 3]);

    expect($monad->join())->toBe([1, 2, 3])
        ->and($monad->join())->toBe([1, 2, 3]);
});

test('a subclass keeps its own type through of and bind', function (): void {
    $subclass = MonadSubclass::of('a');

    expect($subclass)->toBeInstanceOf(MonadSubclass::class)
        ->and($subclass->bind(static fn(string $v): mixed => $v . 'b')->join())->toBe('ab')
        ->and($subclass->map(static fn(string $v): string => $v . 'c'))->toBeInstanceOf(MonadSubclass::class)
        ->and($subclass->join())->toBe('a');
});

test('a subclass returned by a step is adopted by the calling monad', function (): void {
    $base = Monad::of(1)->bind(static fn(int $v): Monad => MonadSubclass::of($v + 1));

    expect($base)->toBeInstanceOf(Monad::class)
        ->and($base->join())->toBe(2);
});
