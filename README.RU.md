# Bermuda Class Finder

**[English version](README.md)**

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Мощная и гибкая PHP библиотека для поиска и фильтрации PHP классов и связанных элементов (классы, интерфейсы, трейты, перечисления) в вашем коде. Создана с учётом производительности и использует возможности PHP 8.4+.

## Возможности

- 🔍 **Умный поиск**: Находит классы, интерфейсы, трейты и перечисления в указанных директориях
- 🎯 **Продвинутая фильтрация**: Богатый набор фильтров с оптимизированной производительностью
- 🚀 **Высокая производительность**: Оптимизированные алгоритмы с ранним завершением и эффективным сопоставлением паттернов
- 🔄 **Система событий**: Уведомления на основе слушателей для найденных классов
- 🎨 **Поддержка атрибутов**: Глубокий поиск через атрибуты PHP 8+ с сопоставлением паттернов
- 📦 **Совместимость с PSR-11**: Полная поддержка контейнеров внедрения зависимостей
- 🧩 **Расширяемость**: Легко расширить пользовательскими фильтрами и слушателями

## Установка

```bash
composer require bermudaphp/finder
```

## Требования

- PHP 8.4 или выше

## Быстрый старт

### Базовое использование

```php
use Bermuda\ClassFinder\ClassFinder;

// Создание экземпляра поисковика
$finder = new ClassFinder();

// Поиск всех PHP классов в директориях
$classes = $finder->find(['src/', 'app/']);

foreach ($classes as $filename => $classInfo) {
    echo "Найден: {$classInfo->fullQualifiedName} в {$filename}\n";
}
```

### С фильтрами

```php
use Bermuda\ClassFinder\ClassFinder;
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    ImplementsFilter,
    AttributeSearchFilter
};

// Поиск инстанцируемых классов, реализующих определённые интерфейсы
$finder = new ClassFinder([
    new InstantiableFilter(),
    new ImplementsFilter(['Serializable', 'Countable']),
    new AttributeSearchFilter(['Route'])
]);

$results = $finder->find('src/');
```

## Доступные фильтры

### Фильтры типов классов

#### InstantiableFilter
Находит классы, которые можно инстанцировать (не абстрактные, не интерфейсы и не трейты):

```php
use Bermuda\ClassFinder\Filter\InstantiableFilter;

$filter = new InstantiableFilter();
// Подходит: class UserService { ... }
// Исключает: abstract class BaseService { ... }
```

#### IsAbstractFilter
Находит абстрактные классы:

```php
use Bermuda\ClassFinder\Filter\IsAbstractFilter;

$filter = new IsAbstractFilter();
// Подходит: abstract class BaseController { ... }
```

#### IsFinalFilter
Находит финальные классы:

```php
use Bermuda\ClassFinder\Filter\IsFinalFilter;

$filter = new IsFinalFilter();
// Подходит: final class UserService { ... }
```

### Фильтры интерфейсов и наследования

#### ImplementsFilter
Находит классы, реализующие определённые интерфейсы:

```php
use Bermuda\ClassFinder\Filter\ImplementsFilter;

// Один интерфейс
$filter = new ImplementsFilter('Serializable');

// Несколько интерфейсов (ВСЕ обязательны - логика И)
$filter = new ImplementsFilter(['Serializable', 'Countable']);

// Любой интерфейс (логика ИЛИ)
$filter = ImplementsFilter::implementsAny(['Serializable', 'Countable']);
```

#### SubclassFilter
Находит классы, наследующие определённый родительский класс:

```php
use Bermuda\ClassFinder\Filter\SubclassFilter;

$filter = new SubclassFilter('App\\Controller\\AbstractController');
// Находит все классы, наследующие AbstractController
```

### Фильтр вызываемых элементов

#### CallableFilter
Находит вызываемые классы (классы с методом `__invoke`):

```php
use Bermuda\ClassFinder\Filter\CallableFilter;

$filter = new CallableFilter();
// Подходит: class MyClass { public function __invoke() { ... } }
```

### Фильтры сопоставления паттернов

#### PatternFilter
Умное сопоставление паттернов с автоматическим определением цели:

```php
use Bermuda\ClassFinder\Filter\PatternFilter;

// Поиск в именах классов
$filter = new PatternFilter('*Controller');     // Классы, заканчивающиеся на "Controller"
$filter = new PatternFilter('Abstract*');       // Классы, начинающиеся с "Abstract"
$filter = new PatternFilter('*Test*');          // Классы, содержащие "Test"

// Поиск в пространствах имён
$filter = new PatternFilter('App\\Controllers\\*');  // Классы в пространстве имён Controllers

// Поиск в полных именах классов
$filter = new PatternFilter('*\\Api\\*Controller');  // API контроллеры

// Статические помощники для распространённых паттернов
$filter = PatternFilter::exactMatch('UserController');
$filter = PatternFilter::contains('Service');
$filter = PatternFilter::startsWith('Abstract');
$filter = PatternFilter::endsWith('Controller');
$filter = PatternFilter::namespace('App\\Services');
```

