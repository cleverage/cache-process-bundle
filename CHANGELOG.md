Latest
------

### Changes
* [#61](https://github.com/cleverage/cache-process-bundle/issues/61) Composer: require PHP `^8.2` instead of `>=8.2` (PHP 9 is no longer accepted before being tested), remove the authorization of the `symfony/flex` plugin, which is not required (the CI installs it globally), as [cleverage/process-bundle#319](https://github.com/cleverage/process-bundle/issues/319).
* [#56](https://github.com/cleverage/cache-process-bundle/issues/56) Define the public API (Symfony BC promise, as [cleverage/process-bundle#279](https://github.com/cleverage/process-bundle/issues/279)): `GetTask`, `SetTask`, `AdapterRegistry` and `CleverAgeCacheProcessBundle` are marked `@final since 2.3`, they will be `final` in 3.0: extending them triggers a deprecation with the Symfony `DebugClassLoader`. `AbstractCacheTask`, `Adapter` (extended by the applications) and `MissingAdapterException` stay open. `CleverAgeCacheProcessExtension` and `RegisterAdaptersPass` are marked `@internal`.
* [#55](https://github.com/cleverage/cache-process-bundle/issues/55) `CleverAgeCacheProcessExtension::findServices()` loads the service definition files with a glob pattern instead of a `Finder` (same files, as [cleverage/process-bundle#271](https://github.com/cleverage/process-bundle/issues/271)), `symfony/finder` is no longer required. Add tests.
* [#53](https://github.com/cleverage/cache-process-bundle/issues/53) AdapterRegistry: the adapters are loaded lazily from a service locator indexed by code: an adapter is only instantiated when a task uses it (an error in an adapter that a process does not use no longer makes it fail). The code is given by the new `code` attribute of the `cleverage.cache.adapter` tag, or by the new `#[AsCacheAdapter(code: '...')]` attribute (autoconfiguration); it must be the one returned by `getCode()`. Duplicate or empty codes fail the container compilation. An adapter tagged without `code` is still registered eagerly, with `AdapterRegistry::addAdapter()`, which is deprecated (the `code` will be required in 3.0). New optional `AdapterRegistry` constructor argument (the locator), new `hasAdapter()` and `getAdapters()` methods. Require `symfony/service-contracts`. Update documentation, add tests.
* [#51](https://github.com/cleverage/cache-process-bundle/issues/51) Use the PHP 8.3 to 8.5 functions through the Symfony polyfills ([cleverage/process-bundle#299](https://github.com/cleverage/process-bundle/issues/299)): require `symfony/polyfill-php83`, `symfony/polyfill-php84` and `symfony/polyfill-php85`. Rector is configured with the minimum supported PHP version (`withPhpVersion(PhpVersion::PHP_82)`, it used PHP 8.5 features) and `withPhpSets()` without explicit version, which applies the rules using a newer function when its polyfill is required (no change on the current code). Document it in `CONTRIBUTING.md`.
* [#49](https://github.com/cleverage/cache-process-bundle/issues/49) Add a `CleverAge\CacheProcessBundle\Registry\AdapterRegistry` alias of the `cleverage_cache_process.registry.adapter` service: the registry can be autowired, e.g. in a custom cache task extending `AbstractCacheTask`. Update documentation, add tests.
* [#43](https://github.com/cleverage/cache-process-bundle/issues/43) Give a code to the tasks ([cleverage/process-bundle#91](https://github.com/cleverage/process-bundle/issues/91)): `cache_get` and `cache_set` (`task: cache_get`, with `cleverage/process-bundle` 5.4 or later, referencing them by `service` being deprecated there). The tasks have their class name as service id; fetching them directly from the service container is deprecated (`container.private`), they will be private in 3.0. The former ids `cleverage_cache_process.task.get` / `cleverage_cache_process.task.set` are deprecated aliases, removed in 3.0. Update documentation, add tests.
* [#41](https://github.com/cleverage/cache-process-bundle/issues/41) CI: test the lowest versions allowed by `composer.json` (`--prefer-lowest`, PHP 8.2).
* [#39](https://github.com/cleverage/cache-process-bundle/issues/39) Add a `.gitattributes`: the development files (`.docker/`, `.github/`, `tests/`, quality tools and PHPUnit configurations, `Makefile`) are no longer part of the archive installed by Composer (`dist`).
* [#37](https://github.com/cleverage/cache-process-bundle/issues/37) Require `psr/cache`, used directly by `Adapter` and `AbstractCacheTask` but only installed through `symfony/cache` and `cleverage/process-bundle`.

v2.2
------

### Changes
* [#30](https://github.com/cleverage/cache-process-bundle/issues/30) GetTask and SetTask: `adapter`, `key` and `value` are no longer required at configuration level (no placeholders needed), only once merged with the input; the key is validated by the task, so an invalid key always throws (Symfony adapters only validate keys with `assert()`). New `AbstractCacheTask::getRequiredOptions()`. Update documentation, add tests.
* [#31](https://github.com/cleverage/cache-process-bundle/issues/31) GetTask: add an `on_miss` option (`output_null` by default, `skip` to send the input to the error outputs, `fail`) to handle cache misses. Update documentation, add tests.
* [#32](https://github.com/cleverage/cache-process-bundle/issues/32) SetTask: add an `expires_after` option, to set the lifetime of the items. Update documentation, add tests.
* [#20](https://github.com/cleverage/cache-process-bundle/issues/20) Add missing tests: GetTask and SetTask (options validation at initialization, context, missing adapter, stored `null`, overwriting), custom tasks extending AbstractCacheTask, Adapter, bundle and DI extension. Remove the obsolete `test.old` directory (tests of the v1 tasks and transformers).
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
