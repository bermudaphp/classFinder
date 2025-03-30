<?php

namespace Bermuda\ClassScanner\Filter;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * @param iterable<\ReflectionClass> $classes
     */
    public function __construct(
        protected iterable $classes = [],
    ) {
    }

    public function setClasses(iterable $classes): FilterInterface
    {
        $this->classes = $classes;
        return $this;
    }

    /**
     * @return \Generator<\ReflectionClass>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->classes as $class) if ($this->filter($class)) yield $class;
    }

    abstract protected function filter(\ReflectionClass $class): bool ;
}