### Фильтры атрибутов

#### Функция глубокого поиска

Параметр `deepSearch` в фильтрах атрибутов управляет областью поиска атрибутов:

- **`deepSearch: false` (по умолчанию)**: Ищет только атрибуты на самом классе
- **`deepSearch: true`**: Расширяет поиск, включая атрибуты на членах класса:
  - Атрибуты методов
  - Атрибуты свойств
  - Атрибуты констант

```php
// Пример класса с атрибутами на разных уровнях
class UserController 
{
    #[Inject]
    private UserService $userService;
    
    #[Route('/users')]
    #[Auth('admin')]
    public function index(): Response 
    {
        // реализация метода
    }
    
    #[Deprecated]
    public const STATUS_ACTIVE = 1;
}
```

#### AttributeSearchFilter
Продвинутая фильтрация атрибутов с множественными вариантами поиска:

```php
use Bermuda\ClassFinder\Filter\AttributeSearchFilter;

// Базовый поиск - только атрибуты на уровне класса
$filter = new AttributeSearchFilter(['Route', 'Controller']);

// Глубокий поиск - включает атрибуты методов, свойств и констант
$filter = new AttributeSearchFilter(['Inject'], deepSearch: true);
// Найдёт UserController, потому что у $userService есть #[Inject]

// Смешанные точные имена и паттерны с глубоким поиском
$filter = new AttributeSearchFilter(['Route', '*Test*', 'Api*'], deepSearch: true);

// Логика И с глубоким поиском - должны быть ВСЕ атрибуты (где угодно в классе)
$filter = new AttributeSearchFilter(['Route', 'Auth'], matchAll: true, deepSearch: true);
// Найдёт UserController, потому что у него есть и #[Route], и #[Auth] на методах

// Сравнение областей поиска:
// Без глубокого поиска - находит только классы с атрибутом Route на объявлении класса
$classOnlyFilter = new AttributeSearchFilter(['Route'], deepSearch: false);

// С глубоким поиском - находит классы с Route на классе ИЛИ на любом методе/свойстве/константе
$deepFilter = new AttributeSearchFilter(['Route'], deepSearch: true);

// Статические помощники с глубоким поиском
$filter = AttributeSearchFilter::hasAttribute('Inject', deepSearch: true);
$filter = AttributeSearchFilter::hasAnyAttribute(['Route', 'Controller'], deepSearch: true);
$filter = AttributeSearchFilter::hasAllAttributes(['Route', 'Middleware'], deepSearch: true);
```

#### AttributePatternFilter
Сопоставление паттернов для имён атрибутов:

```php
use Bermuda\ClassFinder\Filter\AttributePatternFilter;

// Базовое сопоставление паттернов - только атрибуты на уровне класса
$filter = new AttributePatternFilter('*Route*');
$filter = new AttributePatternFilter('Api*');

// Глубокий поиск - включает атрибуты на методах, свойствах, константах
$filter = new AttributePatternFilter('*Route*', deepSearch: true);
// Найдёт UserController, потому что у метода index() есть #[Route('/users')]

$filter = new AttributePatternFilter('*Inject*', deepSearch: true);
// Найдёт UserController, потому что у свойства $userService есть #[Inject]

// Сравнение областей поиска:
// Без глубокого поиска - находит только классы с Http* атрибутами на классе
$classOnlyFilter = new AttributePatternFilter('Http*', deepSearch: false);

// С глубоким поиском - находит классы с Http* на классе ИЛИ членах
$deepFilter = new AttributePatternFilter('Http*', deepSearch: true);

// Оптимизированные статические помощники с глубоким поиском
$filter = AttributePatternFilter::exactAttribute('HttpGet', deepSearch: true);
$filter = AttributePatternFilter::anyAttribute(['Route', 'HttpGet'], deepSearch: true);
$filter = AttributePatternFilter::attributePrefix('Http', deepSearch: true);
```

## Комбинирование фильтров

### ChainableFilter (логика И)
Объединяет несколько фильтров с логикой И - элемент должен пройти ВСЕ фильтры, чтобы быть принятым:

