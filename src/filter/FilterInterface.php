<?php

namespace Bermuda\ClassScanner\Filter;

use Bermuda\Reflection\ReflectionClass;

interface FilterInterface extends \IteratorAggregate
{
    /**
     * @param iterable<ReflectionClass> $classes
     */
    public function setClasses(iterable $classes): FilterInterface;
}
