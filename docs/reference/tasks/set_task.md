SetTask
=======

Stores a value in a cache [adapter](../adapter.md). The key and the value are usually given by the input, so the task
can store every item of an iterable flow (e.g. each line of a file).

Task reference
--------------

* **Service**: `CleverAge\CacheProcessBundle\Task\SetTask`

Accepted inputs
---------------

`array` or empty (`null`): if the input is not empty, its keys `adapter`, `key` and `value` override the options of the
same name, and are validated like the options (e.g. a non-string `key` throws an `InvalidOptionsException`). Other
input keys are ignored.

Any other non-empty input (e.g. a `string`) throws an `\UnexpectedValueException`.

Possible outputs
----------------

`null`: the task does not set any output, the next tasks (if any) receive `null`.

Options
-------

| Code      | Type     | Required | Default | Description                                                                                |
|-----------|----------|:--------:|---------|--------------------------------------------------------------------------------------------|
| `adapter` | `string` |  **X**   |         | Code of the [adapter](../adapter.md) to write to (see `AdapterInterface::getCode()`)       |
| `key`     | `string` |  **X**   |         | Key of the cache item to store, must be a valid PSR-6 key (can be overridden by the input) |
| `value`   | `mixed`  |  **X**   |         | Value to store, must be serializable by the adapter (can be overridden by the input)       |

Examples
--------

* Store a constant value

```yaml
# Task configuration level
set:
  service: '@CleverAge\CacheProcessBundle\Task\SetTask'
  options:
    adapter: 'memory'
    key: 'key1'
    value:
      - column1: value1-1
        column2: value2-1
        column3: value3-1
```

* Store each item of a flow, using its `key` column as cache key and the whole item as value

```yaml
# Task configuration level
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
```

Notes
-----

* `adapter`, `key` and `value` are required at configuration level, even when they are always given by the input:
  set them to a placeholder value (e.g. `key: ''`, `value: ~`). If the input does not override the placeholder key,
  the empty key throws a `Psr\Cache\InvalidArgumentException`.
* No expiration is set on the item: its lifetime is the default lifetime of the adapter (see
  [Adapter](../adapter.md#notes)).
* The item is saved immediately (`save()`, not `saveDeferred()`), an existing item with the same key is overwritten.
