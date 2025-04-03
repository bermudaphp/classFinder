<?php

namespace Bermuda\ClassFinder;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;
use Bermuda\ClassFinder\Filter\FilterInterface;

final class ClassFinder implements ClassFinderInterface
{
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
     * @param string|string[] $dirs
     * @param string|string[] $exclude
     * @return \Generator<ReflectionClass|\ReflectionFunction>
     * @throws \ReflectionException
     */
    public function find(string|array $dirs, string|array $exclude = []): \Generator
    {
        $filters = $this->filters;
        $classes = $this->createGenerator($dirs, $exclude);

        while (($filter = array_shift($filters)) !== null) {
            $classes = $filter->setClasses($classes);
        }

        foreach ($classes as $class) yield $class;
    }

    private function createGenerator(string|array $dirs, string|array $exclude): \Generator
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new NodeFinder;

        /*
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = new \RegexIterator($files, '/\.php$/');
        */

        $files = new Finder()->in($dirs)
            ->files()
            ->exclude($exclude)
            ->name('/\.php$/');

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
                        $name = $node->name->toString();
                    } else {
                        $name = $namespace ? sprintf('%s\%s', $namespace->name->toString(),
                            $node->name->toString()
                        )
                            : $node->name->toString();
                    }

                    try {
                        yield new ReflectionClass($name);
                    } catch (\Throwable $e) {
                        yield new ReflectionFunction($name);
                    }

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

        if ($this->mode&self::MODE_FIND_FUNCTIONS) {
            $nodes[] = Node\Stmt\Function_::class;
        }

        return static function(Node $node) use ($nodes): bool {
            foreach ($nodes as $n) if ($node instanceof $n) return true;
            return false;
        };
    }
    
    public static function createFromContainer(ContainerInterface $container): ClassFinder
    {
        $config = $container->get('config');
        
        return new self(
            $config[ConfigProvider::CONFIG_KEY_MODE] ?? self::MODE_FIND_ALL,
            $config[ConfigProvider::CONFIG_KEY_FILTERS] ?? []
        );
    }
}
