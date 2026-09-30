Share data between process branches
===================================

This recipe uses an in-memory cache to store data in a first branch of a process, then read it in another branch of
the same execution. Nothing is persisted: the values only live during the current PHP process.

First, declare an in-memory cache [adapter](../reference/adapter.md) with the `memory` code:

```php
<?php
// src/Adapter/MemoryAdapter.php

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

Then define the process:

```yaml
clever_age_process:
    configurations:
        app.cache_share_data:
            description: 'Store items in a first branch, then read some of them in other branches'
            tasks:
                start:
                    service: '@CleverAge\ProcessBundle\Task\DummyTask'
                    outputs: [data, get, get_missing] # Branches are executed in this order

                data:
                    service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
                    options:
                        output:
                            - key: 'key1'
                              column1: value1-1
                              column2: value2-1
                            - key: 'key2'
                              column1: value1-2
                              column2: value2-2
                    outputs: [format]

                format:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    key:
                                        code: '[key]'
                                    value:
                                        code: '.'
                    outputs: [set]

                set:
                    service: '@CleverAge\CacheProcessBundle\Task\SetTask'
                    options:
                        adapter: 'memory'
                        key: '' # Overridden by the input
                        value: ~ # Overridden by the input

                get:
                    service: '@CleverAge\CacheProcessBundle\Task\GetTask'
                    options:
                        adapter: 'memory'
                        key: 'key2'
                    outputs: [debug]

                get_missing:
                    service: '@CleverAge\CacheProcessBundle\Task\GetTask'
                    options:
                        adapter: 'memory'
                        key: 'missing'
                    outputs: [debug]

                debug:
                    service: '@CleverAge\ProcessBundle\Task\Debug\DebugTask'
```

How it works:
- [DummyTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/dummy_task.md) starts three
  branches. They are executed one after the other, in the order of `outputs`: the `data` branch is fully processed
  before `get` and `get_missing` are executed.
- [ConstantIterableOutputTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/constant_iterable_output_task.md)
  outputs each item one by one, the
  [TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md)
  maps it to a `key` / `value` array, and [SetTask](../reference/tasks/set_task.md) stores it in the `memory` adapter.
- [GetTask](../reference/tasks/get_task.md) `get` outputs the item stored under `key2` (the whole original item,
  `key` column included), and `get_missing` outputs `null` since `missing` was never stored.
- [DebugTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/debug_task.md) dumps both
  values.

Note that the adapter service is shared: two processes executed in the same PHP process (e.g. with the
[ProcessExecutorTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/process_executor_task.md))
also share the in-memory values. To share data between separate executions, use a persistent pool instead (see
[Warm up a persistent cache from a file](cache_warmup.md)).
