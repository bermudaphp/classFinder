<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;

/**
 * Filters final classes
 *
 * Identifies classes declared with the 'final' keyword.
 * Final classes cannot be extended by other classes.
 *
 * This filter uses the null-safe operator to handle cases where
 * the value might not have an isFinal property.
 *
 * Example:
 * ```php
 * // Find all final classes
 * $filter = new IsFinalFilter();
 *
 * // Will match:
 * // - final class UserService { ... }
 * // - final class ProductController { ... }
 *
 * // Will NOT match:
 * // - class BaseController { ... }
 * // - abstract class AbstractService { ... }
 * // - interface UserInterface { ... } (interfaces can't be final)
 * // - function myFunction() { ... } (functions don't have final concept)
 * ```
 */
class IsFinalFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $value?->isFinal ?? false;
    }
}