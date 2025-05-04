<?php

namespace Bermuda\ClassFinder;

use ReflectionClass;
use ReflectionFunction;
use Psr\Container\ContainerInterface;

/**
 * ClassFoundListenerProvider
 *
 * This class provides a central mechanism for managing and notifying listeners that are interested
 * in discovered PHP elements (either classes or functions). It implements both the
 * ClassFoundListenerProviderInterface and the FinalizedListenerInterface.
 *
 * Listeners added to this provider will receive notifications via the handle() method
 * when a reflection element is found, and optionally perform final processing via finalize().
 */
final class ClassFoundListenerProvider implements ClassFoundListenerProviderInterface, FinalizedListenerInterface
{
    /**
     * @var ClassFoundListenerInterface[] Array of listener objects.
     */
    private array $listeners = [];

    /**
     * Constructor.
     *
     * Accepts an iterable of listeners and adds each listener to the internal list.
     *
     * @param iterable<ClassFoundListenerInterface> $listeners An iterable of listener instances.
     */
    public function __construct(
        iterable $listeners = []
    ) {
        foreach ($listeners as $listener) $this->addListener($listener);
    }

    /**
     * Adds a listener to the provider.
     *
     * @param ClassFoundListenerInterface $listener The listener instance to be added.
     */
    public function addListener(ClassFoundListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
    }

    /**
     * Finalizes the notification process.
     *
     * Calls the finalize() method on each listener that implements FinalizedListenerInterface.
     * This is intended to perform any final processing or cleanup after all PHP elements have been discovered.
     */
    public function finalize(): void
    {
        foreach ($this->listeners as $listener) {
            if ($listener instanceof FinalizedListenerInterface) $listener->finalize();
        }
    }

    /**
     * Handles a discovered reflection element.
     *
     * Iterates through all registered listeners and calls their handle() method,
     * passing the discovered reflection element (class or function) to each listener.
     *
     * @param ReflectionClass|ReflectionFunction $reflector The discovered reflection object.
     */
    public function handle(ReflectionClass|ReflectionFunction $reflector): void
    {
        foreach ($this->listeners as $listener) $listener->handle($reflector);
    }

    /**
     * Retrieves all registered class found listeners.
     *
     * @return ClassFoundListenerInterface[] Returns an array of registered listener instances.
     */
    public function getClassFoundListeners(): array
    {
        return $this->listeners;
    }

    /**
     * Factory method to create an instance of ClassFoundListenerProvider from a PSR-11 container.
     *
     * Reads the configuration from the container's 'config' element using a predefined key,
     * then instantiates the provider with the appropriate listeners.
     *
     * @param ContainerInterface $container A PSR-11 container from which to retrieve configuration.
     * @return self A new instance of ClassFoundListenerProvider configured based on the container.
     */
    public static function createFromContainer(ContainerInterface $container): self
    {
        $listeners = $container->get('config')[ConfigProvider::CONFIG_KEY_LISTENERS] ?? [];
        return new self(empty($listeners) ? [] : array_map(
            static function (ClassFoundListenerInterface|string $listener) use ($container): ClassFoundListenerInterface {
                if ($listener instanceof ClassFoundListenerInterface) return $listener;
                return $container->get($listener);
            }, $listeners
        ));
    }
}
