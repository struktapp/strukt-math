<?php

/** Coverage for the mutable pipeline, including the hazards it now rejects. */

use Strukt\Pipeline;

test('a positional pipeline feeds the piped value then the unconsumed values', function (): void {
    $result = pipeline([12, 3, 2])
        ->next(static fn (int $m, int $x): int => $m * $x)
        ->next(static fn (int $mx, int $c): int => $mx + $c)
        ->next(static fn (int $result): int => $result)
        ->yield();

    expect($result)->toBe(38);
});

test('a scoped pipeline resolves parameters by name', function (): void {
    $result = scoped(['c' => 12, 'm' => 3, 'x' => 2])
        ->next(static fn (int $m, int $x): int => $m * $x)
        ->next(static fn (int $mx, int $c): int => $mx + $c)
        ->next(static fn (int $result): int => $result)
        ->yield();

    expect($result)->toBe(18);
});

test('a scoped pipeline threads a null result through', function (): void {
    $result = scoped(['value' => 1])
        ->next(static fn (int $value): int => 0)
        ->next(static fn (int $result): int => $result + 1)
        ->yield();

    expect($result)->toBe(1);
});

test('a pipeline with no steps is pending and yields null', function (): void {
    $pipeline = pipeline([1, 2]);

    expect($pipeline->isPending())->toBeTrue()
        ->and($pipeline->hasResult())->toBeFalse()
        ->and($pipeline->yield())->toBeNull();
});

test('a step that returns null is still reported as having a result', function (): void {
    $pipeline = pipeline([1])->next(static fn (): null => null);

    expect($pipeline->yield())->toBeNull()
        ->and($pipeline->hasResult())->toBeTrue()
        ->and($pipeline->isPending())->toBeFalse();
});

test('the mode is chosen explicitly rather than inferred from the input', function (): void {
    // The same values in a different key order are still a list, so the mode
    // does not depend on insertion order.
    $ordered = pipeline([0 => 1, 1 => 2])->next(static fn ($a, $b) => "$a/$b")->yield();
    $reordered = pipeline([1 => 1, 0 => 2])->next(static fn ($a, $b) => "$a/$b")->yield();

    expect($ordered)->toBe('1/2')
        ->and($reordered)->toBe('1/2');
});

test('an ambiguous array fails loudly when inferred by the deprecated helper', function (): void {
    monos([1 => 'a', 0 => 'b'])
        ->next(static fn ($first) => $first);
})->throws(InvalidArgumentException::class);

test('monos still infers the mode for an unambiguous input', function (): void {
    expect(monos([1, 2, 3])->next(static fn ($a, $b) => $a + $b)->yield())->toBe(3)
        ->and(monos(['a' => 1, 'b' => 2])->next(static fn ($a, $b) => $a + $b)->yield())->toBe(3);
});

test('the piped value never overwrites a key from the original scope', function (): void {
    scoped(['a' => 1, 'b' => 2])->next(static fn (): int => 10)->next(static fn (int $b): int => $b);
})->throws(InvalidArgumentException::class, 'already in the scope');

test('overwriting a scope key by accident is reported instead of silently wrong', function (): void {
    try {
        scoped(['a' => 1, 'b' => 2])->next(static fn (): int => 10)->next(static fn (int $a): int => $a);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain('[a]')
            ->and($exception->getMessage())->toContain('with()');
    }
});

test('with replaces a scope value deliberately and reserves the key', function (): void {
    $result = scoped(['a' => 1])
        ->with('a', 100)
        ->next(static fn (int $a): int => $a + 1)
        ->yield();

    expect($result)->toBe(101);
});

test('a key added by with is reserved too', function (): void {
    scoped(['a' => 1])
        ->next(static fn (): int => 10)
        ->with('b', 5)
        ->next(static fn (int $b): int => $b);
})->throws(InvalidArgumentException::class, 'already in the scope');

test('with is rejected on a positional pipeline', function (): void {
    pipeline([1])->with('a', 1);
})->throws(LogicException::class, 'positional pipeline has no named scope');

