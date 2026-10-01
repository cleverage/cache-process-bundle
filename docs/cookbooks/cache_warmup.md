Warm up a persistent cache from a file
======================================

This recipe loads a reference CSV file into a persistent cache, so that other processes can read its lines by key
without parsing the file again.

First, declare a FrameworkBundle cache pool and wrap it in a cache [adapter](../reference/adapter.md) with the
`catalog` code:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            app.cache.catalog:
                adapter: cache.adapter.filesystem
                default_lifetime: 86400 # One day

# config/services.yaml
services:
    app.cleverage_cache_process.adapter.catalog:
        class: CleverAge\CacheProcessBundle\Adapter\Adapter
        arguments: ['@app.cache.catalog', 'catalog']
        tags:
            - { name: cleverage.cache.adapter }
```

Then define a process storing each line of the file, and another one reading a line by key:

```yaml
clever_age_process:
    configurations:
        app.catalog_cache_warmup:
            description: 'Store each line of the catalog CSV file in the catalog cache, indexed by sku'
            tasks:
                read_source:
                    service: '@CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask'
                    options:
                        file_path: '%kernel.project_dir%/var/data/catalog.csv'
                        delimiter: ';'
                    outputs: [filter_valid]

                filter_valid:
                    service: '@CleverAge\ProcessBundle\Task\FilterTask'
                    options:
                        not_empty:
                            '[sku]': ~
                    outputs: [format]

                format:
                    service: '@CleverAge\ProcessBundle\Task\TransformerTask'
                    options:
                        transformers:
                            mapping:
                                mapping:
                                    key:
                                        code: '[sku]'
                                    value:
                                        code: '.' # The whole line
                    outputs: [store, count_rows]

                store:
                    service: '@CleverAge\CacheProcessBundle\Task\SetTask'
                    error_strategy: skip # A sku which is not a valid cache key is logged and skipped
                    options:
                        adapter: 'catalog' # The key and the value are given by the input

                count_rows:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\StatCounterTask'

        app.catalog_cache_read:
            description: 'Read a catalog line from the catalog cache'
            help: "bin/console cleverage:process:execute app.catalog_cache_read -c sku:\"'ABC-001'\""
            tasks:
                read:
                    service: '@CleverAge\CacheProcessBundle\Task\GetTask'
                    options:
                        adapter: 'catalog'
                        key: '{{ sku }}'
                        on_miss: skip
                    outputs: [log]

                log:
                    service: '@CleverAge\ProcessBundle\Task\Reporting\LoggerTask'
                    options:
                        level: info
                        message: 'Catalog line found in cache'
```

How it works:
- [CsvReaderTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/csv_reader_task.md) is
  iterable: each line goes through the following tasks before the next one is read.
- [FilterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/filter_task.md) skips the
  lines without `sku`, which cannot be used as cache key.
- The [TransformerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/transformer_task.md)
  builds a `key` / `value` array with the
  [mapping](https://github.com/cleverage/process-bundle/blob/main/docs/reference/transformers/mapping_transformer.md)
  transformer (`code: '.'` maps the whole line).
- [SetTask](../reference/tasks/set_task.md) merges this array over its options: the `key` and `value` of the current
  line complete the configured `adapter`, and the line is stored in the `catalog` adapter. With `error_strategy: skip`,
  a `sku` containing a PSR-6 reserved character (`{}()/\@:`) is logged and the next line is processed.
- [StatCounterTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/stat_counter_task.md)
  logs the number of stored lines at the end of the process.
- In the second process, [GetTask](../reference/tasks/get_task.md) reads the key given by the `sku` context value
  (see [contextual values](https://github.com/cleverage/process-bundle/blob/main/docs/01-quick_start.md#contextual-values)).
  Since the pool is persistent, the lines stored by the first process are available until they expire.
- With `on_miss: skip`, a missing key stops the branch, so the
  [LoggerTask](https://github.com/cleverage/process-bundle/blob/main/docs/reference/tasks/logger_task.md) only logs
  found lines.

Note that the items expire after the `default_lifetime` of the pool (or the `expires_after` option of SetTask):
schedule the warm up process more often than this lifetime if the other processes must always find the data.
