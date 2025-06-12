<?php

namespace Bermuda\ClassFinder;

use Bermuda\Filter\Filterable;
use Bermuda\Filter\FilterInterface;
use Bermuda\Filter\FilterableInterface;
use Bermuda\Tokenizer\ClassInfo;
use Bermuda\Tokenizer\FunctionInfo;
use Bermuda\Tokenizer\SearchMode;
use Bermuda\Tokenizer\Tokenizer;
use Bermuda\Tokenizer\TokenizerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;

/**
 * ClassFinder
 *
 * Discovers PHP elements—such as classes, interfaces, functions, enums, and traits—in the provided directories.
 * Filters can be applied to the discovered ClassInfo objects. This class uses the Tokenizer to analyze file contents
 * and Symfony Finder to locate PHP files.
 *
 * @method withFilter(FilterInterface $filter, bool $prepend = false): ClassFinder
 * @method withoutFilter(FilterInterface $filter): ClassFinder
 */
final class ClassFinder implements ClassFinderInterface, FilterableInterface
{
    use Filterable { accept as private; }

    private TokenizerInterface $tokenizer;

    /**
     * Constructor.
     *
     * @param iterable<FilterInterface>|FilterInterface $filters Either a single FilterInterface instance or an iterable of them.
     * @param TokenizerInterface|null $tokenizer Optional tokenizer instance, defaults to new Tokenizer()
     *
     * Validates the provided filters so that each one implements FilterInterface. If a single filter is passed,
     * it is wrapped into an array for uniform processing.
     *
     * @throws \InvalidArgumentException if any provided filter does not implement FilterInterface.
     */
    public function __construct(
        iterable|FilterInterface $filters = [],
        ?TokenizerInterface $tokenizer = null
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

        $this->tokenizer = $tokenizer ?? new Tokenizer();
    }

    /**
     * Finds PHP elements in the given directories and applies filters to them.
     *
     * This method creates a new ClassIterator, passing along a generator produced by createGenerator().
     * The generator scans all PHP files (using Symfony Finder and Tokenizer), and yields ClassInfo or FunctionInfo objects
     * based on parsed tokens.
     *
     * @param string|string[] $dirs One or more directories to search.
     * @param string|string[] $exclude One or more directories or file patterns to exclude from the search.
     * @param int-mask-of<TokenizerInterface::SEARCH_*> $mode SearchMode enum specifying which element types to include (default is TokenizerInterface::SEARCH_ALL).
     * @return InfoIterator An iterator over the discovered and filtered ClassInfo objects.
     */
    public function find(string|array $dirs, string|array $exclude = [], int $mode = TokenizerInterface::SEARCH_ALL): ClassIterator
    {
        return new ClassIterator($this->createGenerator($dirs, $exclude, $mode), $this->filters);
    }

    /**
     * Creates a generator that yields ClassInfo and FunctionInfo objects by scanning PHP files.
     *
     * This method uses Symfony Finder to locate PHP files within the specified directories,
     * then uses Tokenizer to parse each file. For each matching element,
     * it yields a ClassInfo or FunctionInfo object.
     *
     * @param string|string[] $dirs Directories to search.
     * @param string|string[] $exclude Patterns to exclude from the search.
     * @param int-mask-of<TokenizerInterface::SEARCH_*> $mode SearchMode enum determining which element types are of interest.
     * @return \Generator Yields ClassInfo or FunctionInfo objects.
     */
    private function createGenerator(string|array $dirs, string|array $exclude, int $mode = TokenizerInterface::SEARCH_ALL): \Generator
    {
        $files = new Finder()->in($dirs)
            ->files()
            ->exclude($exclude)
            ->name('/\.php$/');

        foreach ($files as $file) {
            try {
                $content = $file->getContents();
                $declarations = $this->tokenizer->parse($content, $mode);
                foreach ($declarations as $declaration) {
                    yield $file->getFilename() => $declaration;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    /**
     * Instantiates a ClassFinder using configuration from a PSR-11 container.
     *
     * Retrieves configuration options for filters and tokenizer based on predefined keys,
     * then returns a new ClassFinder instance accordingly.
     *
     * @param ContainerInterface $container The PSR-11 container.
     * @return ClassFinder A new instance of ClassFinder based on container configuration.
     */
    public static function createFromContainer(ContainerInterface $container): ClassFinder
    {
        $config = $container->get('config');

        $tokenizer = $container->has(TokenizerInterface::class)
            ? $container->get(TokenizerInterface::class)
            : new Tokenizer();

        return new self(
            $config[ConfigProvider::CONFIG_KEY_FILTERS] ?? [],
            $tokenizer
        );
    }
}