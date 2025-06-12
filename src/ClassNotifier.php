<?php

declare(strict_types=1);

namespace Bermuda\ClassFinder;

use Bermuda\Tokenizer\ClassInfo;
use Bermuda\Tokenizer\TokenizerInterface;
use Psr\Container\ContainerInterface;

/**
 * ClassNotifier - Notifies listeners about discovered PHP elements
 *
 * This class serves as a notification system for discovered PHP elements.
 * It manages registered listeners and ensures that all discovered elements
 * are properly reported to all interested parties.
 *
 * Workflow:
 * 1. Receives ClassIterator with discovered PHP elements
 * 2. Iterates through all discovered elements (ClassInfo objects)
 * 3. Notifies all registered listeners about each discovered element
 * 4. Calls finalize() on listeners that support final processing
 *
 * Features:
 * - Supports multiple listeners for flexible element processing
 * - Handles both individual listeners and listener providers
 * - Two-phase notification: individual processing + finalization
 * - Immutable listener provider for thread safety
 *
 * @package Bermuda\ClassFinder
 */
final class ClassNotifier
{
    /**
     * The listener provider that manages all registered listeners
     *
     * This readonly property ensures that the provider cannot be changed after construction,
     * providing immutability and preventing accidental modification of the listener collection.
     */
    public readonly ClassFoundListenerProviderInterface $provider;

    /**
     * Create new ClassNotifier instance
     *
     * Accepts various types of listener inputs and normalizes them into a ClassFoundListenerProvider.
     * If a provider is not passed, it creates one automatically from the given listeners.
     *
     * @param ClassFoundListenerProviderInterface|ClassFoundListenerInterface|iterable<ClassFoundListenerInterface> $listeners
     *        Can be:
     *        - ClassFoundListenerProviderInterface: Used directly as the provider
     *        - ClassFoundListenerInterface: Wrapped into a new provider
     *        - iterable of ClassFoundListenerInterface: All listeners added to a new provider
     */
    public function __construct(
        ClassFoundListenerProviderInterface|ClassFoundListenerInterface|iterable $listeners = []
    ) {
        if (!$listeners instanceof ClassFoundListenerProviderInterface) {
            $this->provider = new ClassFoundListenerProvider($listeners instanceof ClassFoundListenerInterface ? [$listeners] : $listeners);
        } else $this->provider = $listeners;
    }

    /**
     * Notify all listeners about discovered classes and handle finalization
     *
     * This method processes a ClassIterator in two distinct phases:
     *
     * Phase 1 - Notification: Iterates through all discovered classes and calls handle()
     * on each registered listener for every class. This ensures all listeners receive
     * notifications about every discovered element.
     *
     * Phase 2 - Finalization: After all classes have been processed, calls finalize()
     * exactly once on each listener that implements FinalizedListenerInterface.
     *
     * This two-phase approach allows listeners to:
     * - Process individual classes during the notification phase
     * - Perform batch operations, cleanup, or summary tasks during finalization
     * - Ensure finalize() is called only once per listener, regardless of class count
     *
     * @param ClassIterator $classes Iterator containing discovered ClassInfo objects
     * @return void
     */
    public function notify(ClassIterator $classes): void
    {
        $listeners = $this->provider->getClassFoundListeners();

        foreach ($classes as $class) {
            foreach ($listeners as $listener) $listener->handle($class);
        }

        foreach ($listeners as $listener) {
            if ($listener instanceof FinalizedListenerInterface) $listener->finalize();
        }
    }

    /**
     * Add a listener to receive notifications about discovered elements
     *
     * This is a convenience method that delegates to the internal provider.
     * The listener will receive notifications for all future notification operations.
     *
     * @param ClassFoundListenerInterface $listener The listener to add to the notification system
     * @return void
     */
    public function addListener(ClassFoundListenerInterface $listener): void
    {
        $this->provider->addListener($listener);
    }

    /**
     * Create ClassNotifier instance from PSR-11 container
     *
     * Factory method that constructs a fully configured ClassNotifier using services
     * and configuration from a dependency injection container. It attempts to resolve
     * the listener provider from the container, falling back to a default factory method
     * if the specific service is not available.
     *
     * @param ContainerInterface $container The DI container containing configuration and services
     * @return self Configured ClassNotifier instance ready for use
     */
    public static function createFromContainer(ContainerInterface $container): self
    {
        $listenerProvider = $container->has(ClassFoundListenerProviderInterface::class)
            ? $container->get(ClassFoundListenerProviderInterface::class)
            : ClassFoundListenerProvider::createFromContainer($container);

        return new self($listenerProvider);
    }
}