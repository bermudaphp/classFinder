# Bermuda Class Finder

**[Русская версия](README.RU.md)**

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A powerful and flexible PHP library for discovering and filtering PHP classes and related elements (classes, interfaces, traits, enums) in your codebase. Built with performance in mind and leveraging PHP 8.4+ features.

## Features

- 🔍 **Smart Discovery**: Find classes, interfaces, traits, and enums in specified directories
- 🎯 **Advanced Filtering**: Rich set of filters with optimized performance
- 🚀 **High Performance**: Optimized algorithms with early termination and efficient pattern matching
- 🔄 **Event System**: Listener-based notifications for discovered elements
- 🎨 **Attribute Support**: Deep search through PHP 8+ attributes with pattern matching
- 📦 **PSR-11 Compatible**: Full dependency injection container support
- 🧩 **Extensible**: Easy to extend with custom filters and listeners

## Installation

```bash
composer require bermudaphp/finder
```

## Requirements

- PHP 8.4 or higher
- Composer

## Quick Start

### Basic Usage

```php
use Bermuda\ClassFinder\ClassFinder;

// Create finder instance
$finder = new ClassFinder();

// Find all PHP classes in directories
$classes = $finder->find(['src/', 'app/']);

foreach ($classes as $filename => $classInfo) {
    echo "Found: {$classInfo->fullQualifiedName} in {$filename}\n";
}
```

### With Filters

```php
use Bermuda\ClassFinder\ClassFinder;
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    ImplementsFilter,
    AttributeSearchFilter
};

// Find instantiable classes that implement specific interfaces
$finder = new ClassFinder([
    new InstantiableFilter(),
    new ImplementsFilter(['Serializable', 'Countable']),
    new AttributeSearchFilter(['Route'])
]);

$results = $finder->find('src/');
```

## Available Filters

### Class Type Filters

#### InstantiableFilter
Finds classes that can be instantiated (not abstract, interfaces, or traits):

```php
use Bermuda\ClassFinder\Filter\InstantiableFilter;

$filter = new InstantiableFilter();
// Matches: class UserService { ... }
// Excludes: abstract class BaseService { ... }
```

#### IsAbstractFilter
Finds abstract classes:

```php
use Bermuda\ClassFinder\Filter\IsAbstractFilter;

$filter = new IsAbstractFilter();
// Matches: abstract class BaseController { ... }
```

#### IsFinalFilter
Finds final classes:

```php
use Bermuda\ClassFinder\Filter\IsFinalFilter;

$filter = new IsFinalFilter();
// Matches: final class UserService { ... }
```

### Interface and Inheritance Filters

#### ImplementsFilter
Finds classes implementing specific interfaces:

```php
use Bermuda\ClassFinder\Filter\ImplementsFilter;

// Single interface
$filter = new ImplementsFilter('Serializable');

// Multiple interfaces (ALL required - AND logic)
$filter = new ImplementsFilter(['Serializable', 'Countable']);

// Any interface (OR logic)
$filter = ImplementsFilter::implementsAny(['Serializable', 'Countable']);
```

#### SubclassFilter
Finds classes extending a specific parent class:

```php
use Bermuda\ClassFinder\Filter\SubclassFilter;

$filter = new SubclassFilter('App\\Controller\\AbstractController');
// Finds all classes extending AbstractController
```

### Callable Filter

#### CallableFilter
Finds callable classes (classes with `__invoke` method):

```php
use Bermuda\ClassFinder\Filter\CallableFilter;

$filter = new CallableFilter();
// Matches: class MyClass { public function __invoke() { ... } }
```

### Pattern Matching Filters

#### PatternFilter
Smart pattern matching with automatic target detection:

```php
use Bermuda\ClassFinder\Filter\PatternFilter;

// Search in class names
$filter = new PatternFilter('*Controller');     // Classes ending with "Controller"
$filter = new PatternFilter('Abstract*');       // Classes starting with "Abstract"
$filter = new PatternFilter('*Test*');          // Classes containing "Test"

// Search in namespaces
$filter = new PatternFilter('App\\Controllers\\*');  // Classes in Controllers namespace

// Search in full qualified names
$filter = new PatternFilter('*\\Api\\*Controller');  // API controllers

// Static helpers for common patterns
$filter = PatternFilter::exactMatch('UserController');
$filter = PatternFilter::contains('Service');
$filter = PatternFilter::startsWith('Abstract');
$filter = PatternFilter::endsWith('Controller');
$filter = PatternFilter::namespace('App\\Services');
```

