<?php

namespace Bermuda\ClassFinder;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

/**
 * Interface ClassFinderInterface
 *
 * Provides a contract for classes that locate PHP elements—such as classes,
 * interfaces, enums, traits, and functions—from specified directories.
 * The search behavior is determined by a mode bitmask and can be filtered as needed.
 */
interface ClassFinderInterface
{
    /**
     * Mode flag to search for interfaces.
     */
    const MODE_FIND_INTERFACES = 1;

    /**
     * Mode flag to search for classes.
     */
    const MODE_FIND_CLASSES = 2;

    /**
     * Mode flag to search for enumerations (enums).
     */
    const MODE_FIND_ENUMS = 3;

    /**
     * Mode flag to search for traits.
     */
    const MODE_FIND_TRAITS = 4;

    /**
     * Mode flag to search for functions.
     */
    const MODE_FIND_FUNCTIONS = 5;

    /**
     * Mode flag to search for all element types: classes, enums, traits,
     * interfaces, and functions.
     */
    const MODE_FIND_ALL = self::MODE_FIND_CLASSES | self::MODE_FIND_ENUMS
    | self::MODE_FIND_TRAITS | self::MODE_FIND_INTERFACES | self::MODE_FIND_FUNCTIONS;


    /**
     * Searches for PHP elements in the specified directories.
     *
     * @param string|string[] $dirs One or more directories to search for PHP files.
     * @param string|string[] $exclude One or more directories or filename patterns to exclude from the search.
     * @param int-mask-of<self::MODE_*> $modeFlag Bitmask (using self::MODE_* constants) specifying which node types to include (default is MODE_FIND_ALL).
     * @return ReflectorIterator
     */
    public function find(string|array $dirs, string|array $exclude = [], int $modeFlag = self::MODE_FIND_ALL): ReflectorIterator ;
}
