<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;

/**
 * Filters only instantiable classes
 *
 * Identifies classes that can be instantiated with the 'new' keyword.
 * A class is instantiable if it's concrete (not abstract and not an interface/trait).
 *
 * Instantiable classes are those that:
 * - Are not abstract
 * - Are not interfaces
 * - Are not traits
 * - Have accessible constructors (this filter doesn't check constructor visibility)
 *
 * Functions always pass through this filter since they're not classes.
 *
 * Example:
 * ```php
 * // Find all instantiable classes
 * $filter = new InstantiableFilter();
 *
 * // Will match:
 * // - class UserService { ... }
 * // - final class ProductController { ... }
 *
 * // Will NOT match:
 * // - abstract class BaseController { ... }
 * // - interface UserRepositoryInterface { ... }
 * // - trait TimestampableTrait { ... }
 * ```
 */
class InstantiableFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $value?->isConcrete ?? false;
    }
}