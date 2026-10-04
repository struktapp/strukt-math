<?php

namespace Strukt;

use Closure;

/**
 * Immutable monad for sequencing transformations without mutable state.
 *
 * A monad always holds exactly one value. `of` lifts a value into a monad,
 * `bind` composes a step that may itself return a monad, `map` is `bind`
 * followed by `of`, and `join` reveals the wrapped value.
 *
 * Every operation returns a new instance and nothing mutates, so a monad can
 * be built up, passed around, and reused as many times as needed. Reach for
 * {@see Pipeline} when steps should run eagerly and the builder is kept local
 * to a single run.
 *
 * `bind` is strict: each step runs as it is composed, so an error surfaces
 * where it is written rather than later at `join`.
 *
 * `map` and `bind` satisfy the monad laws for any `of`, so the composition is
 * associative and independent of how the steps are grouped.
 */
class Monad
{
    /**
     * Creates a monad wrapping a value.
     *
     * @param mixed $value Wrapped value.
     */
    protected function __construct(private readonly mixed $value)
    {
    }

    /**
     * Lifts a value into a monad.
     *
     * @param mixed $value Value to wrap.
     * @return static Monad holding the value.
     */
    public static function of(mixed $value): static
    {
        return new static($value);
    }

    /**
     * Composes a step that receives the wrapped value and may return a monad.
     *
     * @param Closure $step Receives the wrapped value, returns a monad or a value.
     * @return static Monad holding the step result.
     */
    public function bind(Closure $step): static
    {
        $next = $step($this->value);

        if ($next instanceof self) {
            return $next instanceof static ? $next : new static($next->value);
        }

        return new static($next);
    }

    /**
     * Transforms the wrapped value, which is `bind` composed with `of`.
     *
     * @param Closure $step Receives the wrapped value, returns a value.
     * @return static Monad holding the transformed value.
     */
    public function map(Closure $step): static
    {
        return $this->bind(static fn (mixed $value): static => new static($step($value)));
    }

    /**
     * Reveals the wrapped value.
     *
     * @return mixed Wrapped value.
     */
    public function join(): mixed
    {
        return $this->value;
    }
}
