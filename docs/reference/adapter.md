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
* **Service tag**: `cleverage.cache.adapter`, with a `code` attribute (`- { name: cleverage.cache.adapter, code: memory }`):
  every tagged service is registered in the `cleverage_cache_process.registry.adapter` registry
  (`CleverAge\CacheProcessBundle\Registry\AdapterRegistry`, autowirable by its class name)
* **Attribute**: `CleverAge\CacheProcessBundle\Attribute\AsCacheAdapter` (`#[AsCacheAdapter(code: 'memory')]`, with
  autoconfiguration), equivalent to the tag
* **Symfony cache pool**: a service implementing `Symfony\Component\Cache\Adapter\AdapterInterface` (every Symfony
  adapter and pool) can be tagged directly, without any adapter class: it is wrapped in an `Adapter` with the `code` of
  the tag

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
            - { name: cleverage.cache.adapter, code: memory }
```

* In-memory adapter, without any PHP class: a Symfony adapter tagged directly

```yaml
# config/services.yaml
services:
    app.cache.memory:
        class: Symfony\Component\Cache\Adapter\ArrayAdapter
        tags:
            - { name: cleverage.cache.adapter, code: memory }
```

* Persistent adapter, a cache pool defined as a service (as the ones of `framework.cache.pools`: namespace, lifetime
  and clearer handled by the FrameworkBundle) and tagged directly

```yaml
# config/services.yaml
services:
    app.cache.catalog:
        parent: cache.adapter.filesystem
        tags:
            - { name: cache.pool, default_lifetime: 86400 }
            - { name: cleverage.cache.adapter, code: catalog }
```

* Persistent adapter, wrapping a pool of `framework.cache.pools` (its service is created by the FrameworkBundle, it
  cannot be tagged by the application)

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
            - { name: cleverage.cache.adapter, code: catalog }
```

Notes
-----

* The adapters are registered by the `code` attribute of their tag, in a service locator built when the container is
  compiled: an adapter is only instantiated when a task uses it (an error in an adapter that a process does not use
  does not make it fail). The `code` must be the one returned by `getCode()` (an `UnexpectedValueException` is thrown
  otherwise when it is fetched). Two adapters with the same code, or an empty code, fail the container compilation.
  `AdapterRegistry::hasAdapter()` and `getAdapters()` (which instantiates every adapter) are available, e.g. in a
  custom cache task.
* A tagged Symfony cache pool (a service implementing `Symfony\Component\Cache\Adapter\AdapterInterface`, but not the
  `AdapterInterface` of the bundle) is wrapped in an `Adapter` (`.cleverage_cache_process.adapter.<code>` service),
  instantiated with the pool when a task uses it; several codes on one pool share its items. The interface is checked
  from the class of the service (or of its `parent`): a tagged service implementing neither interface fails the container
  compilation. A pool must be tagged with a `code`.
* **Deprecated**: a service tagged without `code` is still registered, with `AdapterRegistry::addAdapter()`,
  instantiated with the registry (deprecated since 2.3, the `code` will be required in 3.0). Two adapters with the same
  code then throw an `UnexpectedValueException` giving the ids of both services
  (`Adapter <code> is already defined by service "<id>", cannot register service "<id>"`) when the registry is
  instantiated, i.e. the first time a cache task is used.
* Using a code that is not registered throws a `CleverAge\CacheProcessBundle\Exception\MissingAdapterException`
  (`Adapter <code> is missing`) when the task is executed.
* The lifetime of the items is the default lifetime of the decorated pool (`default_lifetime` of a FrameworkBundle
  pool, `$defaultLifetime` constructor argument of Symfony adapters), unless the `expires_after` option of
  [SetTask](tasks/set_task.md) is set.
* Cache keys must follow the PSR-6 rules: no empty key, and none of the reserved characters `{}()/\@:`. The keys are
  validated by the decorated pool, and Symfony adapters only validate them with `assert()`: an invalid key throws a
  `Psr\Cache\InvalidArgumentException` when assertions are enabled (`zend.assertions=1`, usual in development), but is
  silently accepted when they are not (`zend.assertions=-1`, production `php.ini`). The cache tasks validate the key
  themselves, so an invalid key always throws when using them.
* Only the PSR-6 methods are forwarded by the base class: tag-aware features of the decorated pool are not exposed.
