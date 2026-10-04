<?php

namespace Strukt;

use DivisionByZeroError;
use InvalidArgumentException;

/**
 * Immutable-style rectangular numeric matrix.
 *
 * Matrix operations return new instances and leave their operands unchanged.
 */
class Matrix
{
    /** @var array<int, array<int, int|float>> Matrix rows. */
    private array $arr;

    /** Number of columns in every row. */
    private int $columns;

    /**
     * Creates a rectangular matrix.
     *
     * Numeric strings are normalized to integers or floats so later arithmetic
     * does not repeatedly coerce values.
     *
     * @param array<int, array<int, int|float|string>> $arr Matrix rows.
     * @throws InvalidArgumentException When rows are not rectangular or numeric.
     */
    public function __construct(array $arr)
    {
        $rows = array_values($arr);
        $this->arr = [];
        $this->columns = 0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException(sprintf('Matrix row %d must be an array.', $index));
            }

            $row = array_values($row);
            if ($index === 0) {
                $this->columns = count($row);
            } elseif (count($row) !== $this->columns) {
                throw new InvalidArgumentException('Matrix rows must have equal lengths.');
            }

            $this->arr[] = array_map(
                static function (mixed $value): int|float {
                    if (is_int($value) || is_float($value)) {
                        return $value;
                    }

                    if (is_string($value) && is_numeric($value)) {
                        return strpbrk($value, '.eE') === false ? (int) $value : (float) $value;
                    }

                    throw new InvalidArgumentException('Matrix values must be numeric.');
                },
                $row,
            );
        }
    }

    /**
     * Creates a matrix through late static binding.
     *
     * @param array<int, array<int, int|float|string>> $arr Matrix rows.
     * @return static New matrix.
     */
    public static function create(array $arr): static
    {
        return new static($arr);
    }

    /**
     * Transposes the matrix.
     *
     * @return static Transposed matrix.
     */
    public function transpose(): static
    {
        if ($this->arr === [] || $this->columns === 0) {
            return new static([]);
        }

        $transposed = [];
        for ($column = 0; $column < $this->columns; $column++) {
            $transposed[$column] = [];
            foreach ($this->arr as $row) {
                $transposed[$column][] = $row[$column];
            }
        }

        return new static($transposed);
    }

    /**
     * Multiplies this matrix by another matrix.
     *
     * @param Matrix $b Right-hand matrix.
     * @return static Product matrix.
     * @throws Raise When the matrix dimensions are incompatible.
     */
    public function multiply(Matrix $b): static
    {
        if ($this->columns !== count($b->arr)) {
            raise('Matrices dimensions incompatible!');
        }

        if ($this->arr === [] || $b->arr === []) {
            return new static([]);
        }

        $result = [];
        $rightColumns = $b->columns;

        foreach ($this->arr as $leftRow) {
            $resultRow = [];
            for ($column = 0; $column < $rightColumns; $column++) {
                $sum = 0;
                for ($row = 0; $row < $this->columns; $row++) {
                    $sum += $leftRow[$row] * $b->arr[$row][$column];
                }

                $resultRow[] = $sum;
            }

            $result[] = $resultRow;
        }

        return new static($result);
    }

    /**
     * Adds another matrix element by element.
     *
     * @param Matrix $b Right-hand matrix.
     * @return static Sum matrix.
     * @throws Raise When the matrix dimensions are incompatible.
     */
    public function add(Matrix $b): static
    {
        return $this->combine($b, static fn (int|float $left, int|float $right): int|float => $left + $right);
    }

    /**
     * Subtracts another matrix element by element.
     *
     * @param Matrix $b Right-hand matrix.
     * @return static Difference matrix.
     * @throws Raise When the matrix dimensions are incompatible.
     */
    public function subtract(Matrix $b): static
    {
        return $this->combine($b, static fn (int|float $left, int|float $right): int|float => $left - $right);
    }

    /**
     * Multiplies every element by a factor.
     *
     * @param Number|int|float $factor Scale factor.
     * @return static Scaled matrix.
     */
    public function scale(Number|int|float $factor): static
    {
        $value = $factor instanceof Number ? $factor->yield() : $factor;

        $scaled = [];
        foreach ($this->arr as $row) {
            $scaled[] = array_map(
                static fn (int|float $entry): int|float => $entry * $value,
                $row,
            );
        }

        return new static($scaled);
    }

    /**
     * Returns the matrix magnitude.
     *
     * The default order is the Euclidean norm of the flattened matrix, which
     * is the value that pairs with dot() to build a cosine similarity.
     *
     * @param int $order Norm order; 2 is Euclidean, 1 is the sum of absolute values.
     * @return Number Magnitude.
     * @throws InvalidArgumentException When the order is not 1 or 2.
     */
    public function norm(int $order = 2): Number
    {
        $sum = self::sumOfSquares($this->flatten());
        $absolute = new Number(0);

        foreach ($this->arr as $row) {
            foreach ($row as $entry) {
                $absolute = $absolute->add(self::num($entry)->abs());
            }
        }

        return match ($order) {
            2 => $sum->raise(0.5),
            1 => $absolute,
            default => throw new InvalidArgumentException(sprintf(
                'Only the 1-norm and 2-norm are supported, got [%s].',
                $order,
            )),
        };
    }

    /**
     * Computes the dot product of two vectors.
     *
     * The vector case is by far the most common, so it gets a direct
     * implementation rather than being spelled as a 1 x n matrix product.
     *
     * @param array<int, int|float|Number> $a First vector.
     * @param array<int, int|float|Number> $b Second vector.
     * @return Number Dot product.
     * @throws InvalidArgumentException When the vectors have different lengths.
     */
    public static function dot(array $a, array $b): Number
    {
        $left = array_values($a);
        $right = array_values($b);

        if (count($left) !== count($right)) {
            throw new InvalidArgumentException(sprintf(
                'Vectors must have the same length, got [%d] and [%d].',
                count($left),
                count($right),
            ));
        }

        $product = new Number(0);

        foreach ($left as $index => $value) {
            $product = $product->add(self::num($value)->times(self::num($right[$index])));
        }

        return $product;
    }

    /**
     * Computes the cosine similarity of two vectors.
     *
     * @param array<int, int|float|Number> $a First vector.
     * @param array<int, int|float|Number> $b Second vector.
     * @return Number Similarity between -1 and 1.
     * @throws InvalidArgumentException When the vectors have different lengths.
     * @throws DivisionByZeroError When either vector has zero magnitude, which leaves the direction undefined.
     */
    public static function cosineSimilarity(array $a, array $b): Number
    {
        $left = array_values($a);
        $right = array_values($b);

        if (count($left) !== count($right)) {
            throw new InvalidArgumentException(sprintf(
                'Vectors must have the same length, got [%d] and [%d].',
                count($left),
                count($right),
            ));
        }

        $leftNorm = self::vectorNorm($left);
        $rightNorm = self::vectorNorm($right);

        return self::dot($left, $right)->parts($leftNorm->times($rightNorm));
    }

    /**
     * Returns the matrix rows.
     *
     * @return array<int, array<int, int|float>> Matrix values.
     */
    public function yield(): array
    {
        return $this->arr;
    }

    /**
     * Creates a random matrix.
     *
     * The dimensions use rows x columns notation.
     *
     * @param string $dimensions Dimensions such as 3x3 or 2x4.
     * @param int $sequence Highest generated value, inclusive.
     * @return static Random matrix.
     * @throws Raise When dimensions or sequence are invalid.
     */
    public static function random(string $dimensions = '3x3', int $sequence = 10): static
    {
        if (preg_match('/^(\d+)x(\d+)$/i', strtolower(trim($dimensions)), $matches) !== 1) {
            raise('Invalid matrix dimensions!');
        }

        $rows = (int) $matches[1];
        $columns = (int) $matches[2];
        if ($rows < 1 || $columns < 1 || $sequence < 1) {
            raise('Matrix dimensions and sequence must be positive!');
        }

        $result = [];
        for ($row = 0; $row < $rows; $row++) {
            $resultRow = [];
            for ($column = 0; $column < $columns; $column++) {
                $resultRow[] = mt_rand(1, $sequence);
            }

            $result[] = $resultRow;
        }

        return new static($result);
    }

    /**
     * Renders the matrix one row per line.
     *
     * @return string Text representation.
     */
    public function __toString(): string
    {
        return implode(
            "\n",
            array_map(
                static fn (array $row): string => sprintf('[%s]', implode(',', $row)),
                $this->arr,
            ),
        );
    }

    /**
     * Applies a binary operation to matching elements of two matrices.
     *
     * @param Matrix $b Right-hand matrix.
     * @param callable(int|float, int|float): int|float $operation Element operation.
     * @return static Result matrix.
     * @throws Raise When the matrix dimensions are incompatible.
     */
    private function combine(Matrix $b, callable $operation): static
    {
        if (count($this->arr) !== count($b->arr) || $this->columns !== $b->columns) {
            raise('Matrices dimensions incompatible!');
        }

        $result = [];
        foreach ($this->arr as $row => $entries) {
            $resultRow = [];
            foreach ($entries as $column => $entry) {
                $resultRow[] = $operation($entry, $b->arr[$row][$column]);
            }

            $result[] = $resultRow;
        }

        return new static($result);
    }

    /**
     * Returns the magnitude of a vector.
     *
     * @param array<int, int|float|Number> $vector Vector values.
     * @return Number Euclidean magnitude.
     * @throws DivisionByZeroError When every entry is zero.
     */
    private static function vectorNorm(array $vector): Number
    {
        $magnitude = self::sumOfSquares($vector)->raise(0.5);

        if ($magnitude->equals(0.0)) {
            throw new DivisionByZeroError(
                'The magnitude of a zero vector is zero, so its direction is undefined.',
            );
        }

        return $magnitude;
    }

    /**
     * Sums the squares of a list of values.
     *
     * @param array<int, int|float|Number> $values Vector or flattened values.
     * @return Number Sum of squares.
     */
    private static function sumOfSquares(array $values): Number
    {
        $sum = new Number(0);

        foreach ($values as $value) {
            $sum = $sum->add(self::num($value)->raise(2));
        }

        return $sum;
    }

    /**
     * Flattens the matrix into a single list of values.
     *
     * @return list<int|float> Every entry in row order.
     */
    private function flatten(): array
    {
        $values = [];

        foreach ($this->arr as $row) {
            foreach ($row as $entry) {
                $values[] = $entry;
            }
        }

        return $values;
    }

    /**
     * Wraps a raw numeric value in a Number.
     *
     * @param Number|int|float $value Value to wrap.
     * @return Number The same instance when already wrapped.
     */
    private static function num(Number|int|float $value): Number
    {
        return $value instanceof Number ? $value : new Number($value);
    }
}
