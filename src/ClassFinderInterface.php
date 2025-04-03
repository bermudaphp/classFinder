<?php

namespace Bermuda\ClassScanner;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

interface ClassFinderInterface
{
    /**
     * @param string|string[] $dirs
     * @param string|string[] $exclude
     * @return \Generator<ReflectionClass|ReflectionFunction>
     * @throws \ReflectionException
     */
    public function find(string|array $dirs, string|array $exclude = []): iterable
}
