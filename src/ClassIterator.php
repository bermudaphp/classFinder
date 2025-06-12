<?php

namespace Bermuda\ClassFinder;

use Bermuda\Stdlib\Arrayable;
use Bermuda\Tokenizer\ClassInfo;
use Bermuda\Filter\Filterable;
use Bermuda\Filter\FilterInterface;
use Bermuda\Filter\FilterableInterface;
use Bermuda\Iterator\IterableArrayIterator;

/**
 * ClassIterator
 *
 * This class implements iterator, countable, and arrayable interfaces to allow easy traversal,
 * counting, and conversion to an array of ClassInfo objects.
 * Additionally, it implements the FilterableInterface to allow filtering of class information based on provided filters.
 *
 * It uses the Filterable trait which provides methods (e.g., accept()) for applying filters.
 * @method ClassIterator withFilter(FilterInterface $filter, bool $prepend = false)
 * @method ClassIterator withoutFilter(FilterInterface $filter)
 */
final class ClassIterator implements \IteratorAggregate, \Countable, Arrayable, FilterableInterface
{
    use Filterable { accept as private; }

    /**
     * Cached total count of the class objects.
     *
     * The getter is supposed to return the count from the underlying iterator (if available)
     * or the already stored $count. Note that property accessor syntax shown here is illustrative;
     * standard PHP does not support this syntax, so this may require an alternative implementation.
     *
     * @var int|null
     */
    private(set) ?int $count = null {
        get {
            return $this->classObjects?->count ?? $this->count;
        }
    }

    /**
     * Flag indicating whether the entire class collection has been fully traversed and cached.
     *
     * @var bool
     */
    private(set) bool $traversed = false;

    private IterableArrayIterator|array $classObjects;

    /**
     * Constructor.
     *
     * @param iterable<ClassInfo> $classObjects An iterable collection of ClassInfo objects.
     * @param FilterInterface|iterable $filters A single filter or an iterable of filters implementing FilterInterface.
     *
     * @throws \InvalidArgumentException If any provided filter does not implement FilterInterface.
     */
    public function __construct(
        iterable $classObjects,
        FilterInterface|iterable $filters = []
    ) {
        if ($filters instanceof FilterInterface) {
            $this->filters[] = $filters;
        } else {
            foreach ($filters as $i => $filter) {
                if (!$filter instanceof FilterInterface) {
                    throw new \InvalidArgumentException(
                        "\$filters[$i] passed to Bermuda\ClassFinder\ClassIterator must implement FilterInterface"
                    );
                }
                $this->filters[] = $filter;
            }
        }

        $this->classObjects = new IterableArrayIterator($classObjects);
    }

    /**
     * Returns an iterator (as a generator) that yields only the accepted class objects.
     *
     * The generator iterates over the provided collection of class objects and applies the filters
     * using the accept() method (inherited from the Filterable trait). Only class objects passing all filters
     * are yielded.
     *
     * @return \Generator<string, ClassInfo> Yields filtered ClassInfo objects where the key is the filename and the value is the ClassInfo object.
     */
    public function getIterator(): \Generator
    {
        foreach ($this->classObjects as $filename => $classObject) {
            if ($this->accept($classObject, $filename)) yield $filename => $classObject;
        }

        $this->traverse();
    }

    /**
     * Converts the iterable of class objects into an array.
     *
     * If the internal classObjects property is not already an array,
     * it converts the iterator to an array and stores it.
     *
     * @return array<int, ClassInfo>
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->classObjects as $i => $classObject) {
            if ($this->accept($classObject, $i)) $array[] = $classObject;
        }

        $this->traverse();

        return $array;
    }

    /**
     * Returns the count of class objects.
     *
     * Ensures that the classObjects property is an array before counting.
     *
     * @return int The number of accepted class objects.
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->classObjects as $i => $classObject) {
            if ($this->accept($classObject, $i)) $count++;
        }

        $this->traverse();
        return $count;
    }

    private function traverse(): void
    {
        if (!$this->traversed) {
            $this->traversed = true;
            $this->classObjects = $this->classObjects->toArray();
        }
    }
}