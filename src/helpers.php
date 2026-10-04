<?php

use Strukt\Counter;
use Strukt\MatrixProxy;
use Strukt\Monad;
use Strukt\Number;
use Strukt\Pipeline;
use Strukt\Range;

if (function_exists('helper')) {
    helper('math');
}

if (!function_exists('number') && helper_add('number')) {
    /**
     * Wraps an integer or float in a Number value object.
     *
     * @param int|float $value Number to wrap.
     * @return Number Numeric value object.
     */
    function number(int|float $value): Number
    {
        return new Number($value);
    }
}

if (!function_exists('pipeline') && helper_add('pipeline')) {
    /**
     * Creates a mutable pipeline that feeds steps by position.
     *
     * @param array<int, mixed> $values Positional values.
     * @return Pipeline New pipeline.
     */
    function pipeline(array $values): Pipeline
    {
        return Pipeline::from($values);
    }
}

if (!function_exists('scoped') && helper_add('scoped')) {
    /**
     * Creates a mutable pipeline that feeds steps by parameter name.
     *
     * @param array<int|string, mixed> $scope Named values.
     * @return Pipeline New pipeline.
     */
    function scoped(array $scope): Pipeline
    {
        return Pipeline::fromMap($scope);
    }
}

if (!function_exists('monos') && helper_add('monos')) {
    /**
     * Creates a mutable pipeline, inferring whether steps are fed by position
     * or by name.
     *
     * @deprecated Use pipeline() or scoped() instead. This helper guesses the
     *             mode from the shape of the input, so an array such as
     *             [1 => 'a', 0 => 'b'] is treated as a named scope even though
     *             its values are a list.
     *
     * @param array<int|string, mixed> $params Positional values or named scope.
     * @return Pipeline New pipeline.
     */
    function monos(array $params): Pipeline
    {
        return $params !== [] && !array_is_list($params)
            ? Pipeline::fromMap($params)
            : Pipeline::from($params);
    }
}

if (!function_exists('monad') && helper_add('monad')) {
    /**
     * Lifts a value into an immutable monad.
     *
     * @param mixed $value Value to wrap.
     * @return Monad New monad.
     */
    function monad(mixed $value): Monad
    {
        return Monad::of($value);
    }
}

if (!function_exists('ranger') && helper_add('ranger')) {
    /**
     * Creates an integer range.
     *
     * @param int $min Inclusive lower limit.
     * @param int|null $max Inclusive upper limit.
     * @return Range New range.
     */
    function ranger(int $min = 0, ?int $max = null): Range
    {
        return new Range($min, $max);
    }
}

if (!function_exists('counter') && helper_add('counter')) {
    /**
     * Creates an unnamed or named numeric counter.
     *
     * @param int|float $startAt Initial value.
     * @param string|null $name Optional registry name.
     * @return Counter New or registered counter.
     * @throws \LogicException When the name is already registered.
     */
    function counter(int|float $startAt = 0, ?string $name = null): Counter
    {
        return $name === null
            ? new Counter($startAt)
            : Counter::create($name, $startAt);
    }
}

if (!function_exists('counters') && helper_add('counters')) {
    /**
     * Retrieves a named counter.
     *
     * @param string $name Registered counter name.
     * @return Counter Registered counter.
     * @throws \OutOfBoundsException When the name is not registered.
     */
    function counters(string $name): Counter
    {
        return Counter::get($name);
    }
}

if (!function_exists('matrix') && helper_add('matrix')) {
    /**
     * Creates a fluent matrix facade.
     *
     * @param array<int, array<int, int|float|string>>|null $base Optional rows.
     * @return MatrixProxy Matrix facade.
     * @throws \InvalidArgumentException When the base is not rectangular or numeric.
     */
    function matrix(?array $base): MatrixProxy
    {
        return new MatrixProxy($base);
    }
}
