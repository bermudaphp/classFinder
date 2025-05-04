<?php

namespace Bermuda\ClassFinder;

/**
 * Interface ClassFoundListenerInterface
 *
 * Provides a contract for any class that wishes to be notified when
 * a PHP class or function is discovered during the search process.
 */
interface ClassFoundListenerInterface
{
    /**
     * Handle a discovered reflection object.
     *
     * This method is called when a reflection element (either a class or a function)
     * is found by the ClassFinder.
     *
     * @param ReflectionClass|ReflectionFunction $reflector The discovered reflection object.
     */
    public function handle(\ReflectionClass|\ReflectionFunction $reflector): void;
}
