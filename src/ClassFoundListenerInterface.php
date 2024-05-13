<?php

namespace Bermuda\ClassScanner;

interface ClassFoundListenerInterface
{
    public function finalize(): void ;
    public function handle(\ReflectionClass $reflector): void ;
}
