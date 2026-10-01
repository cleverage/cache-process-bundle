Latest
------

### Changes
* [#20](https://github.com/cleverage/cache-process-bundle/issues/20) Add missing tests: GetTask and SetTask (options validation at initialization, context, missing adapter, stored `null`, overwriting), custom tasks extending AbstractCacheTask, Adapter, bundle and DI extension.
* [#25](https://github.com/cleverage/cache-process-bundle/issues/25) Give the ids of both services in the error on duplicate adapter codes: the adapters are registered by a compiler pass of the bundle, `AdapterRegistry::addAdapter()` gets an optional `$serviceId` argument. Update documentation, add tests.

### Fixes
* [#28](https://github.com/cleverage/cache-process-bundle/issues/28) Fix documentation: invalid cache keys are only rejected by Symfony adapters when assertions are enabled (an exception was documented in every case).
* [#22](https://github.com/cleverage/cache-process-bundle/issues/22) Fix GetTask and SetTask: throw an explicit `\UnexpectedValueException` on a non-array input (a `\TypeError` was triggered by `array_merge()`). Update documentation, add tests.
* [#23](https://github.com/cleverage/cache-process-bundle/issues/23) Fix GetTask and SetTask: validate the option values given by the input with the options resolver (they were used as is). Update documentation, add tests.

v2.1
------

### Changes
* [#17](https://github.com/cleverage/cache-process-bundle/issues/17) Update quality stack: use Rector `withComposerBased()` sets (removed `SYMFONY_64` / `PHPUNIT_100` sets), declare used Symfony packages and PHPUnit range in composer.json, apply quality tools fixes
* [#19](https://github.com/cleverage/cache-process-bundle/issues/19) Add missing documentations: complete Adapter, GetTask & SetTask reference pages, configuration and custom cache tasks guides, cookbooks. Harmonize and fix existing documentation.

v2.0
------

### Changes
* [#10](https://github.com/cleverage/cache-process-bundle/issues/10) Add support for PHP 8.5 and Symfony 8.* Update phpunit/phpunit to version >10.0 Bump version to cleverage/process-bundle ^5.0

### BC breaks
* [#10](https://github.com/cleverage/cache-process-bundle/issues/10) Remove support for PHP 8.1 and Symfony 7.3

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