### Attribute Filters

#### Deep Search Feature

The `deepSearch` parameter in attribute filters controls the scope of attribute searching:

- **`deepSearch: false` (default)**: Searches only for attributes on the class itself
- **`deepSearch: true`**: Extends search to include attributes on class members:
  - Method attributes
  - Property attributes  
  - Constant attributes

```php
// Example class with attributes at different levels
class UserController 
{
    #[Inject]
    private UserService $userService;
    
    #[Route('/users')]
    #[Auth('admin')]
    public function index(): Response 
    {
        // method implementation
    }
    
    #[Deprecated]
    public const STATUS_ACTIVE = 1;
}
```

#### AttributeSearchFilter
Advanced attribute filtering with multiple search options:

```php
use Bermuda\ClassFinder\Filter\AttributeSearchFilter;

// Basic search - only class-level attributes
$filter = new AttributeSearchFilter(['Route', 'Controller']);

// Deep search - includes method, property, and constant attributes
$filter = new AttributeSearchFilter(['Inject'], deepSearch: true);
// Will find UserController because $userService has #[Inject]

// Mixed exact names and patterns with deep search
$filter = new AttributeSearchFilter(['Route', '*Test*', 'Api*'], deepSearch: true);

// AND logic with deep search - must have ALL attributes (anywhere in class)
$filter = new AttributeSearchFilter(['Route', 'Auth'], matchAll: true, deepSearch: true);
// Will find UserController because it has both #[Route] and #[Auth] on methods

// Comparison of search scopes:
// Without deep search - only finds classes with Route attribute on class declaration
$classOnlyFilter = new AttributeSearchFilter(['Route'], deepSearch: false);

// With deep search - finds classes with Route on class OR on any method/property/constant
$deepFilter = new AttributeSearchFilter(['Route'], deepSearch: true);

// Static helpers with deep search
$filter = AttributeSearchFilter::hasAttribute('Inject', deepSearch: true);
$filter = AttributeSearchFilter::hasAnyAttribute(['Route', 'Controller'], deepSearch: true);
$filter = AttributeSearchFilter::hasAllAttributes(['Route', 'Middleware'], deepSearch: true);
```

#### AttributePatternFilter
Pattern matching for attribute names:

```php
use Bermuda\ClassFinder\Filter\AttributePatternFilter;

// Basic pattern matching - only class-level attributes
$filter = new AttributePatternFilter('*Route*');
$filter = new AttributePatternFilter('Api*');

// Deep search - includes attributes on methods, properties, constants
$filter = new AttributePatternFilter('*Route*', deepSearch: true);
// Will find UserController because index() method has #[Route('/users')]

$filter = new AttributePatternFilter('*Inject*', deepSearch: true);
// Will find UserController because $userService property has #[Inject]

// Comparison of search scopes:
// Without deep search - only finds classes with Http* attributes on class
$classOnlyFilter = new AttributePatternFilter('Http*', deepSearch: false);

// With deep search - finds classes with Http* on class OR members
$deepFilter = new AttributePatternFilter('Http*', deepSearch: true);

// Optimized static helpers with deep search
$filter = AttributePatternFilter::exactAttribute('HttpGet', deepSearch: true);
$filter = AttributePatternFilter::anyAttribute(['Route', 'HttpGet'], deepSearch: true);
$filter = AttributePatternFilter::attributePrefix('Http', deepSearch: true);
```

## Filter Combination

### ChainableFilter (AND Logic)
Combines multiple filters with AND logic - an element must pass ALL filters to be accepted:

