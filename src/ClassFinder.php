<?php

namespace Bermuda\ClassScanner;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Bermuda\ClassScanner\Filter\FilterInterface;

final class ClassFinder implements ClassFinderInterface
{
    const int MODE_FIND_INTERFACES = 1;
    const int MODE_FIND_CLASSES = 2;
    const int MODE_FIND_ENUMS = 3;
    const int MODE_FIND_TRAITS = 4;
    const int MODE_FIND_ALL = self::MODE_FIND_CLASSES | self::MODE_FIND_ENUMS | self::MODE_FIND_TRAITS | self::MODE_FIND_INTERFACES;

    /**
     * @var FilterInterface[]
     */
    private array $filters = [];

    public function __construct(
        public int $mode = self::MODE_FIND_CLASSES,
        iterable $filters = []
    ) {
        foreach ($filters as $filter) $this->addFilter($filter);
    }

    public function withFilter(FilterInterface $filter): self
    {
        $copy = new self($this->mode);
        $copy->addFilter($filter);

        return $copy;
    }

    /**
     * @param iterable<FilterInterface> $filters
     * @return self
     */
    public function withFilters(iterable $filters): self
    {
        $copy = new self($this->mode);
        foreach ($filters as $filter) $copy->addFilter($filter);

        return $copy;
    }

    private function addFilter(FilterInterface $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * @return \Generator<\ReflectionClass>
     * @throws \ReflectionException
     */
    public function find(string $dir): \Generator
    {
        $filters = $this->filters;
        $classes = $this->createGenerator($dir);

        while (($filter = array_shift($filters)) !== null) {
            $classes = $filter->setClasses($classes);
        }

        foreach ($classes as $class) yield $class;
    }

    private function createGenerator(string $dir): \Generator
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new NodeFinder;

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = new \RegexIterator($files, '/\.php$/');

        $filter = $this->createAstFilter();

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

                    yield new \ReflectionClass($cls);
                }
            }
        }
    }

    private function createAstFilter(): callable
    {
        $nodes = [
            Node\Stmt\Namespace_::class,
        ];

        if ($this->mode&self::MODE_FIND_CLASSES) {
            array_unshift($nodes, Node\Stmt\Class_::class);
        }
        
        if ($this->mode&self::MODE_FIND_INTERFACES) {
            $nodes[] = Node\Stmt\Interface_::class;
        }

        if ($this->mode&self::MODE_FIND_ENUMS) {
            $nodes[] = Node\Stmt\Enum_::class;
        }

        if ($this->mode&self::MODE_FIND_TRAITS) {
            $nodes[] = Node\Stmt\Trait_::class;
        }

        return static function(Node $node) use ($nodes): bool {
            foreach ($nodes as $n) if ($node instanceof $n) return true;
            return false;
        };
    }
}
