<?php

namespace Bermuda\ClassScanner\Filter;

use Bermuda\Reflection\ReflectionClass;

class AttributeFilter extends \Bermuda\ClassScanner\Filter\AbstractFilter
{
    /**
     * @template T
     * @param class-string<T> $attribute
     * @param iterable<ReflectionClass> $classes
     */
    public function __construct(
        public string $attribute,
        iterable $classes = []
    ) {
        parent::__construct($classes);
    }

    protected function filter(ReflectionClass $class): bool
    {
        return $class->hasAttribute($this->attribute);
    }
}
