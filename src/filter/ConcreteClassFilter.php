<?php

namespace Bermuda\ClassScanner\Filter;

use Bermuda\Reflection\ReflectionClass;

class ConcreteClassFilter extends AbstractFilter
{
      /**
     * @template T
     * @param class-string<T>
     * @param iterable<ReflectionClass> $classes
     */
    public function __construct(
        public readonly string $class,
        iterable $classes = []
    ) {
        parent::__construct($classes);
    }

    protected function filter(ReflectionClass $class): bool
    {
        return $class->name === $this->class;
    }
}
