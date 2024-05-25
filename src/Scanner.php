<?php

namespace Bermuda\ClassScanner\Scanner;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

final class Scanner
{
    /**
     * @var ClassFoundListenerInterface[]
     */
    private array $listeners = [];

    /**
     * @param iterable<ClassFoundListenerInterface>|null $listeners
     */
    public function __construct(
        iterable $listeners = null, 
        private readonly ClassFinderInterface $finder = new ClassFinder()
    ) {
        if ($listeners) {
            foreach ($listeners as $listener) $this->listen($listener);
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function scan(string $dir): void
    {
        foreach ($this->finder->find($dir) as $reflector) {
            foreach ($this->listeners as $listener) $listener->handle($reflector);
        }
        
        foreach ($this->listeners as $listener) $listener->finalize();
    }

    public function listen(ClassFoundListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
    }
}