test('reordering the next step is reported rather than silently misbound', function (): void {
    scoped(['c' => 12, 'm' => 3, 'x' => 2])
        ->next(static fn (int $m, int $x): int => $m * $x)
        ->next(static fn (int $c, int $mx): int => $c + $mx);
})->throws(InvalidArgumentException::class, 'already in the scope');

test('a missing value lists what the step could have used', function (): void {
    try {
        scoped(['a' => 1, 'b' => 2])->next(static fn (int $zzz): int => $zzz);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain('[zzz]')
            ->and($exception->getMessage())->toContain('Available values: [a, b]');
    }
});

test('an optional parameter is skipped when the scope has no value for it', function (): void {
    $result = scoped(['a' => 1])
        ->next(static fn (int $a, int $b = 99): int => $a + $b)
        ->yield();

    expect($result)->toBe(100);
});

test('a failing step does not leave the injected value behind', function (): void {
    $pipeline = scoped(['a' => 1]);
    $pipeline->next(static fn (): int => 10);

    // $leaked is not a scope key, so the value is injected and the step runs.
    expect(fn () => $pipeline->next(static fn (int $leaked): int => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class, 'boom');

    // The injection happened before the step threw, so it must have rolled back
    // and $leaked falls back to its default rather than the injected 10.
    expect($pipeline->next(static fn (mixed $carried, mixed $leaked = 'clean'): mixed => $leaked)->yield())
        ->toBe('clean');
});

test('a failing step leaves a scope key holding its original value', function (): void {
    $pipeline = scoped(['a' => 1, 'b' => 2]);
    $pipeline->next(static fn (): int => 10);

    expect(fn () => $pipeline->next(static fn (int $carried, int $b): int => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class, 'boom');

    // a is read as a later parameter: it must still hold its original 1.
    expect($pipeline->next(static fn (int $carried, int $a): int => $a * 2)->yield())->toBe(2);
});

test('a positional pipeline also survives a failing step', function (): void {
    $pipeline = pipeline([5, 6]);
    $pipeline->next(static fn (int $a, int $b): int => $a * $b);

    expect(fn () => $pipeline->next(static fn (): int => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class);

    expect($pipeline->next(static fn (int $product): int => $product + 1)->yield())->toBe(31);
});

test('by reference parameters are rejected instead of silently downgraded', function (): void {
    pipeline([5])->next(static function (&$value): int {
        $value = 99;

        return $value;
    });
})->throws(InvalidArgumentException::class, 'is by reference');

test('a by reference parameter in a scoped step is rejected too', function (): void {
    scoped(['a' => 5])->next(static function (int &$a): int {
        $a = 99;

        return $a;
    });
})->throws(InvalidArgumentException::class, 'is by reference');

test('a step taking no arguments does not grow the pipeline', function (): void {
    $values = new ReflectionProperty(Pipeline::class, 'values');

    $pipeline = pipeline([1]);
    $pipeline->next(static fn (): int => 1)
        ->next(static fn (): int => 1)
        ->next(static fn (): int => 1)
        ->next(static fn (): int => 1)
        ->next(static fn (): int => 1);

    expect($values->getValue($pipeline))->toBe([1]);
});

test('a variadic step receives the piped value and everything left', function (): void {
    $result = pipeline([1, 2, 3])
        ->next(static fn (int ...$all): int => array_sum($all))
        ->yield();

    expect($result)->toBe(6);
});

test('a step may take fewer values than the pipeline holds', function (): void {
    $result = pipeline([1, 2, 3])->next(static fn (int $first): int => $first)->yield();

    expect($result)->toBe(1);
});

test('an empty positional pipeline can still run a step', function (): void {
    expect(pipeline([])->next(static fn (): int => 7)->yield())->toBe(7);
});

test('fromMap rejects a list so a scope is always named', function (): void {
    scoped([1, 2, 3])->next(static fn ($a) => $a);
})->throws(InvalidArgumentException::class, 'required by the next step');

test('the pipeline is reusable as a mutable builder', function (): void {
    $pipeline = pipeline([1]);

    expect($pipeline->next(static fn (int $a): int => $a + 1)->yield())->toBe(2)
        ->and($pipeline->next(static fn (int $a): int => $a * 10)->yield())->toBe(20);
});
