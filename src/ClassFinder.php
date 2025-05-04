<?php

namespace Bermuda\ClassFinder;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Bermuda\Filter\Filterable;
use Bermuda\Filter\FilterInterface;
use Symfony\Component\Finder\Finder;
use Psr\Container\ContainerInterface;
use Bermuda\Filter\FilterableInterface;
use Bermuda\Reflection\ReflectionClass;
use Bermuda\Reflection\ReflectionFunction;

/**
 * ClassFinder
 *
 * Discovers PHP elements—such as classes, interfaces, functions, enums, and traits—in the provided directories.
 * Filters can be applied to the discovered reflection objects. This class uses the PHP Parser to analyze file contents
 * and Symfony Finder to locate PHP files.
 *
 * @method withFilter(FilterInterface $filter, bool $prepend = false): ClassFinder
 * @method withoutFilter(FilterInterface $filter): ClassFinder
 */
final class ClassFinder implements ClassFinderInterface, FilterableInterface
{
    use Filterable { accept as private; }

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
     * @param iterable<FilterInterface>|FilterInterface $filters Either a single FilterInterface instance or an iterable of them.
     *
     * Validates the provided filters so that each one implements FilterInterface. If a single filter is passed,
     * it is wrapped into an array for uniform processing.
     *
     * @throws \InvalidArgumentException if any provided filter does not implement FilterInterface.
     */
    public function __construct(
        iterable|FilterInterface $filters = []
    ) {
        if ($filters instanceof FilterInterface) {
            $this->filters = [$filters];
        } else {
            foreach ($filters as $i => $filter) {
                if (!$filter instanceof FilterInterface) {
                    throw new \InvalidArgumentException(
                        "\$filters[$i] passed to Bermuda\ClassFinder\ClassFinder must implement FilterInterface"
                    );
                }
                $this->filters[] = $filter;
            }
        }
    }

    /**
     * Finds PHP elements in the given directories and applies filters to them.
     *
     * This method creates a new ReflectorIterator, passing along a generator produced by createGenerator().
     * The generator scans all PHP files (using Symfony Finder and PhpParser), and yields reflection objects – either
     * ReflectionClass or ReflectionFunction – based on parsed AST nodes.
     *
     * @param string|string[] $dirs One or more directories to search.
     * @param string|string[] $exclude One or more directories or file patterns to exclude from the search.
     * @param int-mask-of<self::MODE_*> $modeFlag Bitmask (using self::MODE_* constants) specifying which node types to include (default is MODE_FIND_ALL).
     * @return ReflectorIterator An iterator over the discovered and filtered reflection objects.
     */
    public function find(string|array $dirs, string|array $exclude = [], int $mode = self::MODE_FIND_ALL): ReflectorIterator
    {
        return new ReflectorIterator($this->createGenerator($dirs, $exclude, $mode), $this->filters);
    }

    /**
     * Creates a generator that yields reflection objects by scanning PHP files.
     *
     * This method uses Symfony Finder to locate PHP files within the specified directories,
     * then uses PhpParser to parse each file into an Abstract Syntax Tree (AST). An AST filter (built via createAstFilter())
     * is applied to select only the nodes corresponding to the desired PHP elements. For each matching node,
     * it determines the fully-qualified name (taking namespaces into account) and yields a ReflectionClass or ReflectionFunction.
     *
     * @param string|string[] $dirs Directories to search.
     * @param string|string[] $exclude Patterns to exclude from the search.
     * @param int-mask-of<self::MODE_*> $modeFlag Bitmask (using self::MODE_* constants) determining which node types are of interest.
     * @return \Generator Yields reflection objects.
     */
    private function createGenerator(string|array $dirs, string|array $exclude, int $modeFlag = self::MODE_FIND_ALL): \Generator
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new NodeFinder;

        $files = new Finder()->in($dirs)
            ->files()
            ->exclude($exclude)
            ->name('/\.php$/');


        $filter = $this->createAstFilter($modeFlag);

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
     * Creates an AST filter callable based on the given mode flag.
     *
     * The returned callable checks if an AST node is an instance of one of the desired node types (such as classes,
     * interfaces, enums, traits, or functions), determined by comparing against the internal nodeMap.
     *
     * @param int-mask-of<self::MODE_*> $modeFlag Bitmask (using self::MODE_* constants) specifying which node types should be accepted.
     * @return callable A callable that accepts an AST Node and returns true if it matches one of the desired types.
     */
    private function createAstFilter(int $modeFlag): callable
    {
        $nodes = [Node\Stmt\Namespace_::class];

        foreach ($this->nodeMap as $mode => $node) if ($modeFlag&$mode) $nodes[] = $node;

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
}