```php
use Bermuda\Filter\ChainableFilter;
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    PatternFilter,
    AttributeSearchFilter
};

// Все условия должны быть истинными
$chainFilter = new ChainableFilter([
    new InstantiableFilter(),                // И: должен быть инстанцируемым
    new PatternFilter('*Controller'),        // И: должен заканчиваться на "Controller"
    new AttributeSearchFilter(['Route'])     // И: должен иметь атрибут Route
]);

$finder = new ClassFinder([$chainFilter]);
$strictControllers = $finder->find('src/Controllers/');
```

### OneOfFilter (логика ИЛИ)
Объединяет несколько фильтров с логикой ИЛИ - элементу нужно пройти только ОДИН фильтр, чтобы быть принятым:

```php
use Bermuda\Filter\OneOfFilter;
use Bermuda\ClassFinder\Filter\{
    PatternFilter,
    AttributeSearchFilter,
    ImplementsFilter
};

// Любое условие может быть истинным
$orFilter = new OneOfFilter([
    new PatternFilter('*Controller'),            // ИЛИ: заканчивается на "Controller"
    new PatternFilter('*Service'),               // ИЛИ: заканчивается на "Service"
    new AttributeSearchFilter(['Component']),    // ИЛИ: имеет атрибут Component
    new ImplementsFilter('App\\Contracts\\HandlerInterface') // ИЛИ: реализует HandlerInterface
]);

$finder = new ClassFinder([$orFilter]);
$flexibleResults = $finder->find('src/');
```

### Сложные комбинации фильтров
Комбинируйте разные типы логики для изощрённой фильтрации:

```php
use Bermuda\Filter\{ChainableFilter, OneOfFilter};
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    PatternFilter,
    AttributeSearchFilter,
    ImplementsFilter
};

// Сложная логика: Должен быть инстанцируемым И (Контроллер ИЛИ Сервис ИЛИ имеет атрибут Route)
$complexFilter = new ChainableFilter([
    new InstantiableFilter(),  // Должен быть инстанцируемым
    new OneOfFilter([          // И любое из этого:
        new PatternFilter('*Controller'),        // - заканчивается на "Controller"
        new PatternFilter('*Service'),           // - заканчивается на "Service"  
        new AttributeSearchFilter(['Route'])     // - имеет атрибут Route
    ])
]);

$finder = new ClassFinder([$complexFilter]);
$results = $finder->find('src/');
```

## Продвинутое использование

### Комбинирование нескольких фильтров

```php
use Bermuda\ClassFinder\ClassFinder;
use Bermuda\ClassFinder\Filter\{
    InstantiableFilter,
    PatternFilter,
    AttributeSearchFilter
};

$finder = new ClassFinder([
    new InstantiableFilter(),                    // Только инстанцируемые классы
    new PatternFilter('*Controller'),            // Имена классов, заканчивающиеся на "Controller"
    new AttributeSearchFilter(['Route'])         // Должен иметь атрибут Route
]);

$controllers = $finder->find('src/Controllers/');
```

### Использование с внедрением зависимостей

```php
use Bermuda\ClassFinder\ClassFinder;
use Psr\Container\ContainerInterface;

// Создание из контейнера
$finder = ClassFinder::createFromContainer($container);
```

### Слушатели событий

Прослушивание найденных классов:

```php
use Bermuda\ClassFinder\{ClassFinder, ClassNotifier};
use Bermuda\ClassFinder\ClassFoundListenerInterface;
use Bermuda\Tokenizer\ClassInfo;

class MyListener implements ClassFoundListenerInterface
{
    public function handle(ClassInfo $info): void
    {
        echo "Найден класс: {$info->fullQualifiedName}\n";
    }
}

// Создание уведомителя со слушателем
$notifier = new ClassNotifier([new MyListener()]);

// Поиск и уведомление
$finder = new ClassFinder();
$classes = $finder->find('src/');
$notifier->notify($classes);
```

### Режимы поиска

Управление тем, какие типы элементов классов искать с помощью битовых флагов:

```php
use Bermuda\ClassFinder\ClassFinder;
use Bermuda\Tokenizer\TokenizerInterface;

$finder = new ClassFinder();

// Поиск только классов
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_CLASSES);

// Поиск только интерфейсов
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_INTERFACES);

// Поиск только трейтов
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_TRAITS);

// Поиск только перечислений
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_ENUMS);

// Комбинирование режимов поиска с помощью битового ИЛИ
$results = $finder->find('src/', [], 
    TokenizerInterface::SEARCH_CLASSES | TokenizerInterface::SEARCH_INTERFACES
);

// Поиск всего (по умолчанию) - классы, интерфейсы, трейты, перечисления
$results = $finder->find('src/', [], TokenizerInterface::SEARCH_ALL);
```

