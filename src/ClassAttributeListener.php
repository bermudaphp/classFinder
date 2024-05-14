<?php

namespace Bermuda\ClassScanner;

/**
 * @template T
 */
final class ClassAttributeListener implements ClassFoundListenerInterface
{
    /**
     * @var callable
     */
    private $finalizer;

    /**
     * @var \ReflectionAttribute<T>[]
     */
    private array $attrs = [];

    /**
     * @param class-string<T> $attributeName
     * @param callable $finalizer
     */
    public function __construct(
        private readonly string $attributeName,
        callable $finalizer
    ) {
        $this->finalizer = $finalizer;
    }

    public function finalize(): void
    {
        ($this->finalizer)($this->attrs);
    }

    public function handle(\ReflectionClass $reflector): void
    {
        $attribute = $reflector->getAttributes($this->attributeName)[0] ?? null;
        if ($attribute) $this->attrs[] = $attribute;
    }
}
