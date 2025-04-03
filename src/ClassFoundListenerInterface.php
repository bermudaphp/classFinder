<?php

namespace Bermuda\ClassScanner;

use Bermuda\Reflection\ReflectionClass;

interface ClassFoundListenerInterface
{
    public function finalize(): void ;
    public function handle(ReflectionClass|ReflectionFunction $reflector): void ;
}
