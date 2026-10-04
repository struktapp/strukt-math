<?php

namespace Strukt;

use InvalidArgumentException;

/**
 * Immutable-style numeric value object.
 *
 * Arithmetic methods return a new Number while reset() intentionally mutates
 * the current object for compatibility with the original Strukt API.
 *
 * The class deliberately holds a single `value` property inherited from
 * ValueObject. PHP compares objects property by property, so keeping that one
 * scalar is what allows arrays of Number to be passed straight to max(),
 * min(), and sort() without a custom comparator.
 */
class Number extends ValueObject
{
    /** Default tolerance used by equalsWithDelta(). */
    private const DEFAULT_DELTA = 1e-9;

    /**
     * Creates a numeric value.
     *
     * @param int|float $number Number to wrap.
     */
    public function __construct(int|float $number = 0)
    {
        $this->value = $number;
    }

    /**
     * Creates a Number from a numeric scalar.
     *
     * @param mixed $number Number to wrap.
     * @return static New number of the called class.
     * @throws InvalidArgumentException When the value is not an integer or float.
     */
    public static function create(mixed $number = 0): static
    {
        if (!is_int($number) && !is_float($number)) {
            throw new InvalidArgumentException('Number values must be integers or floats.');
        }

        return new static($number);
    }

    /**
     * Sums a list of numbers.
     *
     * Accepts raw numerics and Number objects, so the result never depends on
     * array_sum(), which cannot add Number instances.
     *
     * @param iterable<int|float|Number> $numbers Values to total.
     * @return static Total, or zero when the list is empty.
     */
    public static function sum(iterable $numbers): static
    {
        $total = new static(0);

        foreach ($numbers as $number) {
            $total = $total->add($number);
        }

        return $total;
    }

    /**
     * Adds a number.
     *
     * @param Number|int|float $number Addend.
     * @return static Sum.
     */
    public function add(Number|int|float $number): static
    {
        return new static($this->numericValue() + self::valueOf($number));
    }

    /**
     * Subtracts a number.
     *
     * @param Number|int|float $number Subtrahend.
     * @return static Difference.
     */
    public function subtract(Number|int|float $number): static
    {
        return new static($this->numericValue() - self::valueOf($number));
    }

    /**
     * Rounds the number.
     *
     * @param int $precision Decimal places.
     * @param int $mode PHP rounding mode.
     * @return static Rounded number.
     */
    public function round(int $precision = 0, int $mode = PHP_ROUND_HALF_UP): static
    {
        return new static(round($this->numericValue(), $precision, $mode));
    }

    /**
     * Rounds the number up to the nearest integer.
     *
     * Distinct from round(), which rounds to the nearest value rather than
     * always upwards, and needed whenever a value indexes into a sequence.
     * The result is an integer, not a float, so it can be used as an offset
     * without a cast.
     *
     * @return static Integer ceiling.
     */
    public function ceil(): static
    {
        return new static((int) ceil($this->numericValue()));
    }

    /**
     * Rounds the number down to the nearest integer.
     *
     * The result is an integer, not a float.
     *
     * @return static Integer floor.
     */
    public function floor(): static
    {
        return new static((int) floor($this->numericValue()));
    }

    /**
     * Returns the absolute value.
     *
     * @return static Non-negative number.
     */
    public function abs(): static
    {
        return new static(abs($this->numericValue()));
    }

    /**
     * Returns the arithmetic negation of the number.
     *
     * @return static Negated number.
     */
    public function negate(): static
    {
        return new static(-$this->numericValue());
    }

    /**
     * Returns the smallest of this number and the given values.
     *
     * Reads as a clamp when chained onto a computed length, such as
     * number($length)->max(1).
     *
     * @param Number|int|float ...$numbers Values to compare.
     * @return static Smallest value.
     */
    public function min(Number|int|float ...$numbers): static
    {
        $smallest = $this->numericValue();

        foreach ($numbers as $number) {
            $value = self::valueOf($number);

            if ($value < $smallest) {
                $smallest = $value;
            }
        }

        return new static($smallest);
    }