```php
use Bermuda\Filter\ChainableFilter;
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    PatternFilter,
    AttributeSearchFilter
};

// All conditions must be true
$chainFilter = new ChainableFilter([
    new InstantiableFilter(),                // AND: must be instantiable
    new PatternFilter('*Controller'),        // AND: must end with "Controller"
    new AttributeSearchFilter(['Route'])     // AND: must have Route attribute
]);

$finder = new ClassFinder([$chainFilter]);
$strictControllers = $finder->find('src/Controllers/');
```

### OneOfFilter (OR Logic)
Combines multiple filters with OR logic - an element only needs to pass ONE filter to be accepted:

```php
use Bermuda\Filter\OneOfFilter;
use Bermuda\ClassFinder\Filter\{
    PatternFilter,
    AttributeSearchFilter,
    ImplementsFilter
};

// Any condition can be true
$orFilter = new OneOfFilter([
    new PatternFilter('*Controller'),            // OR: ends with "Controller"
    new PatternFilter('*Service'),               // OR: ends with "Service"
    new AttributeSearchFilter(['Component']),    // OR: has Component attribute
    new ImplementsFilter('App\\Contracts\\HandlerInterface') // OR: implements HandlerInterface
]);

$finder = new ClassFinder([$orFilter]);
$flexibleResults = $finder->find('src/');
```

### Complex Filter Combinations
Combine different logic types for sophisticated filtering:

```php
use Bermuda\Filter\{ChainableFilter, OneOfFilter};
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    PatternFilter,
    AttributeSearchFilter,
    ImplementsFilter
};

// Complex logic: Must be instantiable AND (Controller OR Service OR has Route attribute)
$complexFilter = new ChainableFilter([
    new InstantiableFilter(),  // Must be instantiable
    new OneOfFilter([          // AND any of these:
        new PatternFilter('*Controller'),        // - ends with "Controller"
        new PatternFilter('*Service'),           // - ends with "Service"  
        new AttributeSearchFilter(['Route'])     // - has Route attribute
    ])
]);

$finder = new ClassFinder([$complexFilter]);
$results = $finder->find('src/');
```

## Advanced Usage

### Combining Multiple Filters

```php
use Bermuda\ClassFinder\ClassFinder;
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    PatternFilter,
    AttributeSearchFilter
};

$finder = new ClassFinder([
    new InstantiableFilter(),                    // Only instantiable classes
    new PatternFilter('*Controller'),            // Class names ending with "Controller"
    new AttributeSearchFilter(['Route'])         // Must have Route attribute
]);

$controllers = $finder->find('src/Controllers/');
```

### Using with Dependency Injection

```php
use Bermuda\ClassFinder\ClassFinder;
use Psr\Container\ContainerInterface;

// Create from container
$finder = ClassFinder::createFromContainer($container);
```

### Event Listeners

Listen for discovered classes:

```php
use Bermuda\ClassFinder\{ClassFinder, ClassNotifier};
use Bermuda\ClassFinder\ClassFoundListenerInterface;
use Bermuda\Tokenizer\ClassInfo;

class MyListener implements ClassFoundListenerInterface
{
    public function handle(ClassInfo $info): void
    {
        echo "Found class: {$info->fullQualifiedName}\n";
    }
}

// Create notifier with listener
$notifier = new ClassNotifier([new MyListener()]);

// Find and notify
$finder = new ClassFinder();
$classes = $finder->find('src/');
$notifier->notify($classes);
```

### Search Modes

Control what types of class elements to discover using bitwise flags:

```php
use Bermuda\ClassFinder\ClassFinder;
use Bermuda\Tokenizer\TokenizerInterface;

$finder = new ClassFinder();

// Search only for classes
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_CLASSES);

// Search only for interfaces
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_INTERFACES);

// Search only for traits
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_TRAITS);

// Search only for enums
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_ENUMS);

// Combine search modes with bitwise OR
$results = $finder->find('src/', [], 
    TokenizerInterface::SEARCH_CLASSES | TokenizerInterface::SEARCH_INTERFACES
);

// Search for everything (default) - classes, interfaces, traits, enums
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_ALL);
```

