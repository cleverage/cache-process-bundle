GetTask
=======

Reads an item from a cache [adapter](../adapter.md) and outputs its value. Useful to reuse a value computed by a
previous branch of the process, or stored by another process when the adapter is persistent.

Task reference
--------------

* **Service**: `CleverAge\CacheProcessBundle\Task\GetTask`

Accepted inputs
---------------

`array` or empty (`null`): if the input is not empty, its keys `adapter` and `key` override the options of the same
name, and are validated like the options (e.g. a non-string `key` throws an `InvalidOptionsException`). Other input
keys are ignored.

Any other non-empty input (e.g. a `string`) throws an `\UnexpectedValueException`.

Possible outputs
----------------

`mixed`: the value of the cache item, or `null` if the key is missing from the cache.

Options
-------

| Code      | Type     | Required | Default | Description                                                                               |
|-----------|----------|:--------:|---------|-------------------------------------------------------------------------------------------|
| `adapter` | `string` |  **X**   |         | Code of the [adapter](../adapter.md) to read from (see `AdapterInterface::getCode()`)     |
| `key`     | `string` |  **X**   |         | Key of the cache item to read, must be a valid PSR-6 key (can be overridden by the input) |

Examples
--------

* Read a fixed key

```yaml
# Task configuration level
get:
  service: '@CleverAge\CacheProcessBundle\Task\GetTask'
  options:
    adapter: 'memory'
    key: 'key2'
  outputs: [debug]
```

* Read a key given by the process context (`-c sku:"'ABC-001'"`) and stop the branch if it is missing

```yaml
# Task configuration level
get:
  service: '@CleverAge\CacheProcessBundle\Task\GetTask'
  options:
    adapter: 'catalog'
    key: '{{ sku }}'
  outputs: [skip_missing]
skip_missing:
  service: '@CleverAge\ProcessBundle\Task\SkipEmptyTask'
  outputs: [debug]
```

* Read a key computed from the input

```yaml
# Task configuration level
build_key:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      mapping:
        mapping:
          key:
            code: '[sku]'
  outputs: [get]
get:
  service: '@CleverAge\CacheProcessBundle\Task\GetTask'
  options:
    adapter: 'catalog'
    key: '' # Overridden by the input
```

Notes
-----

* `adapter` and `key` are required at configuration level, even when they are always given by the input: set them to
  a placeholder value (e.g. `key: ''`).
* A missing key and an item stored with a `null` value both output `null`. Chain a
  [SkipEmptyTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/skip_empty_task.md) to
  stop the branch when nothing is found.
* The input is replaced by the cached value: the rest of the input is not transmitted to the next tasks.
