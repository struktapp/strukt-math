<?php

namespace Strukt;

use Closure;
use InvalidArgumentException;
use LogicException;
use ReflectionFunction;
use ReflectionParameter;
use Throwable;

/**
 * Mutable, sequential transformation pipeline over a list of values or a named
 * scope.
 *
 * This is the stateful counterpart to {@see Monad}: steps run eagerly as soon
 * as they are added and the pipeline is reused by reference, so it should stay
 * local to one run. Reach for `Monad` when a value has to be passed around or
 * composed independently of evaluation order.
 *
 * Positional pipelines call each step with the value piped from the previous
 * step followed by the values that have not been consumed yet. Only unconsumed
 * values are retained, so adding a step that takes no arguments does not grow
 * the pipeline.
 *
 * Scoped pipelines resolve parameters by name. The value piped from the
 * previous step is bound to the first parameter, and a parameter may not be
 * named after a key that was present in the original scope, because the piped
 * value would overwrite it. Use `with()` to replace a scope value deliberately.
 *
 * A step that throws leaves the pipeline exactly as it was.
 */
class Pipeline
{
    /** Values not yet consumed by a step, or the current scope when named. */
    private array $values;

    /** Whether parameters are resolved from a named scope rather than a list. */
    private bool $isMap;

    /** Keys supplied in the original scope, which may never be overwritten. */
    private array $reserved;

    /** Result from the most recent step. */
    private mixed $result = null;

    /** Whether at least one step has already executed. */
    private bool $hasResult = false;

    /**
     * Creates a pipeline from initial values.
     *
     * @param array<int|string, mixed> $values Positional values or named scope.
     * @param bool $isMap Whether to resolve parameters by name.
     */
    protected function __construct(array $values, bool $isMap)
    {
        $this->values = $values;
        $this->isMap = $isMap;
        $this->reserved = $isMap ? array_keys($values) : [];
    }

    /**
     * Creates a pipeline that feeds steps by position.
     *
     * @param array<int, mixed> $values Positional values.
     * @return static New pipeline.
     */
    public static function from(array $values): static
    {
        return new static($values, false);
    }

    /**
     * Creates a pipeline that feeds steps by parameter name.
     *
     * @param array<int|string, mixed> $scope Named values.
     * @return static New pipeline.
     */
    public static function fromMap(array $scope): static
    {
        return new static($scope, true);
    }

    /**
     * Applies a transformation step.
     *
     * @param Closure $step Transformation callback.
     * @return static This pipeline.
     * @throws InvalidArgumentException When a parameter is unavailable, when the
     *                                  piped value would overwrite a scope key,
     *                                  or when a parameter is by reference.
     * @throws Throwable Rethrown from the step, leaving the pipeline unchanged.
     */
    public function next(Closure $step): static
    {
        $reflection = new ReflectionFunction($step);
        $parameters = $reflection->getParameters();
        $this->rejectReferences($parameters);

        $values = $this->values;
        $result = $this->result;
        $hasResult = $this->hasResult;

        try {
            $this->result = $this->isMap
                ? $this->invokeScoped($step, $parameters)
                : $this->invokePositional($step, $reflection);
            $this->hasResult = true;
        } catch (Throwable $exception) {
            $this->values = $values;
            $this->result = $result;
            $this->hasResult = $hasResult;

            throw $exception;
        }

        return $this;
    }

    /**
     * Adds or replaces a value in a named scope.
     *
     * The key becomes reserved, so a later step may not bind the piped value to
     * a parameter of the same name.
     *
     * @param string $name Scope key.
     * @param mixed $value Value to store.
     * @return static This pipeline.
     * @throws LogicException When the pipeline is positional.
     */
    public function with(string $name, mixed $value): static
    {
        if (!$this->isMap) {
            throw new LogicException(
                'A positional pipeline has no named scope, so with() is not available. '
                . 'Use fromMap() to build a pipeline that resolves parameters by name.',
            );
        }

        if (!array_key_exists($name, $this->reserved)) {
            $this->reserved[] = $name;
        }

        $this->values[$name] = $value;

        return $this;
    }

    /**
     * Returns the latest transformation result.
     *
     * @return mixed Result, or null before the first step or after a step that
     *               returned null.
     */
    public function yield(): mixed
    {
        return $this->result;
    }

    /**
     * Reports whether at least one step has executed.
     *
     * @return bool True once a step has produced a result.
     */
    public function hasResult(): bool
    {
        return $this->hasResult;
    }

    /**
     * Reports whether the pipeline is still awaiting its first step.
     *
     * @return bool True while no step has executed.
     */
    public function isPending(): bool
    {
        return !$this->hasResult;
    }

    /**
     * Invokes a step with the piped value followed by unconsumed values.
     *
     * @param Closure $step Transformation callback.
     * @param ReflectionFunction $reflection Step reflection.
     * @return mixed Step result.
     */
    private function invokePositional(Closure $step, ReflectionFunction $reflection): mixed
    {
        $arguments = [];

        if ($this->hasResult) {
            $arguments[] = $this->result;
        }

        $consumed = $reflection->isVariadic()
            ? count($this->values)
            : max(0, $reflection->getNumberOfParameters() - count($arguments));

        $arguments = array_merge($arguments, array_slice($this->values, 0, $consumed));
        $this->values = array_slice($this->values, $consumed);

        return $step(...$arguments);
    }

    /**
     * Invokes a step by binding the piped value to its first parameter.
     *
     * @param Closure $step Transformation callback.
     * @param array<int, ReflectionParameter> $parameters Step parameters.
     * @return mixed Step result.
     * @throws InvalidArgumentException When a parameter is unavailable or the
     *                                  piped value would overwrite a scope key.
     */
    private function invokeScoped(Closure $step, array $parameters): mixed
    {
        if ($this->hasResult && isset($parameters[0])) {
            $name = $parameters[0]->getName();

            if (in_array($name, $this->reserved, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Pipeline value [%s] is already in the scope, so the piped value cannot be '
                    . 'bound to it without overwriting it. Rename the parameter, or set the value '
                    . 'deliberately with with().',
                    $name,
                ));
            }

            $this->values[$name] = $this->result;
        }

        $arguments = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (!array_key_exists($name, $this->values)) {
                if ($parameter->isOptional()) {
                    continue;
                }

                throw new InvalidArgumentException(sprintf(
                    'Pipeline value [%s] is required by the next step. Available values: [%s].',
                    $name,
                    implode(', ', array_keys($this->values)),
                ));
            }

            $arguments[$name] = $this->values[$name];
        }

        return $step(...$arguments);
    }

    /**
     * Rejects by reference parameters, which argument unpacking cannot honour.
     *
     * @param array<int, ReflectionParameter> $parameters Step parameters.
     * @return void
     * @throws InvalidArgumentException When any parameter is by reference.
     */
    private function rejectReferences(array $parameters): void
    {
        foreach ($parameters as $parameter) {
            if ($parameter->isPassedByReference()) {
                throw new InvalidArgumentException(sprintf(
                    'Pipeline step parameter [$%s] is by reference, which a pipeline cannot '
                    . 'honour. Return the new value instead of mutating the argument.',
                    $parameter->getName(),
                ));
            }
        }
    }
}
