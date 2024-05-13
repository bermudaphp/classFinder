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
     * @throws \ReflectionException
     */
    public function scan(string $dir): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new NodeFinder;

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = new \RegexIterator($files, '/\.php$/');

        $filter = static function(Node $node): bool {
            return ($node instanceof Node\Stmt\Class_
                || $node instanceof Node\Stmt\Namespace_);
        };

        foreach ($files as $file) {
            $code = file_get_contents($file->getPathName());

            // parse
            $ast = $parser->parse($code);
            $ast = $finder->find($ast, $filter);

            $namespace = null;
            /**
             * @var Node\Stmt\Class_ $node
             */
            foreach ($ast as $node) {
                if ($node instanceof Node\Stmt\Namespace_) {
                    $namespace = $node;
                    continue;
                };
                if ($node->name) {
                    if ($node->name instanceof Node\Name\FullyQualified) {
                        $cls = $node->name->toString();
                    } else {
                        $cls = $namespace ? sprintf('%s\%s', $namespace->name->toString(),
                            $node->name->toString()
                        )
                            : $node->name->toString();
                    }
                    foreach ($this->listeners as $listener) {
                        $listener->handle(new \ReflectionClass($cls));
                    }
                }
            }
        }

        foreach ($this->listeners as $listener) {
            $listener->finalize();
        }
    }

    public function listen(ClassFoundListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
    }
}
