<?php

namespace Bermuda\ClassFinder;

use Bermuda\Filter\Filterable;
use Bermuda\Filter\FilterInterface;
use Bermuda\Filter\FilterableInterface;
use Bermuda\Stdlib\IterableArrayIterator;
use Bermuda\Stdlib\Arrayable;

/**
 * ReflectorIterator
 *
 * This class implements iterator, countable, and arrayable interfaces to allow easy traversal,
 * counting, and conversion to an array of reflection objects (either ReflectionClass or ReflectionFunction).
 * Additionally, it implements the FilterableInterface to allow filtering of reflectors based on provided filters.
 *
 * It uses the Filterable trait which provides methods (e.g., accept()) for applying filters.
 * @method withFilter(FilterInterface $filter, bool $prepend = false): ReflectorIterator
 * @method withoutFilter(FilterInterface $filter): ReflectorIterator
 */
final class ReflectorIterator implements \IteratorAggregate, \Countable, Arrayable, FilterableInterface
{
    use Filterable { accept as private; }

    /**
     * Cached total count of the reflectors.
     *
     * The getter is supposed to return the count from the underlying iterator (if available)
     * or the already stored $count. Note that property accessor syntax shown here is illustrative;
     * standard PHP does not support this syntax, so this may require an alternative implementation.
     *
     * @var int|null
     */
    private(set) ?int $count = null {
        get {
            return $this->reflectors?->count ?? $this->count;
        }
    }

    /**
     * Flag indicating whether the entire reflector collection has been fully traversed and cached.
     *
     * @var bool
     */
    private(set) bool $traversed = false;


    private IterableArrayIterator|array $reflectors;

    /**
     * Constructor.
     *
     * @param iterable $reflectors An iterable collection of reflection objects.
     * @param FilterInterface|iterable $filters A single filter or an iterable of filters implementing FilterInterface.
     *
     * @throws \InvalidArgumentException If any provided filter does not implement FilterInterface.
     */
    public function __construct(
        iterable $reflectors,
        FilterInterface|iterable $filters = []
    ) {
        if ($filters instanceof FilterInterface) {
            $this->filters[] = $filters;
        } else {
            foreach ($filters as $i => $filter) {
                if (!$filter instanceof FilterInterface) {
                    throw new \InvalidArgumentException(
                        "\$filters[$i] passed to Bermuda\ClassFinder\ReflectorIterator must implement FilterInterface"
                    );
                }
                $this->filters[] = $filter;
            }
        }

        $this->reflectors = new IterableArrayIterator($reflectors);
    }

    /**
     * Returns an iterator (as a generator) that yields only the accepted reflectors.
     *
     * The generator iterates over the provided collection of reflectors and applies the filters
     * using the accept() method (inherited from the Filterable trait). Only reflectors passing all filters
     * are yielded.
     *
     * @return \Generator<int, \ReflectionClass|\ReflectionFunction> Yields filtered reflection objects.
     */
    public function getIterator(): \Generator
    {
        foreach ($this->reflectors as $i => $reflector) {
            if ($this->accept($reflector, $i)) yield $i => $reflector;
        }

        $this->traverse();
    }

    /**
     * Converts the iterable of reflectors into an array.
     *
     * If the internal reflectors property is not already an array,
     * it converts the iterator to an array and stores it.
     *
     * @return array<int, \ReflectionClass|\ReflectionFunction>
     */
    public function toArray(): array
    {
        $this->traverse();
        return $this->reflectors;
    }

    /**
     * Returns the count of reflectors.
     *
     * Ensures that the reflectors property is an array before counting.
     *
     * @return int The number of accepted reflectors.
     */
    public function count(): int
    {
        $this->traverse();
        return $this->count;
    }

    private function traverse(): void
    {
        if (!$this->traversed) {
            $this->traversed = true;
            $this->reflectors = $this->reflectors->toArray();
        }
    }
}
