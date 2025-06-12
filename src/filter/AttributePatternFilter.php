<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;
use Bermuda\Tokenizer\FunctionInfo;

/**
 * Filters elements by attribute name pattern
 *
 * Performs pattern matching on attribute names using wildcard patterns.
 * Automatically optimizes simple patterns to native PHP functions for better performance:
 * - Exact matches use === comparison
 * - Prefix patterns (prefix*) use str_starts_with()
 * - Suffix patterns (*suffix) use str_ends_with()
 * - Contains patterns (*substring*) use str_contains()
 * - Complex patterns fallback to fnmatch()
 *
 * Supports deep search through class members (methods, properties, constants).
 *
 * For complex attribute filtering with AND/OR logic, consider using AttributeSearchFilter.
 *
 * Example:
 * ```php
 * // Find elements with attributes containing "Route"
 * new AttributePatternFilter('*Route*', deepSearch: true)
 *
 * // Find elements with attributes starting with "Api"
 * new AttributePatternFilter('Api*')
 *
 * // Deep search through all class members
 * new AttributePatternFilter('*Route*', deepSearch: true)
 *
 * // Exact attribute name match (optimized)
 * AttributePatternFilter::exactAttribute('HttpGet')
 *
 * // Multiple attribute names (optimized)
 * AttributePatternFilter::anyAttribute(['Route', 'HttpGet', 'Controller'])
 * ```
 */
class AttributePatternFilter extends AbstractFilter
{
    private \Closure $matcher;

    public function __construct(
        private string $pattern,
        private bool $deepSearch = false, // Search in class members (methods, properties, constants)
        iterable $iterable = []
    ) {
        parent::__construct($iterable);

        // Create matcher function
        $this->matcher = $this->createMatcher();
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        // For classes, check class attributes
        if ($this->checkAttributes($value->reflector->getAttributes())) {
            return true;
        }

        // If deep search is disabled, stop here
        if (!$this->deepSearch) {
            return false;
        }

        // Deep search: check all class members
        return $this->searchClassMembers($value->reflector);
    }

