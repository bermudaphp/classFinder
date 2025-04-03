<?php

namespace Bermuda\ClassFinder;

use Psr\Container\ContainerInterface;

final class ClassFoundListenerProvider implements ClassFoundListenerProviderInterface
{
    private array $listeners = [];

    /**
     * @param iterable<ClassFoundListenerInterface> $listeners
     */
    public function __construct(
        iterable $listeners = []
    ) {
        foreach ($listeners as $listener) $this->addListener($listener);
    }

    public function addListener(ClassFoundListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
    }

    /**
     * @return ClassFoundListenerInterface[]
     */
    public function getClassFoundListeners(): array
    {
        return $this->listeners;
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return new self($container->get('config')[ConfigProvider::CONFIG_KEY_LISTENERS] ?? []);
    }
}