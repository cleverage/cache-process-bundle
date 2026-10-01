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

`mixed`: the value of the cache item. If the key is missing from the cache, depends on `on_miss`: `null` (default), no
output (the input is sent to the error outputs), or an exception.

Options
-------

| Code      | Type     | Required | Default       | Description                                                                           |
|-----------|----------|:--------:|---------------|---------------------------------------------------------------------------------------|
| `adapter` | `string` |  **X**   |               | Code of the [adapter](../adapter.md) to read from (see `AdapterInterface::getCode()`) |
| `key`     | `string` |  **X**   |               | Key of the cache item to read, must be a valid PSR-6 key                              |
| `on_miss` | `string` |          | `output_null` | Behaviour when the key is missing from the cache (see below)                          |

Every option can be given by the configuration or by the input: `adapter` and `key` are required once merged with the
input, an option given by neither throws a `MissingOptionsException` on execution.

`on_miss` values:

* `output_null`: output `null`, as for an item stored with a `null` value.
* `skip`: skip the item and send the input to the error outputs (`error_outputs`), e.g. to compute the missing value
  and store it.
* `fail`: throw an `\UnexpectedValueException` (`Cache item <key> is missing from adapter <adapter>`).

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
    on_miss: skip
  outputs: [debug]
```

* Compute and store the missing values ("cache-aside")

```yaml
# Task configuration level
get:
  service: '@CleverAge\CacheProcessBundle\Task\GetTask'
  options:
    adapter: 'catalog'
    on_miss: skip # The input ({ key: ... }) is sent to the error outputs
  outputs: [debug]
  error_outputs: [compute]
compute:
  service: '@CleverAge\ProcessBundle\Task\TransformerTask'
  options:
    transformers:
      mapping:
        mapping:
          key:
            code: '[key]'
          value:
            code: '[key]'
            transformers:
              slugify: ~ # Any computation
  outputs: [set]
set:
  service: '@CleverAge\CacheProcessBundle\Task\SetTask'
  options:
    adapter: 'catalog'
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
    adapter: 'catalog' # The key is given by the input
```

Notes
-----

* The key is validated by the task (`CacheItem::validateKey()`): an empty key, or a key containing one of the PSR-6
  reserved characters `{}()/\@:`, throws a `Psr\Cache\InvalidArgumentException`, whatever the adapter and the
  `zend.assertions` setting (see [Adapter](../adapter.md#notes)).
* A cache miss is detected with `CacheItemInterface::isHit()`: an item stored with a `null` value is a hit, and is
  always output.
* With `on_miss: skip`, the next tasks of `outputs` are not executed for this input, but the tasks of `error_outputs`
  are (whatever the `error_strategy`).
* The input is replaced by the cached value: the rest of the input is not transmitted to the next tasks.
