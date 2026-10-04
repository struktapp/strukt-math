<?php

namespace Strukt;

use InvalidArgumentException;

/**
 * Represents an optional integer interval.
 *
 * The upper limit is inclusive when the range is used for random values or
 * validation.
 */
class Range
{
    /** Inclusive lower limit. */
    private int $lowlimit;

    /** Inclusive upper limit, or null for an unbounded range. */
    private ?int $uplimit;

    /**
     * Creates a range.
     *
     * @param int $lowlimit Inclusive lower limit.
     * @param int|null $uplimit Inclusive upper limit.
     * @throws InvalidArgumentException When the upper limit is below the lower limit.
     */
    public function __construct(int $lowlimit = 0, ?int $uplimit = null)
    {
        if ($uplimit !== null && $uplimit < $lowlimit) {
            throw new InvalidArgumentException('The upper limit cannot be below the lower limit.');
        }

        $this->lowlimit = $lowlimit;
        $this->uplimit = $uplimit;
    }

    /**
     * Creates a range through late static binding.
     *
     * @param int $lowlimit Inclusive lower limit.
     * @param int|null $uplimit Inclusive upper limit.
     * @return static New range.
     */
    public static function create(int $lowlimit = 0, ?int $uplimit = null): static
    {
        return new static($lowlimit, $uplimit);
    }

    /**
     * Checks whether a number is inside the range.
     *
     * @param Number|int|float $number Value to check.
     * @return bool True when the value is within both inclusive limits.
     */
    public function valid(Number|int|float $number): bool
    {
        $value = $number instanceof Number ? $number->yield() : $number;

        return $value >= $this->lowlimit
            && ($this->uplimit === null || $value <= $this->uplimit);
    }

    /**
     * Generates random integers from the range.
     *
     * When no upper limit exists, PHP's full mt_rand() interval is used.
     *
     * @param int $qty Number of values to generate.
     * @return array<int, int> Random values.
     * @throws InvalidArgumentException When quantity is negative.
     */
    public function random(int $qty = 1): array
    {
        if ($qty < 0) {
            throw new InvalidArgumentException('The random quantity cannot be negative.');
        }

        if ($qty === 0) {
            return [];
        }

        $numbers = [];
        for ($index = 0; $index < $qty; $index++) {
            $numbers[] = $this->uplimit === null
                ? mt_rand()
                : mt_rand($this->lowlimit, $this->uplimit);
        }

        return $numbers;
    }
}
