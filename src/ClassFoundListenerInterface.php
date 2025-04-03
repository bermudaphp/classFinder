<?php

namespace Bermuda\ClassFinder;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

interface ClassFoundListenerInterface
{
    public function handle(ReflectionClass|ReflectionFunction $reflector): void ;
}
