GetTask
===============

Get data from cache adapter.

Adapter reference
--------------

* [Documentation](../adapter.md)

Task reference
--------------

* **Task Service**: `CleverAge\CacheProcessBundle\Task\GetTask`

Accepted inputs
---------------

`array`: inputs are merged with task defined options.

Possible outputs
----------------

`mixed` or `null`: the content of the cache key.

Options
-------

| Code      | Type     | Required | Default  | Description                                                                |
|-----------|----------|:--------:|----------|----------------------------------------------------------------------------|
| `adapter` | `string` |  **X**   |          | `CleverAge\CacheProcessBundle\Adapter\AdapterInterface` service identifier |
| `key`     | `string` |  **X**   |          | index where retrieving data                                                |

Examples
--------

```yaml
# Task configuration level
code:
  service: '@CleverAge\CacheProcessBundle\Task\GetTask'
  options:
    adapter: 'memory'
    key: 'key2'
```
