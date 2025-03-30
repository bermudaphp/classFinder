<?php

namespace Bermuda\ClassScanner\Filter;

interface FilterInterface extends \IteratorAggregate
{
    /**
     * @param iterable<\ReflectionClass> $classes
     */
    public function setClasses(iterable $classes): FilterInterface;
}
