Upgrade Guide
=============

## v2.3

Unless stated otherwise, the deprecations below trigger a notice (`Since cleverage/cache-process-bundle 2.3: ...`)
and will be removed in 3.0.

### Process bundle 5.4

`cleverage/process-bundle` `^5.4` is required: follow its
[upgrade guide](https://github.com/cleverage/process-bundle/blob/main/UPGRADE.md#v54).

### Configuration key

The configuration key of the bundle is `cleverage_cache_process`: `clever_age_cache_process` is no longer recognized.
The bundle has no configuration, so remove the key: configuring one under `cleverage_cache_process` is deprecated (the
keys were ignored), it will be rejected in 3.0.

### Task codes and service ids

Reference the tasks by their code instead of their service:

```yaml
tasks:
    get:
        # Before (deprecated)
        service: '@CleverAge\CacheProcessBundle\Task\GetTask'
        # After
        task: cache_get
```

| Former service id (deprecated alias) | Class     | Code        |
|--------------------------------------|-----------|-------------|
| `cleverage_cache_process.task.get`   | `GetTask` | `cache_get` |
| `cleverage_cache_process.task.set`   | `SetTask` | `cache_set` |

The service id of the tasks is their class name. Fetching them from the service container (`$container->get()`) is
deprecated: they will be private in 3.0.

### Adapter codes

Registering an adapter without code is deprecated: declare its code (the one returned by `getCode()`) with the
`#[AsCacheAdapter(code: 'my_adapter')]` attribute or the `code` attribute of the `cleverage.cache.adapter` tag, so that
it is only instantiated when used. `AdapterRegistry::addAdapter()` is deprecated.

```yaml
# config/services.yaml
App\Cache\MyAdapter:
    tags: [{ name: cleverage.cache.adapter, code: my_adapter }]
```

### Final classes

`GetTask`, `SetTask`, `AdapterRegistry` and `CleverAgeCacheProcessBundle` are marked `@final`, they will be `final` in
3.0 (extending them triggers a deprecation with the Symfony `DebugClassLoader`). `AbstractCacheTask` and `Adapter` stay
open. `CleverAgeCacheProcessExtension` and `RegisterAdaptersPass` are `@internal`.

### Exceptions

The bundle throws exceptions implementing `ProcessExceptionInterface` of the process bundle, extending the SPL
exceptions thrown before: existing `catch` blocks keep working.
