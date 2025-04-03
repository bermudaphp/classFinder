<?php

namespace Bermuda\ClassFinder;

use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;
use Psr\Container\ContainerInterface;

final class ClassFoundListenerProvider implements ClassFoundListenerProviderInterface, FinalizedListenerInterface
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

    public function finalize(): void
    {
        foreach ($this->listeners as $listener) $listener->finalize();
    }

    public function handle(ReflectionClass|ReflectionFunction $reflector): void
    {
        foreach ($this->listeners as $listener) $listener->handle($reflector);
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
        $listeners = $container->get('config')[ConfigProvider::CONFIG_KEY_LISTENERS] ?? [];
        return new self(empty($listeners) ? [] : array_map(
            static function (ClassFoundListenerInterface|string $listener) use ($container): ClassFoundListenerInterface {
                if ($listener instanceof ClassFoundListenerInterface) return $listener;
                return $container->get($listener);
            }, $listeners
        ));
    }
}