    /**
     * Returns the largest of this number and the given values.
     *
     * @param Number|int|float ...$numbers Values to compare.
     * @return static Largest value.
     */
    public function max(Number|int|float ...$numbers): static
    {
        $largest = $this->numericValue();

        foreach ($numbers as $number) {
            $value = self::valueOf($number);

            if ($value > $largest) {
                $largest = $value;
            }
        }

        return new static($largest);
    }

    /**
     * Resets this number to zero.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->value = 0;
    }

    /**
     * Formats the number for display.
     *
     * @param int $precision Decimal places.
     * @param string $thousandsSeparator Thousands separator.
     * @param string $decimalSeparator Decimal separator.
     * @return string Formatted number.
     */
    public function format(
        int $precision = 2,
        string $thousandsSeparator = ',',
        string $decimalSeparator = '.',
    ): string {
        return number_format(
            $this->numericValue(),
            $precision,
            $decimalSeparator,
            $thousandsSeparator,
        );
    }

    /**
     * Multiplies by a number.
     *
     * @param Number|int|float $number Multiplier.
     * @return static Product.
     */
    public function times(Number|int|float $number): static
    {
        return new static($this->numericValue() * self::valueOf($number));
    }

    /**
     * Divides by a number.
     *
     * @param Number|int|float $number Divisor.
     * @return static Quotient.
     * @throws \DivisionByZeroError When the divisor is zero.
     */
    public function parts(Number|int|float $number): static
    {
        $divisor = self::valueOf($number);
        if ($divisor == 0) {
            throw new \DivisionByZeroError('Cannot divide a number by zero.');
        }

        return new static($this->numericValue() / $divisor);
    }

    /**
     * Divides by a number, falling back when the divisor is zero.
     *
     * Share and ratio calculations divide by a total that is frequently zero
     * for empty input, where a neutral result is the meaningful answer rather
     * than an exception.
     *
     * @param Number|int|float $number Divisor.
     * @param Number|int|float $fallback Value used when the divisor is zero.
     * @return static Quotient, or the fallback for a zero divisor.
     */
    public function partsOr(Number|int|float $number, Number|int|float $fallback = 0): static
    {
        $divisor = self::valueOf($number);

        if ($divisor == 0) {
            return new static(self::valueOf($fallback));
        }

        return new static($this->numericValue() / $divisor);
    }

    /**
     * Calculates the remainder after division.
     *
     * Floating-point operands use fmod(); integer operands use integer
     * remainder semantics.
     *
     * @param Number|int|float $number Divisor.
     * @return static Remainder.
     * @throws \DivisionByZeroError When the divisor is zero.
     */
    public function mod(Number|int|float $number): static
    {
        $divisor = self::valueOf($number);
        if ($divisor == 0) {
            throw new \DivisionByZeroError('Cannot calculate a remainder by zero.');
        }

        $value = $this->numericValue();
        $remainder = is_float($value) || is_float($divisor)
            ? fmod($value, $divisor)
            : $value % $divisor;

        return new static($remainder);
    }

    /**
     * Raises the number to a power.
     *
     * @param Number|int|float $number Exponent.
     * @return static Power result.
     */
    public function raise(Number|int|float $number): static
    {
        return new static($this->numericValue() ** self::valueOf($number));
    }

    /**
     * Returns the natural logarithm, or a logarithm in another base.
     *
     * @param float $base Logarithm base, or M_E for natural log.
     * @return static Logarithm.
     * @throws InvalidArgumentException When the base is not positive.
     */
    public function log(float $base = M_E): static
    {
        if ($base <= 0) {
            throw new InvalidArgumentException('The logarithm base must be greater than zero.');
        }

        return new static(log($this->numericValue(), $base));
    }

    /**
     * Returns the base ten logarithm.
     *
     * @return static Logarithm.
     */
    public function log10(): static
    {
        return new static(log10($this->numericValue()));
    }

    /**
     * Returns the number raised to the power of e.
     *
     * @return static Exponential.
     */
    public function exp(): static
    {
        return new static(exp($this->numericValue()));
    }

