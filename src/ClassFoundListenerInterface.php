<?php

namespace Bermuda\ClassFinder;

use Bermuda\Tokenizer\ClassInfo;
use Bermuda\Tokenizer\FunctionInfo;

/**
 * Interface ClassFoundListenerInterface
 *
 * Provides a contract for any class that wishes to be notified when
 * a PHP class is discovered during the search process.
 */
interface ClassFoundListenerInterface
{
    /**
     * Handle a discovered ClassInfo object.
     *
     * This method is called when a PHP element (either a class or a function)
     * is found by the ClassFinder.
     *
     * @param ClassInfo $info The discovered ClassInfo object.
     */
    public function handle(ClassInfo $info): void;
}