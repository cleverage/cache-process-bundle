<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/CacheProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\CacheProcessBundle\Tests\App;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use CleverAge\CacheProcessBundle\CleverAgeCacheProcessBundle;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\CacheProcessBundle\Tests\Fixtures\AttributedAdapter;
use CleverAge\ProcessBundle\CleverAgeProcessBundle;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

/**
 * Application used by the integration tests: process-bundle and this bundle, with an adapter declared by tag and one
 * by attribute, in a temporary directory of its own (per PHP process).
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new CleverAgeProcessBundle();
        yield new CleverAgeCacheProcessBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->getRunDir().'/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getRunDir().'/logs';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'validation' => ['email_validation_mode' => 'html5'],
        ]);
        if (Kernel::VERSION_ID >= 70300) {
            // Not setting it is deprecated since Symfony 7.3: its default value changes in 8.0
            $container->extension('framework', ['property_info' => ['with_constructor_extractor' => true]]);
        }

        $services = $container->services();
        $services->set('app.cache_adapter', Adapter::class)
            ->args([inline_service(ArrayAdapter::class), 'app_tagged'])
            ->tag('cleverage.cache.adapter', ['code' => 'app_tagged']);
        $services->set(AttributedAdapter::class)
            ->autoconfigure()
            ->args([inline_service(ArrayAdapter::class), 'app_attributed']);
        // Exposed to the tests
        $services->alias('test.adapter_registry', AdapterRegistry::class)->public();

        // Tasks referenced by code (task: <code>): process-bundle 5.4 or later
        if (class_exists(TaskRegistry::class)) {
            $container->extension('clever_age_process', [
                'configurations' => [
                    'test.cache' => [
                        'tasks' => [
                            'get' => [
                                'task' => 'cache_get',
                                'options' => ['adapter' => 'app_tagged', 'key' => 'key'],
                            ],
                            'set' => [
                                'task' => 'cache_set',
                                'options' => ['adapter' => 'app_tagged', 'key' => 'key'],
                            ],
                        ],
                    ],
                ],
            ]);
            $services->alias('test.task_registry', TaskRegistry::class)->public();
            $services->alias('test.process_configuration_registry', ProcessConfigurationRegistry::class)->public();
        }
    }

    private function getRunDir(): string
    {
        return sys_get_temp_dir().'/cache_process_bundle_tests_'.getmypid();
    }
}
