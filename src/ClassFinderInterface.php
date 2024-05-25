<?php

namespace Bermuda\ClassScanner;

interface ClassFinderInterface
{
    /**
     * @return iterable<\ReflectionClass>
     * @throws \ReflectionException
     */
    public function find(string $dir): iterable;
}
