<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * @param iterable<ReflectionClass|ReflectionFunction> $classes
     */
    public function __construct(
        protected iterable $classes = [],
    ) {
    }

    /**
     * @param iterable<ReflectionClass|ReflectionFunction> $classes
     * @return FilterInterface
     */
    public function setClasses(iterable $classes): FilterInterface
    {
        $this->classes = $classes;
        return $this;
    }

    /**
     * @return \Generator<ReflectionClass|ReflectionFunction>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->classes as $class) {
            if ($this->filter($class)) yield $class;
        }
    }

    abstract protected function filter(ReflectionClass|ReflectionFunction $class): bool ;
}
