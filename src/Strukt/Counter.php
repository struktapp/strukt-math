<?php

namespace Strukt;

use LogicException;
use OutOfBoundsException;

/**
 * Mutable numeric counter with an optional process-local named registry.
 */
class Counter
{
    /** Current counter value. */
    private Number $counter;

    /** @var array<string, static> Named counters. */
    private static array $counters = [];

    /**
     * Creates an unnamed counter.
     *
     * @param int|float $startAt Initial value.
     */
    public function __construct(int|float $startAt = 0)
    {
        $this->counter = new Number($startAt);
    }

    /**
     * Creates and registers a named counter.
     *
     * @param string $name Registry name.
     * @param int|float $counter Initial value.
     * @return static Registered counter.
     * @throws LogicException When the name is already registered.
     */
    public static function create(string $name, int|float $counter = 0): static
    {
        if (array_key_exists($name, static::$counters)) {
            throw new LogicException(sprintf('Counter[%s] already exists.', $name));
        }

        $newCounter = new static($counter);
        static::$counters[$name] = $newCounter;

        return $newCounter;
    }

    /**
     * Retrieves a named counter.
     *
     * @param string $name Registry name.
     * @return static Registered counter.
     * @throws OutOfBoundsException When the name is not registered.
     */
    public static function get(string $name): static
    {
        if (!array_key_exists($name, static::$counters)) {
            throw new OutOfBoundsException(sprintf('Counter[%s] was not registered.', $name));
        }

        return static::$counters[$name];
    }

    /**
     * Increments the counter.
     *
     * @return void
     */
    public function up(): void
    {
        $this->counter = $this->counter->add(1);
    }

    /**
     * Decrements the counter.
     *
     * @return void
     */
    public function down(): void
    {
        $this->counter = $this->counter->subtract(1);
    }

    /**
     * Resets this counter to zero.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->counter->reset();
    }

    /**
     * Compares the current value.
     *
     * @param mixed $value Value to compare.
     * @return bool True when the values are numerically equal.
     */
    public function equals(mixed $value): bool
    {
        return $this->counter->equals($value);
    }

    /**
     * Returns the current value.
     *
     * @return int|float Counter value.
     */
    public function yield(): int|float
    {
        return $this->counter->yield();
    }

    /**
     * Clears all named counters.
     *
     * @return void
     */
    public static function resetAll(): void
    {
        static::$counters = [];
    }
}
