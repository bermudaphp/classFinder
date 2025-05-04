<?php

namespace Bermuda\ClassFinder;

/**
 * Interface ClassFoundListenerProviderInterface
 *
 * Defines a contract for a provider that manages listeners to be notified
 * when a PHP class or function is discovered during the scanning process.
 */
interface ClassFoundListenerProviderInterface
{
    /**
     * Retrieves the registered class found listeners.
     *
     * @return iterable<ClassFoundListenerInterface> An iterable collection of ClassFoundListenerInterface instances.
     */
    public function getClassFoundListeners(): iterable;

    /**
     * Adds a listener to be notified when a class or function is found.
     *
     * @param ClassFoundListenerInterface $listener The listener instance to add.
     */
    public function addListener(ClassFoundListenerInterface $listener): void;
}
