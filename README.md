# Install
```bash
composer require bermudaphp/class-scanner
```
# Usage
```php
$routes = new \Bermuda\Routes\Routes;
$scanner = new \Bermuda\ClassScaner\Scanner;
// register a listener that will parse the <Bermuda\Router\Attribute\Route> attribute and register it in the RouteMap
$scanner->listen(new class($routes) implements \Bermuda\ClassScaner\ClassFoundListenerInterface
    {
        /**
         * @var array<int, array{0:class-string, 1: Route}>
         */
        private array $routes = [];
        public function __construct(
            private readonly RouteMap $map
        ) {
        }

        public function finalize(): void
        {
            krsort($this->routes);
            foreach ($this->routes as $priorityGroup) {
                foreach ($priorityGroup as $pair) {
                    /**
                     * @var \Bermuda\Router\Route $route
                     */
                    list($handler, $route) = $pair;

                    $routeRecord = new RouteRecord($route->name, $route->path, $handler);

                    if ($route->group) {
                        $this->map->group($route->group)->addRoute($routeRecord);
                    } else {
                        $this->map->addRoute($routeRecord);
                    }

                    $routeRecord->setMethods($route->methods);

                    if ($route->middleware) $routeRecord->setMiddleware($route->middleware);
                    if ($route->defaults)  $routeRecord->setDefaults($route->defaults);
                }
            }
        }

        public function handle(\ReflectionClass $reflector): void
        {
            $attribute = $reflector->getAttributes(Route::class)[0] ?? null;

            if ($attribute) {
                /**
                 * @var Route $route
                 */
                $route = $attribute->newInstance();
                $this->routes[$route->priority][] = [$reflector->getName(), $route];
            }
        }
    });

// The scan method will find all classes in the scanned directory and pass the ReflectionClass instance
// to the handle method of the registered listeners for each class found.
// After that, the finalize method will be called for each listener
$scanner->scan('scanning/directory');

````
