CleverAge/CacheProcessBundle
=======================

See process bundle documentation

## Help needed

This bundle is a mess, it doesn't require the proper dependencies, it uses missing methods (transformValue), there is a
general lack of comments and annotations and it needs to be properly migrated to PHP7.1+.

Also we need to check if tests are ok.

## Documentation

Contains tasks and transformers to handle cache.

Activation
----------

Activated if cache pool `cleverage_process` is defined.

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\ArrayFilterTransformer`
* **Transformer code**: `array_filter`

Accepted inputs
---------------

`array` or `\Iterable`

Possible outputs
----------------

`array` containing only filtered data

Options
-------

| Code | Type | Required | Default | Description |
| ---- | ---- | :------: | ------- | ----------- |
| `condition` | `array` | | `[]` | See [ConditionTrait](TODO) |
____
