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

namespace CleverAge\CacheProcessBundle\Tests\Task;

use CleverAge\CacheProcessBundle\Adapter\Adapter;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\CacheProcessBundle\Task\SetTask;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[CoversClass(SetTask::class)]
#[UsesClass(Adapter::class)]
#[UsesClass(AdapterRegistry::class)]
class SetTaskTest extends TestCase
{
    private Adapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new Adapter(new ArrayAdapter(), 'memory');
    }

    public function testSetValue(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => ['column1' => 'value1']]);

        $this->execute($task, $state, null);

        self::assertSame(['column1' => 'value1'], $this->adapter->getItem('key1')->get());
    }

    public function testInputOverridesOptions(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '', 'value' => null]);

        $this->execute($task, $state, ['key' => 'key1', 'value' => 'value1', 'sku' => 'ignored']);
        $this->execute($task, $state, ['key' => 'key2', 'value' => 'value2']);

        self::assertSame('value1', $this->adapter->getItem('key1')->get());
        self::assertSame('value2', $this->adapter->getItem('key2')->get());
    }

    public function testNonArrayInputIsRejected(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => null]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('SetTask expects an array or null input, string given');
        $this->execute($task, $state, 'value1');
    }

    public function testInputValuesAreValidated(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '', 'value' => null]);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "adapter" with value 1 is expected to be of type "string", but is of type "int".');
        $this->execute($task, $state, ['adapter' => 1, 'key' => 'key1', 'value' => 'value1']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{SetTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('set', SetTask::class, $options));

        $registry = new AdapterRegistry();
        $registry->addAdapter($this->adapter);
        $task = new SetTask($registry);
        $task->initialize($state);

        return [$task, $state];
    }

    private function execute(SetTask $task, ProcessState $state, mixed $input): void
    {
        $state->reset(false);
        $state->setInput($input);
        $task->execute($state);
    }
}
