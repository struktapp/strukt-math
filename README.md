# Strukt Math

Immutable numeric and matrix primitives. This is the `strukt3/math` API plus a set of
additive conveniences; every pre-existing behaviour is unchanged, so it is a drop in
replacement.

## Install

```bash
composer require strukt/math
```

## Number

`Strukt\Number` wraps a single `int` or `float` and returns a new instance from every
operation, so a value can be passed around safely.

```php
use Strukt\Number;

$sum = Number::sum([1, 2, 2.5]);   // 5.5
$mean = Number::sum($column)->parts(count($column));

$average = number(10)->add(0.2)->parts(3);   // 3.4
```

### Aggregating

`sum()` accepts raw numbers, `Number` objects, or any iterable, and totals them into a
`Number` of the same kind: an `int` only when every input is an `int`.

```php
Number::sum([1, 2, 3]);          // 6
Number::sum([1, 2.5]);           // 3.5
Number::sum([]);                 // 0
Number::sum($generator);         // any iterable
```

`min()` and `max()` compare the receiver against any number of values and accept
`Number` arguments. With no arguments they return the receiver, which makes them read
as a clamp when chained off a length.

```php
number(0)->max(1)->yield();      // 1
number(7)->max()->yield();       // 7
```

### Sign, rounding, and roots

```php
number(-4.5)->abs();             // 4.5
number(2.1)->ceil();             // 3  (integer)
number(2.9)->floor();            // 2  (integer)
```

`ceil()` and `floor()` always yield an `integer` so the result can be used to index a
sequence. `round()` is unchanged and always yields a `float`, which is the main
practical difference between the two.

```php
number(8)->log(2);               // 3
number(1000)->log10();           // 3
number(1)->exp();                // 2.718281828459045
```

`log()` defaults to the natural logarithm and rejects a base of zero or less.

### Dividing

`parts()` still throws `DivisionByZeroError` on a zero divisor. `partsOr()` is the
opt in safe variant and returns a fallback instead:

```php
number(10)->parts(2);            // 5
number(10)->parts(0);            // DivisionByZeroError
number(10)->partsOr(0, 7);       // 7
number(10)->partsOr(0);          // 0
```

### Comparing

`equalsWithDelta()` is for values that drift in floating point arithmetic, such as
`0.1 + 0.2`:

```php
number(0.1)->add(0.2)->equals(0.3);                     // false
number(0.1)->add(0.2)->equalsWithDelta(0.3);           // true
number(0.1)->add(0.2)->equalsWithDelta(0.3, 0.0);      // false
```

The delta is an absolute bound, so it is always taken as a magnitude.

`compare()` is exposed as an explicit method returning `-1`, `0`, or `1` for use in
sorters, and the existing comparison methods delegate to it rather than each
re-deriving the sign:

```php
number(5)->compare(3);           // 1
number(5)->compare(5.0);         // 0
number(5)->compare(number(3));   // 1
```

## Matrix

`Strukt\Matrix` is an immutable matrix of `Number` values. Every operation returns a
new matrix and leaves its operands untouched.

```php
use Strukt\Matrix;

$a = Matrix::create([[1, 2], [3, 4]]);

$a->add(Matrix::create([[1, 1], [1, 1]]))->yield();    // [[2, 3], [4, 5]]
$a->subtract(Matrix::create([[1, 1], [1, 1]]))->yield(); // [[0, 1], [2, 3]]
$a->scale(2)->yield();                                  // [[2, 4], [6, 8]]
```

`add()` and `subtract()` require matching shapes and raise `Strukt\Raise` otherwise.

### Vector products

`dot()` multiplies matching entries and adds the products. `cosineSimilarity()`
returns the cosine of the angle between two vectors, which is `1` for parallel vectors,
`0` for orthogonal ones, and `-1` for opposite ones.

```php
Matrix::dot([1, 2], [3, 4]);                    // 11
Matrix::dot([1, 0], [0, 1]);                    // 0

Matrix::cosineSimilarity([1, 2, 3], [1, 2, 3]); // 1.0
Matrix::cosineSimilarity([1, 0], [0, 1]);       // 0.0
```

Both pair entries by position, so keys in the input arrays are irrelevant. Mismatched
lengths throw `InvalidArgumentException`. A zero vector has no direction, so
`cosineSimilarity()` throws `DivisionByZeroError` rather than returning `NAN`.

### Norms

The norm is the magnitude of the whole matrix, flattened into a single vector. The
default order is the euclidean norm, the square root of the sum of squares; order `1`
is the sum of absolute values.

```php
Matrix::create([[3, 4]])->norm();     // 5
Matrix::create([[1, -2]])->norm(1);   // 3
```

Any other order throws `InvalidArgumentException`.

### The helper

`matrix()` returns a proxy, which is handy when the base matrix is only known at
runtime. It also exposes the vector products, which are also available as statics.

```php
$helper = matrix([[1, 2], [3, 4]]);

$helper->dot([1, 2], [3, 4]);     // 11
$helper->norm();                  // sqrt(30)
```

## Transformations

Two classes cover different needs, and both live in this package.

| | `Strukt\Pipeline` | `Strukt\Monad` |
|---|---|---|
| State | mutable, `next()` returns `$this` | immutable, every call returns a new monad |
| Evaluation | eager, a step runs as it is added | strict, a step runs as it is bound |
| Reuse | stateful, reuse re-chains from the last result | unlimited, a monad can be shared and re-joined |
| Best for | a short, local chain of steps | composing, passing around, branching |

