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

namespace CleverAge\CacheProcessBundle\Tests\Functional;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\CacheProcessBundle\Task\GetTask;
use CleverAge\CacheProcessBundle\Task\SetTask;
use CleverAge\CacheProcessBundle\Tests\App\TestKernel;
use CleverAge\CacheProcessBundle\Tests\Fixtures\AttributedAdapter;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The bundle in an application with process-bundle (see TestKernel): the container compiles, the services of the
 * bundle are resolved with their dependencies.
 */
#[CoversNothing]
class KernelTest extends KernelTestCase
{
    private mixed $exceptionHandler;

    protected function setUp(): void
    {
        $this->exceptionHandler = $this->getExceptionHandler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // The ErrorHandler registered when the kernel boots is not removed by the first Symfony 6.4 versions
        if ($this->getExceptionHandler() !== $this->exceptionHandler) {
            restore_exception_handler();
        }
    }

    public function testAdaptersAreResolvedByCode(): void
    {
        $registry = self::getContainer()->get('test.adapter_registry');
        self::assertInstanceOf(AdapterRegistry::class, $registry);

        self::assertInstanceOf(Adapter::class, $registry->getAdapter('app_tagged'));
        self::assertInstanceOf(AttributedAdapter::class, $registry->getAdapter('app_attributed'));
    }

    public function testTasksAreResolvedByCode(): void
    {
        if (!class_exists(TaskRegistry::class)) {
            self::markTestSkipped('The tasks are referenced by code since cleverage/process-bundle 5.4.');
        }
        $processConfigurationRegistry = self::getContainer()->get('test.process_configuration_registry');
        self::assertInstanceOf(ProcessConfigurationRegistry::class, $processConfigurationRegistry);
        $taskRegistry = self::getContainer()->get('test.task_registry');
        self::assertInstanceOf(TaskRegistry::class, $taskRegistry);

        $processConfiguration = $processConfigurationRegistry->getProcessConfiguration('test.cache');
        self::assertInstanceOf(GetTask::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration('get')));
        self::assertInstanceOf(SetTask::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration('set')));
    }

    private function getExceptionHandler(): mixed
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