#### Available Search Mode Constants:
- `SEARCH_CLASSES = 1` - Find only classes
- `SEARCH_INTERFACES = 2` - Find only interfaces  
- `SEARCH_TRAITS = 4` - Find only traits
- `SEARCH_ENUMS = 8` - Find only enums
- `SEARCH_ALL = 15` - Find all OOP elements (default)

### Working with Results

```php
use Bermuda\ClassFinder\ClassFinder;

$finder = new ClassFinder();
$classes = $finder->find('src/');

// Count results
echo "Found " . count($classes) . " classes\n";

// Convert to array
$array = $classes->toArray();

// Add filters dynamically
$filtered = $classes->withFilter(new InstantiableFilter());

// Remove filters
$unfiltered = $classes->withoutFilter($someFilter);
```

## Performance Tips

1. **Use specific filters**: The more specific your filters, the faster the search
2. **Order matters**: Place the most restrictive filters first  
3. **Pattern optimization**: Simple patterns (prefix*, *suffix, *contains*) are automatically optimized
4. **Reflection caching**: Repeated searches on the same classes benefit from reflection caching

## Configuration

### Container Configuration

```php
// config/dependencies.php
use Bermuda\ClassFinder\{
    ClassFinder,
    ClassFoundListenerProviderInterface,
    ClassFoundListenerProvider,
    ConfigProvider
};

return [
    'config' => [
        ConfigProvider::CONFIG_KEY_FILTERS => [
            // Your default filters
        ],
        ConfigProvider::CONFIG_KEY_LISTENERS => [
            // Your listeners
        ]
    ],
    
    ClassFoundListenerProviderInterface::class => 
        fn() => ClassFoundListenerProvider::createFromContainer($container)
];
```

## Examples

### Find All Controllers

```php
$finder = new ClassFinder([
    new InstantiableFilter(),
    new PatternFilter('*Controller'),
    new SubclassFilter('App\\Controller\\BaseController')
]);

$controllers = $finder->find('src/Controllers/');
```

### Find Services with Dependency Injection

```php
$finder = new ClassFinder([
    new InstantiableFilter(),
    new AttributeSearchFilter(['Service'], deepSearch: true),
    new PatternFilter('*Service')
]);

$services = $finder->find('src/Services/');
```

### Find Classes with Deep Attribute Search

```php
// Find all classes that use dependency injection anywhere in the class
$finder = new ClassFinder([
    new AttributeSearchFilter(['Inject', 'Autowired'], deepSearch: true)
]);

$diClasses = $finder->find('src/');

// Find API-related classes by checking for routing attributes in methods
$finder = new ClassFinder([
    new InstantiableFilter(),
    new AttributePatternFilter('*Route*', deepSearch: true) // Checks class AND method attributes
]);

$apiClasses = $finder->find('src/');
```

### Find Test Classes

```php
$finder = new ClassFinder([
    new PatternFilter('*Test'),
    new SubclassFilter('PHPUnit\\Framework\\TestCase')
]);

$tests = $finder->find('tests/');
```

### Find API Endpoints

```php
$finder = new ClassFinder([
    new InstantiableFilter(),
    new AttributeSearchFilter(['Route', 'ApiResource'], deepSearch: true)
]);

$endpoints = $finder->find('src/Api/');
```

### Complex Filter Logic

```php
use Bermuda\Filter\{ChainableFilter, OneOfFilter};

// Find classes that are either Controllers OR Services, but must be instantiable
$finder = new ClassFinder([
    new InstantiableFilter(),  // Must be instantiable
    new OneOfFilter([          // AND (Controller OR Service)
        new PatternFilter('*Controller'),
        new PatternFilter('*Service')
    ])
]);

$handleableClasses = $finder->find('src/');
```

### Flexible Component Discovery

```php
use Bermuda\Filter\OneOfFilter;

// Find any component-like classes using multiple criteria
$componentFilter = new OneOfFilter([
    new AttributeSearchFilter(['Component', 'Service', 'Repository']),
    new ImplementsFilter(['App\\Contracts\\ComponentInterface']),
    new PatternFilter('App\\Components\\*')
]);

$finder = new ClassFinder([$componentFilter]);
$components = $finder->find('src/');
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
