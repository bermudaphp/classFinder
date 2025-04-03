<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

class AttributeFilter extends AbstractFilter
{
    /**
     * @template T
     * @param class-string<T> $attribute
     * @param iterable<ReflectionClass|ReflectionFunction> $classes
     */
    public function __construct(
        public readonly string $attribute,
        iterable $classes = []
    ) {
        parent::__construct($classes);
    }

    protected function filter(ReflectionClass|ReflectionFunction $class): bool
    {
        return $class->hasAttribute($this->attribute, true);
    }
}