Reach for `Pipeline` when the builder stays local to one run. Reach for `Monad` when
the value has to be handed around, or when the steps may be grouped differently.

### Pipeline

`pipeline()` feeds steps by position: each step receives the value piped from the
previous step followed by the values it still needs. Only unconsumed values are kept,
so a step that takes no arguments does not grow the pipeline.

```php
use Strukt\Pipeline;

pipeline([12, 3, 2])
    ->next(static fn (int $m, int $x): int => $m * $x)   // 12, 3        => 36
    ->next(static fn (int $mx, int $c): int => $mx + $c) // 36, 2        => 38
    ->next(static fn (int $result): int => $result)      // 38           => 38
    ->yield();                                          // 38
```

`scoped()` feeds steps by parameter name. The value piped from the previous step is
bound to the first parameter.

```php
scoped(['c' => 12, 'm' => 3, 'x' => 2])
    ->next(static fn (int $m, int $x): int => $m * $x)
    ->next(static fn (int $mx, int $c): int => $mx + $c)
    ->next(static fn (int $result): int => $result)
    ->yield();                                          // 18
```

Note that the two forms answer `38` and `18` for the same values. That is deliberate:
the positional form pairs by position, the scoped form by name. Pick one per pipeline
rather than translating between them.

A scoped parameter may not be named after a key that was already in the scope, because
the piped value would overwrite it. That is reported rather than silently applied, so
rename the parameter, or set the value deliberately with `with()`:

```php
scoped(['a' => 1, 'b' => 2])
    ->next(static fn (): int => 10)
    ->next(static fn (int $b): int => $b);      // InvalidArgumentException
```

`with()` is the explicit way to replace a scope value, and the key it sets becomes
reserved:

```php
scoped(['a' => 1])->with('a', 100)->next(static fn (int $a): int => $a + 1)->yield();  // 101
```

A step that throws leaves the pipeline exactly as it was, and a by reference parameter
is rejected because argument unpacking cannot honour one:

```php
$pipeline->next(static function (int &$a) { $a = 99; });   // InvalidArgumentException
```

`hasResult()` and `isPending()` tell a pipeline that has not run yet from one whose step
returned `null`:

```php
$pipeline = pipeline([1]);

$pipeline->isPending();    // true
$pipeline->hasResult();    // false
```

`monos()` is a deprecated shim that guesses the mode from the shape of its input. Use
`pipeline()` or `scoped()` instead, because an array such as `[1 => 'a', 0 => 'b']` is
treated as a named scope even though its values are a list.

### Monad

A monad always holds exactly one value. `of` lifts a value in, `bind` composes a step
that may itself return a monad, `map` is `bind` composed with `of`, and `join` reveals
the value.

```php
use Strukt\Monad;

Monad::of(12)
    ->bind(static fn (int $c): Monad => Monad::of(3)->map(static fn (int $m) => $m * 2))
    ->bind(static fn (int $mx): Monad => Monad::of($mx + 12))
    ->join();   // 18
```

Nothing mutates, so a monad can be shared and joined as many times as you like:

```php
$base = Monad::of(2);

$base->map(static fn (int $v): int => $v * 2)->join();   // 4
$base->map(static fn (int $v): int => $v * 3)->join();   // 6
$base->join();                                          // 2
```

`bind` is strict, so a step error surfaces where it is written rather than later at
`join`. `map` does not flatten, so it nests a returned monad; use `bind` to flatten.

The three monad laws hold for any `of`, which is what makes grouping irrelevant:

```php
$f = static fn (int $v): Monad => Monad::of($v + 1);
$g = static fn (int $v): Monad => Monad::of($v * 2);
$h = static fn (int $v): Monad => Monad::of($v - 3);

Monad::of(10)->bind($f)->bind($g)->bind($h)->join();                              // 19
Monad::of(10)->bind(static fn (int $v) => $f($v)->bind($g)->bind($h))->join();     // 19
```

`Monad` deliberately models sequencing only. It has no notion of failure, so use `bind`
with a closure that throws, or return an `Either` from a base package for that.

## Compatibility with strukt3/math

`Range`, `Counter`, `helpers.php`, and every pre-existing `Number` and `Matrix` method
behave exactly as before. The new methods are purely additive, with two deliberate
type choices worth knowing about:

* `ceil()` and `floor()` return an `integer` rather than a `float`, which differs from
  `round()` and is what makes them safe as indexes.
* `sum()` preserves the type of its inputs: an `int` only when every input is an `int`.

One pre-existing class did change. The old `Strukt\Monad` was a mutable pipeline with no
`bind` and no laws, which is what `Strukt\Pipeline` now is. Migrate as follows:

| Old | New |
|---|---|
| `new Monad([...])` | `Pipeline::from([...])` / `Pipeline::fromMap([...])` |
| `Monad::create([...])` | `Pipeline::from([...])` / `Pipeline::fromMap([...])` |
| `->next($step)->yield()` | unchanged, on `Pipeline` |
| `monos([...])` | `pipeline([...])` or `scoped([...])` |
| `Monad` as a value wrapper | `Monad::of($value)->join()` |

Because `Pipeline` now refuses to overwrite a scope key, a step whose first parameter
happens to share a name with a scope key throws where it used to return a silently wrong
value. That is the intended difference.

## Tests

```bash
composer test
```
