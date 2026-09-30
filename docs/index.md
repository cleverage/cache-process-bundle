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
`cleverage.cache.adapter`. Its code is then used in the `adapter` option of the tasks.

```yaml
# config/services.yaml
services:
    app.cleverage_cache_process.adapter.app:
        class: CleverAge\CacheProcessBundle\Adapter\Adapter
        arguments: ['@cache.app', 'app']
        tags:
            - { name: cleverage.cache.adapter }
```

## Custom cache tasks

`CleverAge\CacheProcessBundle\Task\AbstractCacheTask` can be extended to implement other cache operations. It extends
[AbstractConfigurableTask](https://github.com/cleverage/process-bundle/blob/main/docs/03-custom_tasks.md), requires
the `cleverage_cache_process.registry.adapter` service (`AdapterRegistry`) as constructor argument, defines the
required `adapter` and `key` string options, and provides `getMergedOptions()` (options merged with the array input,
resolved again so that the input values are validated) and `$this->registry->getAdapter($code)`.

```php
<?php

declare(strict_types=1);

namespace App\Task;

use CleverAge\CacheProcessBundle\Task\AbstractCacheTask;
use CleverAge\ProcessBundle\Model\ProcessState;

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

```yaml
# config/services.yaml
services:
    App\Task\DeleteTask:
        public: true
        shared: false
        arguments: ['@cleverage_cache_process.registry.adapter']
```

## Reference

- [Adapter](reference/adapter.md)
- Tasks
  - [GetTask](reference/tasks/get_task.md)
  - [SetTask](reference/tasks/set_task.md)

## Cookbooks

- [Share data between process branches](cookbooks/share_data_between_branches.md)
- [Warm up a persistent cache from a file](cookbooks/cache_warmup.md)
