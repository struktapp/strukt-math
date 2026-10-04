<?php

namespace Strukt;

/**
 * Fluent facade returned by the matrix() helper.
 *
 * The facade allows a base matrix to be optional while exposing factory
 * operations such as random().
 */
final class MatrixProxy
{
    /** Optional base matrix. */
    private ?Matrix $base;

    /**
     * Creates a matrix helper facade.
     *
     * @param array<int, array<int, int|float|string>>|null $base Initial rows.
     */
    public function __construct(?array $base)
    {
        $this->base = $base === null ? null : Matrix::create($base);
    }

    /**
     * Multiplies the stored base matrix.
     *
     * @param array<int, array<int, int|float|string>> $multiplier Right matrix.
     * @return Matrix|null Product, or null when no base matrix exists.
     * @throws Raise When dimensions are incompatible.
     */
    public function multiply(array $multiplier): ?Matrix
    {
        return $this->base?->multiply(Matrix::create($multiplier));
    }

    /**
     * Adds a matrix to the stored base matrix.
     *
     * @param array<int, array<int, int|float|string>> $addend Matrix to add.
     * @return Matrix|null Sum, or null when no base matrix exists.
     * @throws Raise When dimensions are incompatible.
     */
    public function add(array $addend): ?Matrix
    {
        return $this->base?->add(Matrix::create($addend));
    }

    /**
     * Subtracts a matrix from the stored base matrix.
     *
     * @param array<int, array<int, int|float|string>> $subtrahend Matrix to subtract.
     * @return Matrix|null Difference, or null when no base matrix exists.
     * @throws Raise When dimensions are incompatible.
     */
    public function subtract(array $subtrahend): ?Matrix
    {
        return $this->base?->subtract(Matrix::create($subtrahend));
    }

    /**
     * Scales the stored base matrix.
     *
     * @param Number|int|float $factor Scale factor.
     * @return Matrix|null Scaled matrix, or null when no base matrix exists.
     */
    public function scale(Number|int|float $factor): ?Matrix
    {
        return $this->base?->scale($factor);
    }

    /**
     * Returns the magnitude of the stored base matrix.
     *
     * @param int $order Norm order; 2 is Euclidean, 1 is the sum of absolute values.
     * @return Number|null Magnitude, or null when no base matrix exists.
     */
    public function norm(int $order = 2): ?Number
    {
        return $this->base?->norm($order);
    }

    /**
     * Computes the dot product of two vectors.
     *
     * @param array<int, int|float|Number> $a First vector.
     * @param array<int, int|float|Number> $b Second vector.
     * @return Number Dot product.
     * @throws InvalidArgumentException When the vectors have different lengths.
     */
    public function dot(array $a, array $b): Number
    {
        return Matrix::dot($a, $b);
    }

    /**
     * Computes the cosine similarity of two vectors.
     *
     * @param array<int, int|float|Number> $a First vector.
     * @param array<int, int|float|Number> $b Second vector.
     * @return Number Similarity between -1 and 1.
     * @throws InvalidArgumentException When the vectors have different lengths.
     * @throws DivisionByZeroError When either vector has zero magnitude.
     */
    public function cosineSimilarity(array $a, array $b): Number
    {
        return Matrix::cosineSimilarity($a, $b);
    }

    /**
     * Creates a random matrix.
     *
     * @param string $dimensions Dimensions such as 3x3.
     * @param int $sequence Highest generated value.
     * @return Matrix Random matrix.
     * @throws Raise When dimensions or sequence are invalid.
     */
    public function random(string $dimensions = '3x3', int $sequence = 10): Matrix
    {
        return Matrix::random($dimensions, $sequence);
    }

    /**
     * Transposes the stored base matrix.
     *
     * @return Matrix|null Transposed matrix, or null without a base.
     */
    public function transpose(): ?Matrix
    {
        return $this->base?->transpose();
    }

    /**
     * Returns the stored base matrix.
     *
     * @return Matrix|null Base matrix, or null when no base was supplied.
     */
    public function yield(): ?Matrix
    {
        return $this->base;
    }
}
