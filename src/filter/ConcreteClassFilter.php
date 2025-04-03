<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

class ConcreteClassFilter extends AbstractFilter
{
    /**
     * @template T
     * @param class-string<T>
     * @param iterable<ReflectionClass|ReflectionFunction> $classes
     */
    public function __construct(
        public readonly string $class,
        iterable $classes = []
    ) {
        parent::__construct($classes);
    }

    protected function filter(ReflectionClass|ReflectionFunction $class): bool
    {
        return $class->name === $this->class;
    }
}
