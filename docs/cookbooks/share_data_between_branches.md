Share data between process branches
===================================

This recipe uses an in-memory cache to store data in a first branch of a process, then read it in another branch of
the same execution. Nothing is persisted: the values only live during the current PHP process.

First, declare an in-memory cache [adapter](../reference/adapter.md) with the `memory` code: a Symfony `ArrayAdapter`
tagged directly, no PHP class is needed:

```yaml
# config/services.yaml
services:
    app.cache.memory:
        class: Symfony\Component\Cache\Adapter\ArrayAdapter
        tags:
            - { name: cleverage.cache.adapter, code: memory }
```

Then define the process:

```yaml
clever_age_process:
    configurations:
        app.cache_share_data:
            description: 'Store items in a first branch, then read some of them in other branches'
            tasks:
                start:
                    task: dummy
                    outputs: [data, get, get_missing] # Branches are executed in this order

                data:
                    task: constant_iterable_output
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
                    task: transformer
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
                    task: cache_set
                    options:
                        adapter: 'memory' # The key and the value are given by the input

                get:
                    task: cache_get
                    options:
                        adapter: 'memory'
                        key: 'key2'
                    outputs: [debug]

                get_missing:
                    task: cache_get
                    options:
                        adapter: 'memory'
                        key: 'missing'
                    outputs: [debug]

                debug:
                    task: debug
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
