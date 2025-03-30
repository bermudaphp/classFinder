<?php

namespace Bermuda\ClassScanner\Filter;

use Bermuda\Reflection\ReflectionClass;

class SubclassFilter extends AbstractFilter
{
    /**
     * @template T
     * @param class-string<T>
     * @param iterable<ReflectionClass> $classes
     */
    public function __construct(
        public readonly string $cls,
        iterable $classes = [],
    ) {
        parent::__construct($classes);
    }

    protected function filter(ReflectionClass $class): bool
    {
        return $class->isSubclassOf($this->cls);
    }
}
