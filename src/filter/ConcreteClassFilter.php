<?php

namespace finder;

class ConcreteClassFilter extends AbstractFilter
{
    public function __construct(
        public readonly string $class,
        iterable $classes = []
    ) {
        parent::__construct($classes);
    }

    protected function filter(\ReflectionClass $class): bool
    {
        return $class->name === $this->class;
    }
}