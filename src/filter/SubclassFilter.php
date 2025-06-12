<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;

/**
 * Filters classes that extend specific parent class
 *
 * Uses PHP's reflection to check inheritance hierarchy.
 * Only works with concrete classes (excludes interfaces and traits).
 *
 * The filter uses isSubclassOf() which checks the entire inheritance chain,
 * so it will match classes that extend the target class directly or indirectly.
 *
 * Example:
 * ```php
 * // Find all classes extending AbstractController
 * $filter = new SubclassFilter('App\\Controller\\AbstractController');
 *
 * // Find all Exception classes
 * $filter = new SubclassFilter('Exception');
 *
 * // Find all classes extending a specific interface implementation
 * $filter = new SubclassFilter('App\\Services\\BaseService');
 * ```
 */
class SubclassFilter extends AbstractFilter
{
    public function __construct(
        private string $parentClass,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait) {
            return false;
        }

        return $value->reflector->isSubclassOf($this->parentClass);
    }
}