<?php

namespace Bermuda\ClassFinder\Filter;

use Bermuda\Filter\AbstractFilter;
use Bermuda\Tokenizer\ClassInfo;

/**
 * Filters classes that implement specific interfaces
 *
 * Supports both single interface and multiple interfaces with AND/OR logic:
 * - Single interface: checks if class implements the interface
 * - Array of interfaces: checks if class implements ALL interfaces (AND logic)
 * - Use implementsAny() for OR logic (any of the interfaces)
 *
 * Example:
 * ```php
 * // Check single interface
 * new ImplementsFilter('Serializable')
 *
 * // Check all interfaces (AND)
 * new ImplementsFilter(['Serializable', 'Countable'])
 *
 * // Check any interface (OR)
 * ImplementsFilter::implementsAny(['Serializable', 'Countable'])
 * ```
 */
class ImplementsFilter extends AbstractFilter
{
    private array $interfacesFlipped;
    private bool $isArray;
    private int $interfaceCount;

    public function __construct(
        private string|array $interfaces,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);

        $this->isArray = is_array($this->interfaces);

        if ($this->isArray) {
            $this->interfacesFlipped = array_flip($this->interfaces);
            $this->interfaceCount = count($this->interfaces);
        } else {
            $this->interfacesFlipped = [$this->interfaces => 0];
            $this->interfaceCount = 1;
        }
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        // Quick preliminary check
        if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait) {
            return false;
        }

        $implementedInterfaces = $value->reflector->getInterfaceNames();
        $implementedCount = count($implementedInterfaces);

        // Optimization: if more interfaces required than implemented
        if ($this->isArray && $this->interfaceCount > $implementedCount) {
            return false;
        }

        if ($this->isArray) {
            // Check ALL interfaces - fastest method with early termination
            $found = 0;
            foreach ($implementedInterfaces as $interface) {
                if (isset($this->interfacesFlipped[$interface])) {
                    if (++$found === $this->interfaceCount) {
                        return true; // Early termination
                    }
                }
            }
            return false;
        }

        // Check single interface - O(n) but with early termination
        foreach ($implementedInterfaces as $interface) {
            if ($interface === $this->interfaces) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates filter that checks if class implements ANY of the specified interfaces (OR logic)
     *
     * This is optimized for maximum performance using array_flip for O(1) lookups.
     *
     * @param array $interfaces List of interface names to check
     * @return self Optimized filter instance
     *
     * Example:
     * ```php
     * // Returns true if class implements Serializable OR Countable OR both
     * $filter = ImplementsFilter::implementsAny(['Serializable', 'Countable']);
     * ```
     */
    public static function implementsAny(array $interfaces): self
    {
        return new class($interfaces) extends ImplementsFilter {
            private array $interfacesFlipped;

            public function __construct(array $interfaces, iterable $iterable = [])
            {
                $this->interfacesFlipped = array_flip($interfaces);
                parent::__construct($interfaces, $iterable);
            }

            public function accept(mixed $value, string|int|null $key = null): bool
            {
                if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait) {
                    return false;
                }

                // O(n) with early termination and O(1) search
                foreach ($value->reflector->getInterfaceNames() as $interface) {
                    if (isset($this->interfacesFlipped[$interface])) {
                        return true;
                    }
                }

                return false;
            }
        };
    }
}