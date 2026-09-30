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
use CleverAge\CacheProcessBundle\Task\GetTask;
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

#[CoversClass(GetTask::class)]
#[UsesClass(Adapter::class)]
#[UsesClass(AdapterRegistry::class)]
class GetTaskTest extends TestCase
{
    private Adapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new Adapter(new ArrayAdapter(), 'memory');
        $this->adapter->save($this->adapter->getItem('key1')->set('value1'));
        $this->adapter->save($this->adapter->getItem('key2')->set('value2'));
    }

    public function testGetValue(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1']);

        self::assertSame('value1', $this->execute($task, $state, null));
    }

    public function testGetMissingValue(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'missing']);

        self::assertNull($this->execute($task, $state, null));
    }

    public function testInputOverridesOptions(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '']);

        self::assertSame('value1', $this->execute($task, $state, ['key' => 'key1', 'sku' => 'ignored']));
        self::assertSame('value2', $this->execute($task, $state, ['key' => 'key2']));
    }

    public function testNonArrayInputIsRejected(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('GetTask expects an array or null input, string given');
        $this->execute($task, $state, 'key1');
    }

    public function testInputValuesAreValidated(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '']);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "key" with value 1 is expected to be of type "string", but is of type "int".');
        $this->execute($task, $state, ['key' => 1]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{GetTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('get', GetTask::class, $options));

        $registry = new AdapterRegistry();
        $registry->addAdapter($this->adapter);
        $task = new GetTask($registry);
        $task->initialize($state);

        return [$task, $state];
    }

    private function execute(GetTask $task, ProcessState $state, mixed $input): mixed
    {
        $state->reset(false);
        $state->setInput($input);
        $task->execute($state);

        return $state->getOutput();
    }
}
