<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;
use ReflectionAttribute;
use ReflectionClass;

/**
 * Enhanced attribute filter with multiple search options
 *
 * Supports complex attribute filtering with:
 * - Specific attribute classes (exact class matching)
 * - Attribute name patterns (wildcard matching)
 * - AND/OR logic for multiple conditions
 * - Deep search through class members (methods, properties, constants)
 * - High-performance optimizations
 *
 * Example:
 * ```php
 * // Check specific attributes (auto-detects exact vs patterns)
 * new AttributeSearchFilter(['Route', 'Controller'])
 *
 * // Mixed exact and patterns
 * new AttributeSearchFilter(['Route', '*Test*', 'Api*'])
 *
 * // AND logic - must have ALL attributes
 * new AttributeSearchFilter(['Route', 'Middleware'], matchAll: true)
 *
 * // Deep search in class members
 * new AttributeSearchFilter(['Inject'], deepSearch: true)
 *
 * // Static helpers for common cases
 * AttributeSearchFilter::hasAttribute('Route')
 * AttributeSearchFilter::hasAnyAttribute(['Route', 'Controller'])
 * AttributeSearchFilter::hasAllAttributes(['Route', 'Middleware'])
 * ```
 */
class AttributeSearchFilter extends AbstractFilter
{
    private array $matchers = [];
    private int $totalSearches;

    public function __construct(
        private array $attributes = [], // Единый массив для классов и паттернов
        private bool  $matchAll = false, // true = AND logic, false = OR logic
        private bool  $deepSearch = false, // Search in class members (methods, properties, constants)
        iterable      $iterable = []
    )
    {
        parent::__construct($iterable);

        $this->totalSearches = count($this->attributes);
        $this->createMatchers();
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        // Fast check for empty searches
        if ($this->totalSearches === 0) {
            return false;
        }

        // Check class attributes
        $matchCount = $this->checkElement($value->reflector);

        // For OR logic, return true immediately if we found any match
        if (!$this->matchAll && $matchCount > 0) {
            return true;
        }

        // For AND logic, return true if we found all matches
        if ($this->matchAll && $matchCount === $this->totalSearches) {
            return true;
        }

        // If deep search is disabled, check final result
        if (!$this->deepSearch) {
            return $this->matchAll ? false : false; // No matches found
        }

        // Deep search: check all class members
        $deepMatchCount = $this->searchInMembersForMatches($value->reflector, $matchCount);

        // Final check
        return $this->matchAll
            ? $deepMatchCount === $this->totalSearches
            : $deepMatchCount > 0;
    }

    /**
     * Check attributes on a single element (class)
     */
    private function checkElement(ReflectionClass $reflector): int
    {
        $matchCount = 0;

        // Check specific attribute classes
        if ($this->hasAttributeClasses) {
            foreach ($this->attributeClasses as $attributeClass) {
                $attributes = $reflector->getAttributes($attributeClass);

                if (!empty($attributes)) {
                    $matchCount++;

                    // Early termination for OR logic
                    if (!$this->matchAll) {
                        return $matchCount;
                    }
                }
            }
        }

        // Check attribute patterns
        if ($this->hasAttributePatterns) {
            $allAttributes = $reflector->getAttributes();

            if (!empty($allAttributes)) {
                // Extract attribute names once
                $attributeNames = array_map(
                    static fn(\ReflectionAttribute $attr) => $attr->getName(),
                    $allAttributes
                );

                foreach ($this->patternMatchers as $matcher) {
                    foreach ($attributeNames as $name) {
                        if ($matcher($name)) {
                            $matchCount++;

                            // Early termination for OR logic
                            if (!$this->matchAll) {
                                return $matchCount;
                            }
                            break; // Move to next pattern
                        }
                    }
                }
            }
        }

        return $matchCount;
    }

    /**
     * Search through all class members with optimized tracking
     */
    private function searchInMembersForMatches(ReflectionClass $reflector, int $currentMatchCount): int
    {
        if ($currentMatchCount >= $this->totalSearches && $this->matchAll) {
            return $currentMatchCount; // Already found all for AND logic
        }

        $foundClasses = [];
        $foundPatterns = [];

        // Search in all members
        $this->searchInMembers(
            $reflector,
            function (array $attributes) use (&$foundClasses, &$foundPatterns): bool {
                $newMatches = $this->checkMemberAttributes($attributes, $foundClasses, $foundPatterns);

                // For OR logic, stop immediately if found any
                if (!$this->matchAll && $newMatches > 0) {
                    return true; // Stop searching
                }

                // For AND logic, stop if found all
                return $this->matchAll && (count($foundClasses) + count($foundPatterns) >= $this->totalSearches);
            }
        );

        return $currentMatchCount + count($foundClasses) + count($foundPatterns);
    }

    /**
     * Check attributes on a class member and track found patterns/classes
     */
    private function checkMemberAttributes(array $attributes, array &$foundClasses, array &$foundPatterns): int
    {
        $newMatches = 0;

        // Check attribute classes
        if ($this->hasAttributeClasses) {
            foreach ($attributes as $attribute) {
                $attrName = $attribute->getName();

                foreach ($this->attributeClasses as $idx => $attributeClass) {
                    if (isset($foundClasses[$idx])) {
                        continue; // Already found
                    }

                    if ($attrName === $attributeClass) {
                        $foundClasses[$idx] = true;
                        $newMatches++;
                    }
                }
            }
        }

        // Check patterns
        if ($this->hasAttributePatterns && !empty($attributes)) {
            foreach ($attributes as $attribute) {
                $attrName = $attribute->getName();

                foreach ($this->patternMatchers as $idx => $matcher) {
                    if (isset($foundPatterns[$idx])) {
                        continue; // Already found
                    }

                    if ($matcher($attrName)) {
                        $foundPatterns[$idx] = true;
                        $newMatches++;
                    }
                }
            }
        }

        return $newMatches;
    }

