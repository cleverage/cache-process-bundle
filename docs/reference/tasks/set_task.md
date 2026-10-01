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

| Code            | Type          | Required | Default | Description                                                                                  |
|-----------------|---------------|:--------:|---------|----------------------------------------------------------------------------------------------|
| `adapter`       | `string`      |  **X**   |         | Code of the [adapter](../adapter.md) to write to (see `AdapterInterface::getCode()`)         |
| `key`           | `string`      |  **X**   |         | Key of the cache item to store, must be a valid PSR-6 key                                    |
| `value`         | `mixed`       |  **X**   |         | Value to store (can be `null`), must be serializable by the adapter                          |
| `expires_after` | `int`, `null` |          | `null`  | Lifetime of the item in seconds (strictly positive), `null` for the default adapter lifetime |

Every option can be given by the configuration or by the input: `adapter`, `key` and `value` are required once merged
with the input, an option given by neither throws a `MissingOptionsException` on execution.

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
    adapter: 'memory' # The key and the value are given by the input
```

* Store an item for one hour

```yaml
# Task configuration level
set:
  service: '@CleverAge\CacheProcessBundle\Task\SetTask'
  options:
    adapter: 'catalog'
    expires_after: 3600
```

Notes
-----

* The key is validated by the task (`CacheItem::validateKey()`): an empty key, or a key containing one of the PSR-6
  reserved characters `{}()/\@:`, throws a `Psr\Cache\InvalidArgumentException`, whatever the adapter and the
  `zend.assertions` setting (see [Adapter](../adapter.md#notes)).
* Without `expires_after`, the lifetime of the item is the default lifetime of the adapter (see
  [Adapter](../adapter.md#notes)). Give `expires_after` in the input to set a lifetime per item.
* The item is saved immediately (`save()`, not `saveDeferred()`), an existing item with the same key is overwritten.
