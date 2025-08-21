v1.1
------

### Changes
* [#8](https://github.com/cleverage/cache-process-bundle/issues/8) Upgrade to Symfony 7.3 & PHP 8.4

v1.0.0
------

* Initial stable release

## BC breaks

* [#3](https://github.com/cleverage/cache-process-bundle/issues/3) Bump dependency "cleverage/process-bundle": "^4.0"
* [#5](https://github.com/cleverage/cache-process-bundle/issues/5) Update services according to Symfony best practices. Services should not use autowiring or autoconfiguration. Instead, all services should be defined explicitly.
  Services must be prefixed with the bundle alias instead of using fully qualified class names => `cleverage_cache_process`
* [#1](https://github.com/cleverage/cache-process-bundle/issues/1) Rework Tasks & Transfomers using AdapterRegistry

### Changes

* [#4](https://github.com/cleverage/cache-process-bundle/issues/4) Add Makefile & .docker for local standalone usage
* [#4](https://github.com/cleverage/cache-process-bundle/issues/4) Add rector, phpstan & php-cs-fixer configurations & apply it

v0.3
------

* Legacy release