    /**
     * Creates pattern matchers from patterns
     */
    private function createPatternMatchers(): void
    {
        foreach ($this->attributePatterns as $pattern) {
            $this->patternMatchers[] = $this->createMatcher($pattern);
        }
    }

    /**
     * Creates matcher for pattern
     */
    private function createMatcher(string $pattern): \Closure
    {
        // Exact match (no wildcards)
        if (strpos($pattern, '*') === false && strpos($pattern, '?') === false) {
            return static fn(string $name): bool => $name === $pattern;
        }

        // "starts with" pattern - prefix*
        if (str_ends_with($pattern, '*') && substr_count($pattern, '*') === 1) {
            $prefix = substr($pattern, 0, -1);
            return static fn(string $name): bool => str_starts_with($name, $prefix);
        }

        // "ends with" pattern - *suffix
        if (str_starts_with($pattern, '*') && substr_count($pattern, '*') === 1) {
            $suffix = substr($pattern, 1);
            return static fn(string $name): bool => str_ends_with($name, $suffix);
        }

        // "contains" pattern - *substring*
        if (str_starts_with($pattern, '*') && str_ends_with($pattern, '*') &&
            substr_count($pattern, '*') === 2) {
            $substring = substr($pattern, 1, -1);
            return static fn(string $name): bool => str_contains($name, $substring);
        }

        // Complex patterns use fnmatch
        return static fn(string $name): bool => fnmatch($pattern, $name);
    }

    /**
     * Creates filter for single attribute class
     */
    public static function hasAttribute(string $attributeClass, bool $deepSearch = false): self
    {
        return new class($attributeClass, $deepSearch) extends AttributeSearchFilter {
            private string $attributeClass;
            private bool $deepSearch;

            public function __construct(string $attributeClass, bool $deepSearch, iterable $iterable = [])
            {
                $this->attributeClass = $attributeClass;
                $this->deepSearch = $deepSearch;
                AbstractFilter::__construct($iterable);
            }

            public function accept(mixed $value, string|int|null $key = null): bool
            {
                if (!$value instanceof ClassInfo) {
                    return false;
                }

                // Check class attributes
                if (!empty($value->reflector->getAttributes($this->attributeClass))) {
                    return true;
                }

                // Deep search if enabled
                return $this->deepSearch && $this->searchInMembers(
                        $value->reflector,
                        fn(array $attrs) => !empty(array_filter(
                            $attrs,
                            fn(\ReflectionAttribute $attr) => $attr->getName() === $this->attributeClass
                        ))
                    );
            }
        };
    }

    /**
     * Creates filter for checking presence of any attributes
     */
    public static function hasAnyAttributes(bool $mustHaveAttributes = true, bool $deepSearch = false): self
    {
        return new class($mustHaveAttributes, $deepSearch) extends AttributeSearchFilter {
            private bool $mustHaveAttributes;
            private bool $deepSearch;

            public function __construct(bool $mustHaveAttributes, bool $deepSearch, iterable $iterable = [])
            {
                $this->mustHaveAttributes = $mustHaveAttributes;
                $this->deepSearch = $deepSearch;
                AbstractFilter::__construct($iterable);
            }

            public function accept(mixed $value, string|int|null $key = null): bool
            {
                if (!$value instanceof ClassInfo) {
                    return false;
                }

                $hasAttributes = !empty($value->reflector->getAttributes());

                if ($hasAttributes && $this->mustHaveAttributes) {
                    return true;
                }

                if (!$hasAttributes && !$this->mustHaveAttributes && !$this->deepSearch) {
                    return true;
                }

                if (!$this->deepSearch) {
                    return $this->mustHaveAttributes ? $hasAttributes : !$hasAttributes;
                }

                // Deep search
                $hasAnyInMembers = $this->searchInMembers(
                    $value->reflector,
                    static fn(array $attrs) => !empty($attrs)
                );

                $overallHasAttributes = $hasAttributes || $hasAnyInMembers;
                return $this->mustHaveAttributes ? $overallHasAttributes : !$overallHasAttributes;
            }
        };
    }

    /**
     * Creates filter for multiple attributes with ANY logic (OR)
     */
    public static function hasAnyAttribute(array $attributes, bool $deepSearch = false): self
    {
        return new self($attributes, matchAll: false, deepSearch: $deepSearch);
    }

    /**
     * Creates filter for multiple attributes with ALL logic (AND)
     */
    public static function hasAllAttributes(array $attributes, bool $deepSearch = false): self
    {
        return new self($attributes, matchAll: true, deepSearch: $deepSearch);
    }

    /**
     * Search through all class members using a custom checker function
     */
    protected function searchInMembers(ReflectionClass $reflector, \Closure $checker): bool
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

        // Check constants
        foreach ($reflector->getReflectionConstants() as $constant) {
            if ($checker($constant->getAttributes())) {
                return true;
            }
        }

        return false;
    }
}