    /**
     * Allocates this number according to one or more ratios.
     *
     * @param int|float ...$ratios Positive or negative ratio parts.
     * @return array<int, int|float> Allocated values.
     * @throws InvalidArgumentException When no ratios are supplied.
     * @throws \DivisionByZeroError When the ratio total is zero.
     */
    public function ratio(int|float ...$ratios): array
    {
        if ($ratios === []) {
            throw new InvalidArgumentException('At least one ratio is required.');
        }

        $total = array_sum($ratios);
        if ($total == 0) {
            throw new \DivisionByZeroError('Ratio total cannot be zero.');
        }

        $unit = $this->numericValue() / $total;

        return array_map(
            static fn (int|float $ratio): int|float => $ratio * $unit,
            $ratios,
        );
    }

    /**
     * Compares the number with another numeric value.
     *
     * @param mixed $number Value to compare.
     * @return bool True when the values are numerically equal.
     */
    public function equals(mixed $number): bool
    {
        if ($number instanceof self) {
            $number = $number->yield();
        }

        return (is_int($number) || is_float($number))
            && $this->numericValue() == $number;
    }

    /**
     * Compares the number with another value within a tolerance.
     *
     * Preferred over equals() for floats derived from repeated arithmetic,
     * where an exact comparison is almost never what the caller means.
     *
     * @param Number|int|float $number Value to compare.
     * @param float $delta Largest accepted absolute difference.
     * @return bool True when the values differ by no more than the delta.
     */
    public function equalsWithDelta(Number|int|float $number, float $delta = self::DEFAULT_DELTA): bool
    {
        return abs($this->numericValue() - self::valueOf($number)) <= abs($delta);
    }

    /**
     * Orders this number against another value.
     *
     * @param Number|int|float $number Value to compare.
     * @return int Negative, zero, or positive as this number is less than, equal to, or greater than the value.
     */
    public function compare(Number|int|float $number): int
    {
        return $this->numericValue() <=> self::valueOf($number);
    }

    /**
     * Checks whether this number is greater than another value.
     *
     * @param Number|int|float $number Value to compare.
     * @return bool True when this number is greater.
     */
    public function gt(Number|int|float $number): bool
    {
        return $this->compare($number) > 0;
    }

    /**
     * Checks whether this number is less than another value.
     *
     * @param Number|int|float $number Value to compare.
     * @return bool True when this number is less.
     */
    public function lt(Number|int|float $number): bool
    {
        return $this->compare($number) < 0;
    }

    /**
     * Checks whether this number is less than or equal to another value.
     *
     * @param Number|int|float $number Value to compare.
     * @return bool True when this number is less than or equal.
     */
    public function lte(Number|int|float $number): bool
    {
        return $this->compare($number) <= 0;
    }

    /**
     * Checks whether this number is greater than or equal to another value.
     *
     * @param Number|int|float $number Value to compare.
     * @return bool True when this number is greater than or equal.
     */
    public function gte(Number|int|float $number): bool
    {
        return $this->compare($number) >= 0;
    }

    /**
     * Returns the native scalar type of the number.
     *
     * @return string Native PHP type name.
     */
    public function type(): string
    {
        return gettype($this->numericValue());
    }

    /**
     * Returns the native number.
     *
     * @return int|float Number value.
     */
    public function yield(): int|float
    {
        return $this->numericValue();
    }

    /**
     * Converts the number to a string.
     *
     * @return string String representation.
     */
    public function __toString(): string
    {
        return (string) $this->numericValue();
    }

    /**
     * Reads a numeric operand.
     *
     * @param Number|int|float $number Operand.
     * @return int|float Native numeric value.
     */
    private static function valueOf(Number|int|float $number): int|float
    {
        return $number instanceof self ? $number->yield() : $number;
    }

    /**
     * Reads this object's numeric value.
     *
     * @return int|float Native numeric value.
     */
    private function numericValue(): int|float
    {
        return $this->value;
    }
}
