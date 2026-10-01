Adapter
=======

A cache adapter makes a [Symfony Cache](https://symfony.com/doc/current/components/cache.html) pool available to the
cache tasks ([GetTask](tasks/get_task.md), [SetTask](tasks/set_task.md)) under a short **code**, referenced by the
`adapter` option of these tasks.

Adapter reference
-----------------

* **Interface**: `CleverAge\CacheProcessBundle\Adapter\AdapterInterface`, extends
  `Symfony\Component\Cache\Adapter\AdapterInterface` and adds `getCode(): string` (the code used in the `adapter`
  option of the tasks)
* **Base class**: `CleverAge\CacheProcessBundle\Adapter\Adapter`, decorates any
  `Symfony\Component\Cache\Adapter\AdapterInterface` (every PSR-6 method is forwarded to it)
* **Service tag**: `cleverage.cache.adapter`, every tagged service is registered in the
  `cleverage_cache_process.registry.adapter` registry (`CleverAge\CacheProcessBundle\Registry\AdapterRegistry`)

Constructor arguments
---------------------

Arguments of the `CleverAge\CacheProcessBundle\Adapter\Adapter` base class:

| Code      | Type                                               | Required | Default | Description                                                         |
|-----------|----------------------------------------------------|:--------:|---------|---------------------------------------------------------------------|
| `adapter` | `Symfony\Component\Cache\Adapter\AdapterInterface` |  **X**   |         | The decorated cache pool, where items are actually read and stored  |
| `code`    | `string`                                           |  **X**   |         | Unique adapter code, referenced by the `adapter` option of the tasks |

Examples
--------

* In-memory adapter, as a PHP class (values only live during the current PHP process)

```php
<?php

declare(strict_types=1);

namespace App\Adapter;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MemoryAdapter extends Adapter
{
    public function __construct()
    {
        parent::__construct(new ArrayAdapter(), 'memory');
    }
}
```

```yaml
# config/services.yaml
services:
    app.cleverage_cache_process.adapter.memory:
        class: App\Adapter\MemoryAdapter
        tags:
            - { name: cleverage.cache.adapter }
```

* Persistent adapter, wrapping a cache pool of the FrameworkBundle, without any PHP class

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            app.cache.catalog:
                adapter: cache.adapter.filesystem
                default_lifetime: 86400
```

```yaml
# config/services.yaml
services:
    app.cleverage_cache_process.adapter.catalog:
        class: CleverAge\CacheProcessBundle\Adapter\Adapter
        arguments:
            - '@app.cache.catalog'
            - 'catalog'
        tags:
            - { name: cleverage.cache.adapter }
```

Notes
-----

* Codes must be unique: registering two adapters with the same code throws an `\UnexpectedValueException` giving the
  ids of both services (`Adapter <code> is already defined by service "<id>", cannot register service "<id>"`) when
  the registry is instantiated, i.e. the first time a cache task is used.
* Using a code that is not registered throws a `CleverAge\CacheProcessBundle\Exception\MissingAdapterException`
  (`Adapter <code> is missing`) when the task is executed.
* The cache tasks do not handle any expiration: the lifetime of the items is the default lifetime of the decorated
  pool (`default_lifetime` of a FrameworkBundle pool, `$defaultLifetime` constructor argument of Symfony adapters).
* Cache keys must follow the PSR-6 rules: no empty key, and none of the reserved characters `{}()/\@:`. The keys are
  validated by the decorated pool, and Symfony adapters only validate them with `assert()`: an invalid key throws a
  `Psr\Cache\InvalidArgumentException` when assertions are enabled (`zend.assertions=1`, usual in development), but is
  silently accepted when they are not (`zend.assertions=-1`, production `php.ini`).
* Only the PSR-6 methods are forwarded by the base class: tag-aware features of the decorated pool are not exposed.
