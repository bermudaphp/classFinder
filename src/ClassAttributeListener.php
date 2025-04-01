<?php

namespace Bermuda\ClassScanner;

use Bermuda\Reflection\ReflectionClass;

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
     * @template T
     * @param class-string<T> $attributeName
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

    public function handle(ReflectionClass $reflector): void
    {
        if ($reflector->hasAttribute($this->attributeName))) $reflector->getAttribute($this->attributeName);
    }
}
