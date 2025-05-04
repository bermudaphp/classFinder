<?php

namespace Bermuda\ClassFinder;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Bermuda\Filter\FilterInterface;
use Bermuda\Filter\FilterableInterface;
use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

/**
 * ClassFinder
 *
 * Discovers classes, interfaces, functions, enums, and traits within provided directories.
 * Filters can be applied to the discovered reflection objects. This class uses the PHP Parser
 * to analyze file contents and Symfony Finder to locate PHP files.
 */
final class ClassFinder implements ClassFinderInterface, FilterableInterface
{
    /**
     * @var FilterInterface[] Array of filters to apply on discovered reflection objects.
     */
    private array $filters = [];

    /**
     * Maps each search mode flag to its corresponding AST node class.
     */
    private array $nodeMap = [
        self::MODE_FIND_CLASSES => Node\Stmt\Class_::class,
        self::MODE_FIND_INTERFACES => Node\Stmt\Interface_::class,
        self::MODE_FIND_ENUMS => Node\Stmt\Enum_::class,
        self::MODE_FIND_TRAITS => Node\Stmt\Trait_::class,
        self::MODE_FIND_FUNCTIONS => Node\Stmt\Function_::class,
    ];

    /**
     * Constructor.
     *
     * @param int-mask-of<self::MODE_*> $mode The mode that determines which PHP elements to search for
     *                  (e.g. MODE_FIND_CLASSES, MODE_FIND_INTERFACES, etc.).
     * @param iterable<FilterInterface>|FilterInterface $filters
     */
    public function __construct(
        public readonly int $mode = self::MODE_FIND_CLASSES,
        iterable $filters = []
    ) {
        if ($filters instanceof FilterInterface) {
            $this->filters = [$filters];
        } else foreach ($filters as $filter) $this->addFilter($filter);
    }

    /**
     * Returns a new instance with the specified mode.
     *
     * @param int-mask-of<self::MODE_*> $mode The new mode for finding elements.
     * @return ClassFinderInterface New instance of ClassFinder with the updated mode.
     */
    public function withMode(int $mode): ClassFinderInterface
    {
        $copy = new self($mode);
        $copy->filters = $this->filters;

        return $copy;
    }

    /**
     * Returns a new instance with an additional filter.
     *
     * @param FilterInterface $filter The filter to add.
     * @param bool $prepend Whether to prepend the filter (default is false, meaning the filter will be appended).
     * @return self A new ClassFinder instance with the added filter.
     */
    public function withFilter(FilterInterface $filter, bool $prepend = false): ClassFinder
    {
        $copy = new self($this->mode);
        $copy->addFilter($filter, $prepend);

        return $copy;
    }

    /**
     * Returns a new instance without the specified filter.
     *
     * @param FilterInterface $filter The filter to remove.
     * @return ClassFinder A new ClassFinder instance without the specified filter.
     */
    public function withoutFilter(FilterInterface $filter): ClassFinder
    {
        $copy = new self($this->mode);
        $copy->filters = array_filter($this->filters, static fn ($f) => $f !== $filter);

        return $copy;

    }

    /**
     * Finds PHP elements in the given directories and applies filters to them.
     *
     * @param string|string[] $dirs One or more directories to search.
     * @param string|string[] $exclude One or more directories or file patterns to exclude.
     * @return \Generator<\ReflectionClass|\ReflectionFunction> Yields reflection objects (ReflectionClass or ReflectionFunction).
     * @throws \ReflectionException If reflection fails.
     */
    public function find(string|array $dirs, string|array $exclude = []): \Generator
    {
        foreach ($this->createGenerator($dirs, $exclude) as $i => $reflector) {
            if (array_any($this->filters, static fn(FilterInterface $filter) => $filter->accept($i, $reflector))) {
                yield $i => $reflector;
            }
        }
    }

    /**
     * Creates a generator that yields reflection objects by scanning PHP files.
     *
     * This method uses Symfony Finder to locate PHP files in the provided directories,
     * then uses PhpParser to parse their contents, and finally uses an AST filter to select
     * nodes corresponding to PHP elements.
     *
     * @param string|string[] $dirs Directories to search.
     * @param string|string[] $exclude Patterns to exclude from search.
     * @return \Generator Yields reflection objects.
     */
    private function createGenerator(string|array $dirs, string|array $exclude): \Generator
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new NodeFinder;

        $files = new Finder()->in($dirs)
            ->files()
            ->exclude($exclude)
            ->name('/\.php$/');


        $filter = $this->createAstFilter();

        foreach ($files as $file) {

            $code = $file->getContents();

            // parse
            $ast = $parser->parse($code);
            $ast = $finder->find($ast, $filter);

            $namespace = null;
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

                    if ($this->isClassLikeNode($node)) {
                        yield new ReflectionClass($name);
                    } elseif ($node instanceof Node\Stmt\Function_) {
                        yield new ReflectionFunction($name);
                    }
                }
            }
        }
    }

    /**
     * Determines if the given node represents a class-like reflection.
     *
     * This method checks whether the node is a Class, Interface, Trait, or Enum.
     *
     * @param mixed $node The AST node to check.
     * @return bool True if the node is class-like, false otherwise.
     */
    private function isClassLikeNode(mixed $node): bool
    {
        return $node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_
            || $node instanceof Node\Stmt\Trait_ || $node instanceof Node\Stmt\Enum_;
    }

    /**
     * Creates an AST filter callable based on the current mode.
     *
     * The callable checks if a given AST node is an instance of one of the desired node types,
     * such as classes, interfaces, enums, traits, or functions.
     *
     * @return callable A callable which accepts a Node and returns true if it matches.
     */
    private function createAstFilter(): callable
    {
        $nodes = [Node\Stmt\Namespace_::class];

        foreach ($this->nodeMap as $mode => $node) if ($this->mode&$mode) $nodes[] = $node;

        return static fn(Node $node): bool => array_any($nodes,
            static fn(string $nodeClass) => $node::class === $nodeClass
        );
    }

    /**
     * Instantiates a ClassFinder using configuration from a PSR-11 container.
     *
     * Retrieves configuration options for the mode and filters based on predefined keys,
     * then returns a new ClassFinder instance accordingly.
     *
     * @param ContainerInterface $container The PSR-11 container.
     * @return ClassFinder A new instance of ClassFinder based on container configuration.
     */
    public static function createFromContainer(ContainerInterface $container): ClassFinder
    {
        $config = $container->get('config');
        
        return new self(
            $config[ConfigProvider::CONFIG_KEY_MODE] ?? self::MODE_FIND_ALL,
            $config[ConfigProvider::CONFIG_KEY_FILTERS] ?? []
        );
    }

    /**
     * Adds a filter to the internal filters array.
     *
     * @param FilterInterface $filter The filter object to add.
     * @param bool $prepend If TRUE, the filter will be added to the beginning of the array.
     */
    private function addFilter(FilterInterface $filter, bool $prepend = false): void
    {
        $prepend ? array_unshift($this->filters, $filter)
            : $this->filters[] = $filter;
    }
}