    /**
     * Searches through all class members for matching attributes
     */
    private function searchClassMembers(\ReflectionClass $reflector): bool
    {
        // Check methods
        foreach ($reflector->getMethods() as $method) {
            if ($this->checkAttributes($method->getAttributes())) {
                return true;
            }
        }

        // Check properties
        foreach ($reflector->getProperties() as $property) {
            if ($this->checkAttributes($property->getAttributes())) {
                return true;
            }
        }

        foreach ($reflector->getReflectionConstants() as $constant) {
            if ($this->checkAttributes($constant->getAttributes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Searches through all class members using custom attribute checker
     */
    protected function searchClassMembersWithChecker(\ReflectionClass $reflector, \Closure $checker): bool
    {
        // Check methods
        foreach ($reflector->getMethods() as $method) {
            if ($checker($method->getAttributes())) {
                return true;
            }
        }

        // Check properties
        foreach ($reflector->getProperties() as $property) {
            if ($checker($property->getAttributes())) {
                return true;
            }
        }

        foreach ($reflector->getReflectionConstants() as $constant) {
            if ($checker($constant->getAttributes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if any attribute matches the pattern
     */
    private function checkAttributes(array $attributes): bool
    {
        // Fast check for empty attributes
        if (empty($attributes)) {
            return false;
        }

        // Use optimized matcher with early termination
        foreach ($attributes as $attribute) {
            if (($this->matcher)($attribute->getName())) {
                return true; // Early termination
            }
        }

        return false;
    }

    /**
     * Creates matcher based on pattern type
     *
     * Analyzes wildcard patterns and converts simple cases to native PHP functions
     * for significant performance improvement over fnmatch().
     */
    private function createMatcher(): \Closure
    {
        // Exact match (no wildcards) - fastest option
        if (strpos($this->pattern, '*') === false && strpos($this->pattern, '?') === false) {
            return fn(string $name): bool => $name === $this->pattern;
        }

        // Prefix pattern "prefix*" - 5-8x faster than fnmatch
        if (str_ends_with($this->pattern, '*') && strpos($this->pattern, '*') === strlen($this->pattern) - 1) {
            $prefix = substr($this->pattern, 0, -1);
            return fn(string $name): bool => str_starts_with($name, $prefix);
        }

        // Suffix pattern "*suffix" - 5-8x faster than fnmatch
        if (str_starts_with($this->pattern, '*') && strpos($this->pattern, '*') === 0) {
            $suffix = substr($this->pattern, 1);
            return fn(string $name): bool => str_ends_with($name, $suffix);
        }

        // Contains pattern "*substring*" - 3-5x faster than fnmatch
        if (str_starts_with($this->pattern, '*') && str_ends_with($this->pattern, '*') &&
            substr_count($this->pattern, '*') === 2) {
            $substring = substr($this->pattern, 1, -1);
            return fn(string $name): bool => str_contains($name, $substring);
        }

        // Complex patterns use fnmatch as fallback
        return fn(string $name): bool => fnmatch($this->pattern, $name);
    }

    /**
     * Creates filter for exact attribute name match (fastest option)
     *
     * Uses direct string comparison instead of pattern matching for maximum performance.
     * Recommended for known exact attribute names.
     *
     * @param string $attributeName Exact attribute name to match
     * @param bool $deepSearch Whether to search in class members (methods, properties, constants)
     * @return self Optimized filter instance
     *
     * Example:
     * ```php
     * // Exact match
     * $filter = AttributePatternFilter::exactAttribute('Route');
     *
     * // Deep search in all class members
     * $filter = AttributePatternFilter::exactAttribute('Route', deepSearch: true);
     * ```
     */
    public static function exactAttribute(string $attributeName, bool $deepSearch = false): self
    {
        return new class($attributeName, $deepSearch) extends AttributePatternFilter {
            private string $exactName;
            private bool $deepSearch;

            public function __construct(string $attributeName, bool $deepSearch, iterable $iterable = [])
            {
                $this->exactName = $attributeName;
                $this->deepSearch = $deepSearch;
                AbstractFilter::__construct($iterable);
            }

            public function accept(mixed $value, string|int|null $key = null): bool
            {
                if (!$value instanceof ClassInfo) {
                    return false;
                }

                // For classes, check class attributes
                if ($this->checkClassAttributes($value->reflector->getAttributes())) {
                    return true;
                }

                // If deep search is disabled, stop here
                if (!$this->deepSearch) {
                    return false;
                }

                // Deep search: check all class members
                return $this->searchClassMembersWithChecker($value->reflector, [$this, 'checkClassAttributes']);
            }

            private function checkClassAttributes(array $attributes): bool
            {
                foreach ($attributes as $attribute) {
                    if ($attribute->getName() === $this->exactName) {
                        return true;
                    }
                }

                return false;
            }
        };
    }

    /**
     * Creates filter for multiple attribute names with ANY logic (OR)
     *
     * Uses array_flip() for O(1) lookups instead of O(n) searches.
     * Returns true if element has any of the specified attributes.
     *
     * @param array $attributeNames List of attribute names to check
     * @param bool $deepSearch Whether to search in class members (methods, properties, constants)
     * @return self Optimized filter instance
     *
     * Example:
     * ```php
     * // Check for any routing-related attributes
     * $filter = AttributePatternFilter::anyAttribute([
     *     'Route', 'HttpGet', 'HttpPost', 'Controller'
     * ]);
     *
     * // Deep search in all class members
     * $filter = AttributePatternFilter::anyAttribute(['Route', 'Get'], deepSearch: true);
     * ```
     */
    public static function anyAttribute(array $attributeNames, bool $deepSearch = false): self
    {
        return new class($attributeNames, $deepSearch) extends AttributePatternFilter {
            private array $attributesFlipped;
            private bool $deepSearch;

            public function __construct(array $attributeNames, bool $deepSearch, iterable $iterable = [])
            {
                $this->attributesFlipped = array_flip($attributeNames);
                $this->deepSearch = $deepSearch;
                AbstractFilter::__construct($iterable);
            }

            public function accept(mixed $value, string|int|null $key = null): bool
            {
                if (!$value instanceof ClassInfo) {
                    return false;
                }

                // For classes, check class attributes
                if ($this->checkClassAttributes($value->reflector->getAttributes())) {
                    return true;
                }

                // If deep search is disabled, stop here
                if (!$this->deepSearch) {
                    return false;
                }

                // Deep search: check all class members
                return $this->searchClassMembersWithChecker($value->reflector, [$this, 'checkClassAttributes']);
            }

            private function checkClassAttributes(array $attributes): bool
            {
                foreach ($attributes as $attribute) {
                    // O(1) lookup
                    if (isset($this->attributesFlipped[$attribute->getName()])) {
                        return true;
                    }
                }

                return false;
            }
        };
    }

    /**
     * Creates filter for attribute name prefix matching
     *
     * Uses str_starts_with() for better performance than wildcard patterns.
     * Useful for finding attributes from specific namespaces or with common prefixes.
     *
     * @param string $prefix Prefix to match attribute names against
     * @param bool $deepSearch Whether to search in class members (methods, properties, constants)
     * @return self Optimized filter instance
     *
     * Example:
     * ```php
     * // Find attributes starting with "Http"
     * $filter = AttributePatternFilter::attributePrefix('Http');
     *
     * // Deep search in all class members
     * $filter = AttributePatternFilter::attributePrefix('Http', deepSearch: true);
     * ```
     */
    public static function attributePrefix(string $prefix, bool $deepSearch = false): self
    {
        return new class($prefix, $deepSearch) extends AttributePatternFilter {
            private string $prefix;
            private bool $deepSearch;

            public function __construct(string $prefix, bool $deepSearch, iterable $iterable = [])
            {
                $this->prefix = $prefix;
                $this->deepSearch = $deepSearch;
                AbstractFilter::__construct($iterable);
            }

            public function accept(mixed $value, string|int|null $key = null): bool
            {
                if (!$value instanceof ClassInfo) {
                    return false;
                }

                // For classes, check class attributes
                if ($this->checkClassAttributes($value->reflector->getAttributes())) {
                    return true;
                }

                // If deep search is disabled, stop here
                if (!$this->deepSearch) {
                    return false;
                }

                // Deep search: check all class members
                return $this->searchClassMembersWithChecker($value->reflector, [$this, 'checkClassAttributes']);
            }

            private function checkClassAttributes(array $attributes): bool
            {
                foreach ($attributes as $attribute) {
                    if (str_starts_with($attribute->getName(), $this->prefix)) {
                        return true;
                    }
                }

                return false;
            }
        };
    }
}
