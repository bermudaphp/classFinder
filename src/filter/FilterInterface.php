<?php

namespace Bermuda\ClassScanner\Filter;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

interface FilterInterface extends \IteratorAggregate
{
    /**
     * @param iterable<ReflectionClass|ReflectionFunction> $classes
     */
    public function setClasses(iterable $classes): FilterInterface;
}
