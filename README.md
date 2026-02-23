# Filter Builder for Eloquent

Eloquent adapter for the [filter-builder-core](https://github.com/kobavzw/filter-builder-core) library.

## Installation

```bash
composer require koba/filter-builder-eloquent
```

## Usage

### Creating an Eloquent Strategy

```php
use App\Models\User;
use Koba\FilterBuilder\Core\Configuration\Configuration;
use Koba\FilterBuilder\Eloquent\EloquentStrategy;

$strategy = new EloquentStrategy(User::class);
$config = new Configuration($strategy);
```

### Filtering Model Attributes

Use `makeRule()` to create filters for model attributes:

```php
use Koba\FilterBuilder\Core\Enums\ConstraintType;
use Koba\FilterBuilder\Core\Enums\Operation;

$config->addRuleEntry(
    name: 'email',
    type: ConstraintType::STRING,
    supportedOperations: [Operation::EQUALS, Operation::STARTS_WITH],
    boundFilterFn: fn($strategy) => $strategy->makeRule(
        fn($query, $apply) => $apply('email', $query)
    )
);
```

### Filtering Relationships

Use `makeRelation()` to filter based on related models:

```php
use App\Models\Post;

// Create configuration for the related model
$postStrategy = new EloquentStrategy(Post::class);
$postConfig = new Configuration($postStrategy);

$postConfig->addRuleEntry(
    name: 'title',
    type: ConstraintType::STRING,
    supportedOperations: [Operation::STARTS_WITH],
    boundFilterFn: fn($strategy) => $strategy->makeRule(
        fn($query, $apply) => $apply('title', $query)
    )
);

// Add relationship filter
$config->addRelationEntry(
    name: 'posts',
    boundFilterFn: fn($strategy) => $strategy->makeRelation(
        Post::class,
        fn($query, $apply) => $query->whereHas('posts', $apply)
    ),
    configuration: $postConfig
);
```

### Applying Filters

```php
$filter = $config->getFilter($filterInput);

$query = User::query();
$filter->apply($query);

$users = $query->get();
```

## Examples

### Using `whereDoesntHave`

```php
$config->addRelationEntry(
    name: 'posts',
    boundFilterFn: fn($strategy) => $strategy->makeRelation(
        Post::class,
        fn($query, $apply) => $query->whereDoesntHave('posts', $apply)
    ),
    configuration: $postConfig
);
```

### Filtering on Relationship Attributes

```php
$config->addRuleEntry(
    name: 'author_name',
    type: ConstraintType::STRING,
    supportedOperations: [Operation::EQUALS],
    boundFilterFn: fn($strategy) => $strategy->makeRule(
        fn($query, $apply) => $query->whereHas('author', fn($q) => $apply('name', $q))
    )
);
```

## Documentation

For complete documentation on configuration, filter syntax, operations, and validation, see the [core library](https://github.com/kobavzw/filter-builder-core).

## License

MIT
