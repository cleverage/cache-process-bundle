Adapter
===============

Create cache adapter.

Reference
--------------

* **Adapter Service Interface**: `CleverAge\CacheProcessBundle\Adapter\AdapterInterface`

Options
-------

| Code      | Type     | Required | Default | Description                                                |
|-----------|----------|:--------:|---------|------------------------------------------------------------|
| `code`    | `string` |  **X**   |         | Service identifier, used by Task adapter option            |
| `adapter` | `string` |  **X**   |         | `Symfony\Component\Cache\Adapter\AdapterInterface` service |

Examples
--------

```php
<?php

declare(strict_types=1);

namespace App\Adapter;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MemoryAdapter extends Adapter
{
    public function __construct()
    {
        $cache = new ArrayAdapter();
        parent::__construct($cache, 'memory');
    }
}
```

```yaml
services:
  app.cleverage_cache_process.adapter.memory:
    class: App\Adapter\MemoryAdapter
    tags:
      - { name: cleverage.cache.adapter }
```
