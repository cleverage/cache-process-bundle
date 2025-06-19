SetTask
===============

Set data on cache adapter.

Adapter reference
--------------

* [Documentation](../adapter.md)

Task reference
--------------

* **Task Service**: `CleverAge\CacheProcessBundle\Task\SetTask`

Accepted inputs
---------------

`array`: inputs are merged with task defined options.

Possible outputs
----------------

none

Options
-------

| Code      | Type     | Required | Default  | Description                                                                |
|-----------|----------|:--------:|----------|----------------------------------------------------------------------------|
| `adapter` | `string` |  **X**   |          | `CleverAge\CacheProcessBundle\Adapter\AdapterInterface` service identifier |
| `key`     | `string` |  **X**   |          | index where storing data                                                   |
| `value`   | `mixed`  |  **X**   |          | data to store                                                              |

Examples
--------

```yaml
# Task configuration level
code:
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

```yaml
# Task configuration level
code:
  data:
    service: '@CleverAge\ProcessBundle\Task\ConstantIterableOutputTask'
    outputs: [ format ]
    options:
      output:
        - key: 'key1'
          column1: value1-1
          column2: value2-1
          column3: value3-1
        - key: 'key2'
          column1: value1-2
          column2: value2-2
          column3: value3-2
        - key: 'key3'
          column1: ''
          column2: null
          column3: value3-3
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
    outputs: [ set ]

  set:
    service: '@CleverAge\CacheProcessBundle\Task\SetTask'
    options:
      adapter: 'memory'
      key: '' # overrided by input'
      value: '' # overrided by input
```
