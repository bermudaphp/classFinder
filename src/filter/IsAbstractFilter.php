<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;

/**
 * Filters abstract classes
 *
 * Identifies classes declared with the 'abstract' keyword.
 * Abstract classes cannot be instantiated directly and typically
 * serve as base classes for inheritance.
 *
 * This filter uses the null-safe operator to handle cases where
 * the value might not have an isAbstract property.
 *
 * Example:
 * ```php
 * // Find all abstract classes
 * $filter = new IsAbstractFilter();
 *
 * // Will match:
 * // - abstract class BaseController { ... }
 * // - abstract class AbstractService { ... }
 *
 * // Will NOT match:
 * // - class UserController { ... }
 * // - interface UserInterface { ... } (interfaces are not abstract classes)
 * // - function myFunction() { ... } (functions don't have abstract concept)
 * ```
 */
class IsAbstractFilter extends AbstractFilter
{
    public function __construct(
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        // Use null-safe operator to check if value has isAbstract property and it's true
        return $value?->isAbstract ?? false;
    }
}