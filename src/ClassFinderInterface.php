<?php

namespace Bermuda\ClassFinder;

use Bermuda\Tokenizer\TokenizerInterface;

/**
 * Interface ClassFinderInterface
 *
 * Provides a contract for classes that locate PHP elements—such as classes,
 * interfaces, enums, traits, and functions—from specified directories.
 * The search behavior is determined by SearchMode enum and can be filtered as needed.
 * Returns ClassInfo objects from the tokenizer.
 */
interface ClassFinderInterface
{
    /**
     * Searches for PHP elements in the specified directories.
     *
     * @param string|string[] $dirs One or more directories to search for PHP files.
     * @param string|string[] $exclude One or more directories or filename patterns to exclude from the search.
     * @param int $mode Search mode specifying which element types to include (default is TokenizerInterface::SEARCH_ALL).
     * @return ClassIterator
     */
    public function find(string|array $dirs, string|array $exclude = [], int $mode = TokenizerInterface::SEARCH_ALL): ClassIterator ;
}
