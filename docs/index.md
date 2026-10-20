## Prerequisite

CleverAge/ProcessBundle must be [installed](https://github.com/cleverage/process-bundle/blob/main/docs/01-quick_start.md#installation).

## Installation

Make sure Composer is installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Open a command console, enter your project directory and install it using composer:

```bash
composer require cleverage/cache-process-bundle
```

Remember to add the following line to config/bundles.php (not required if Symfony Flex is used)

```php
CleverAge\CacheProcessBundle\CleverAgeCacheProcessBundle::class => ['all' => true],
```

## Configuration

The bundle has no semantic configuration. To use the cache tasks, declare at least one cache
[adapter](reference/adapter.md): a service implementing `CleverAge\CacheProcessBundle\Adapter\AdapterInterface`
(usually `CleverAge\CacheProcessBundle\Adapter\Adapter`, wrapping any Symfony cache pool), tagged
`cleverage.cache.adapter` with its code (or with the `#[AsCacheAdapter]` attribute). This code is then used in the
`adapter` option of the tasks.

The tasks are registered as services (non-shared) and are used in your processes by their code, `cache_get` and
`cache_set` (e.g. `task: cache_get`, requires `cleverage/process-bundle` 5.4 or later, see its
[task definition](https://github.com/cleverage/process-bundle/blob/main/docs/reference/02-task_definition.md)).
Referencing them by their class name (`service: '@CleverAge\CacheProcessBundle\Task\GetTask'`) is deprecated since
`cleverage/process-bundle` 5.4.

Their service id is their class name. Fetching them directly from the service container is deprecated since 2.3 (they
will be private in 3.0); their former ids, `cleverage_cache_process.task.get` and `cleverage_cache_process.task.set`,
are deprecated aliases since 2.3, removed in 3.0.

```yaml
# config/services.yaml
services:
    app.cleverage_cache_process.adapter.app:
        class: CleverAge\CacheProcessBundle\Adapter\Adapter
        arguments: ['@cache.app', 'app']
        tags:
            - { name: cleverage.cache.adapter, code: app }
```

No option can be configured under `clever_age_cache_process`: a key there is ignored. Configuring one is deprecated since 2.3 (an `E_USER_DEPRECATED` error is triggered when the container is compiled), the keys will be rejected in 3.0.

## Custom cache tasks

`CleverAge\CacheProcessBundle\Task\AbstractCacheTask` can be extended to implement other cache operations. It extends
[AbstractConfigurableTask](https://github.com/cleverage/process-bundle/blob/main/docs/03-custom_tasks.md), requires
the `cleverage_cache_process.registry.adapter` service (`AdapterRegistry`) as constructor argument, defines the
`adapter` and `key` string options (required once merged with the input, see `getRequiredOptions()`), and provides
`getMergedOptions()` (options merged with the array input, resolved again so that the input values are validated, and
key validated) and `$this->registry->getAdapter($code)`.

```php
<?php

declare(strict_types=1);

namespace App\Task;

use CleverAge\CacheProcessBundle\Task\AbstractCacheTask;
use CleverAge\ProcessBundle\Attribute\AsTask;
use CleverAge\ProcessBundle\Model\ProcessState;

#[AsTask(code: 'app_cache_delete')]
class DeleteTask extends AbstractCacheTask
{
    public function execute(ProcessState $state): void
    {
        /** @var array{adapter: string, key: string} $options */
        $options = $this->getMergedOptions($state);

        $this->registry->getAdapter($options['adapter'])->deleteItem($options['key']);
    }
}
```

Its code is given by the `#[AsTask]` attribute (`cleverage/process-bundle` 5.4 or later), prefixed to avoid conflicts
with the codes of the bundles. With autoconfiguration, the task is also non-shared and logs in the
`cleverage_process_task` channel. The registry is autowired by its class (since 2.3; before, pass
`'@cleverage_cache_process.registry.adapter'` as argument), so the default `App\:` resource of a Symfony application is
enough, or explicitly:

```yaml
# config/services.yaml
services:
    App\Task\DeleteTask:
        autowire: true
        autoconfigure: true
```

```yaml
delete_from_cache:
    task: app_cache_delete
    options:
        adapter: app
        key: my_key
```

## Reference

- [Adapter](reference/adapter.md)
- Tasks
  - [GetTask](reference/tasks/get_task.md)
  - [SetTask](reference/tasks/set_task.md)

## Cookbooks

- [Share data between process branches](cookbooks/share_data_between_branches.md)
- [Warm up a persistent cache from a file](cookbooks/cache_warmup.md)
