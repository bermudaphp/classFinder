<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;
use Bermuda\Tokenizer\FunctionInfo;

/**
 * Filters callable elements (classes with __invoke or functions)
 *
 * Identifies elements that can be called as functions:
 * - All functions are considered callable
 * - Classes are callable if they have an __invoke() method
 *
 * This is useful for finding elements that can be used as:
 * - Event handlers
 * - Middleware
 * - Service callables
 * - Functional programming patterns
 *
 * Example:
 * ```php
 * // Find all callable elements
 * $filter = new CallableFilter();
 *
 * // Results might include:
 * // - function myFunction() { ... }
 * // - class MyClass { public function __invoke() { ... } }
 * ```
 */
class CallableFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        // Classes are callable if they exist and have __invoke method
        if ($value instanceof ClassInfo && $value->exists()) {
            return method_exists($value->fullQualifiedName, '__invoke');
        }

        return false;
    }
}