#### Доступные константы режимов поиска:
- `SEARCH_CLASSES = 1` - Найти только классы
- `SEARCH_INTERFACES = 2` - Найти только интерфейсы  
- `SEARCH_TRAITS = 4` - Найти только трейты
- `SEARCH_ENUMS = 8` - Найти только перечисления
- `SEARCH_ALL = 15` - Найти все ООП элементы (по умолчанию)

### Работа с результатами

```php
use Bermuda\ClassFinder\ClassFinder;

$finder = new ClassFinder();
$classes = $finder->find('src/');

// Подсчёт результатов
echo "Найдено " . count($classes) . " классов\n";

// Преобразование в массив
$array = $classes->toArray();

// Динамическое добавление фильтров
$filtered = $classes->withFilter(new InstantiableFilter());

// Удаление фильтров
$unfiltered = $classes->withoutFilter($someFilter);
```

## Советы по производительности

1. **Используйте конкретные фильтры**: Чем конкретнее ваши фильтры, тем быстрее поиск
2. **Порядок имеет значение**: Размещайте самые ограничивающие фильтры первыми
3. **Оптимизация паттернов**: Простые паттерны (prefix*, *suffix, *contains*) автоматически оптимизируются
4. **Кэширование рефлексии**: Повторные поиски на одних и тех же классах выигрывают от кэширования рефлексии

## Конфигурация

### Конфигурация контейнера

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
            // Ваши фильтры по умолчанию
        ],
        ConfigProvider::CONFIG_KEY_LISTENERS => [
            // Ваши слушатели
        ]
    ],
    
    ClassFoundListenerProviderInterface::class => 
        fn() => ClassFoundListenerProvider::createFromContainer($container)
];
```

## Примеры

### Поиск всех контроллеров

```php
$finder = new ClassFinder([
    new InstantiableFilter(),
    new PatternFilter('*Controller'),
    new SubclassFilter('App\\Controller\\BaseController')
]);

$controllers = $finder->find('src/Controllers/');
```

### Поиск сервисов с внедрением зависимостей

```php
$finder = new ClassFinder([
    new InstantiableFilter(),
    new AttributeSearchFilter(['Service'], deepSearch: true),
    new PatternFilter('*Service')
]);

$services = $finder->find('src/Services/');
```

### Поиск классов с глубоким поиском атрибутов

```php
// Найти все классы, которые используют внедрение зависимостей где угодно в классе
$finder = new ClassFinder([
    new AttributeSearchFilter(['Inject', 'Autowired'], deepSearch: true)
]);

$diClasses = $finder->find('src/');

// Найти API-связанные классы, проверив атрибуты маршрутизации в методах
$finder = new ClassFinder([
    new InstantiableFilter(),
    new AttributePatternFilter('*Route*', deepSearch: true) // Проверяет атрибуты класса И методов
]);

$apiClasses = $finder->find('src/');
```

### Поиск тестовых классов

```php
$finder = new ClassFinder([
    new PatternFilter('*Test'),
    new SubclassFilter('PHPUnit\\Framework\\TestCase')
]);

$tests = $finder->find('tests/');
```

### Поиск API эндпоинтов

```php
$finder = new ClassFinder([
    new InstantiableFilter(),
    new AttributeSearchFilter(['Route', 'ApiResource'], deepSearch: true)
]);

$endpoints = $finder->find('src/Api/');
```

### Сложная логика фильтров

```php
use Bermuda\Filter\{ChainableFilter, OneOfFilter};

// Поиск классов, которые являются либо Контроллерами, либо Сервисами, но должны быть инстанцируемыми
$finder = new ClassFinder([
    new InstantiableFilter(),  // Должны быть инстанцируемыми
    new OneOfFilter([          // И (Контроллер ИЛИ Сервис)
        new PatternFilter('*Controller'),
        new PatternFilter('*Service')
    ])
]);

$handleableClasses = $finder->find('src/');
```

### Гибкое обнаружение компонентов

```php
use Bermuda\Filter\OneOfFilter;

// Поиск любых компонентоподобных классов с использованием множественных критериев
$componentFilter = new OneOfFilter([
    new AttributeSearchFilter(['Component', 'Service', 'Repository']),
    new ImplementsFilter(['App\\Contracts\\ComponentInterface']),
    new PatternFilter('App\\Components\\*')
]);

$finder = new ClassFinder([$componentFilter]);
$components = $finder->find('src/');
```

## Лицензия

Этот проект лицензирован под лицензией MIT - смотрите файл [LICENSE](LICENSE) для подробностей.
