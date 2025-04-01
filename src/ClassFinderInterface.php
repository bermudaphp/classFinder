<?php

namespace Bermuda\ClassScanner;

use Bermuda\Reflection\ReflectionClass;

interface ClassFinderInterface
{
    /**
     * @param string|string[] $dirs
     * @param string|string[] $exclude
     * @return \Generator<ReflectionClass>
     * @throws \ReflectionException
     */
    public function find(string|array $dirs, string|array $exclude = []): iterable
}
