<?php

namespace Bermuda\ClassFinder;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

interface ClassFinderInterface
{
    const int MODE_FIND_INTERFACES = 1;
    const int MODE_FIND_CLASSES = 2;
    const int MODE_FIND_ENUMS = 3;
    const int MODE_FIND_TRAITS = 4;
    const int MODE_FIND_FUNCTIONS = 5;
    const int MODE_FIND_ALL = self::MODE_FIND_CLASSES | self::MODE_FIND_ENUMS
    | self::MODE_FIND_TRAITS | self::MODE_FIND_INTERFACES | self::MODE_FIND_FUNCTIONS;

    public int $mode {
        get;
    }

    public function withMode(int $mode): ClassFinderInterface ;

    /**
     * @param string|string[] $dirs
     * @param string|string[] $exclude
     * @return \Generator<ReflectionClass|ReflectionFunction>
     * @throws \ReflectionException
     */
    public function find(string|array $dirs, string|array $exclude = []): iterable ;
}
