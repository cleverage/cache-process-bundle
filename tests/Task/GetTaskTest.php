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
use CleverAge\CacheProcessBundle\Exception\MissingAdapterException;
use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\CacheProcessBundle\Task\GetTask;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[CoversClass(GetTask::class)]
#[UsesClass(Adapter::class)]
#[UsesClass(AdapterRegistry::class)]
#[UsesClass(MissingAdapterException::class)]
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

    public function testGetStoredNullValue(): void
    {
        $this->adapter->save($this->adapter->getItem('null')->set(null));
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'null']);

        self::assertNull($this->execute($task, $state, null));
    }

    public function testKeyFromContext(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '{{ sku }}'], ['sku' => 'key2']);

        self::assertSame('value2', $this->execute($task, $state, null));
    }

    public function testEmptyArrayInputUsesOptions(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1']);

        self::assertSame('value1', $this->execute($task, $state, []));
    }

    public function testInputOverridesAdapter(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'other', 'key' => 'key1']);

        self::assertSame('value1', $this->execute($task, $state, ['adapter' => 'memory']));
    }

    public function testMissingAdapter(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'missing', 'key' => 'key1']);

        $this->expectException(MissingAdapterException::class);
        $this->expectExceptionMessage('Adapter missing is missing');
        $this->execute($task, $state, null);
    }

    public function testOptionsFromInputOnly(): void
    {
        [$task, $state] = $this->createTask([]);

        self::assertSame('value1', $this->execute($task, $state, ['adapter' => 'memory', 'key' => 'key1']));
    }

    public function testRequiredOptionsOnExecution(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory']);

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "key" is missing.');
        $this->execute($task, $state, ['sku' => 'ABC-001']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidKeys(): iterable
    {
        yield 'empty' => [''];
        yield 'reserved character' => ['a/b'];
    }

    /**
     * Rejected by the task, also when assertions are disabled (Symfony adapters only validate the keys with assert()).
     */
    #[DataProvider('provideInvalidKeys')]
    public function testInvalidKey(string $key): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => $key]);

        $this->expectException(InvalidArgumentException::class);
        $this->execute($task, $state, null);
    }

    public function testOnMissSkip(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'on_miss' => 'skip']);

        $this->execute($task, $state, ['key' => 'missing', 'sku' => 'ABC-001']);

        self::assertTrue($state->isSkipped());
        self::assertTrue($state->hasErrorOutput());
        self::assertSame(['key' => 'missing', 'sku' => 'ABC-001'], $state->getErrorOutput());
        self::assertNull($state->getOutput());
    }

    public function testOnMissSkipWithHit(): void
    {
        $this->adapter->save($this->adapter->getItem('null')->set(null));
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'on_miss' => 'skip']);

        self::assertSame('value1', $this->execute($task, $state, ['key' => 'key1']));
        self::assertFalse($state->isSkipped());
        self::assertFalse($state->hasErrorOutput());

        // A stored null value is a hit
        self::assertNull($this->execute($task, $state, ['key' => 'null']));
        self::assertFalse($state->isSkipped());
        self::assertFalse($state->hasErrorOutput());
    }

    public function testOnMissFail(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'missing', 'on_miss' => 'fail']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Cache item missing is missing from adapter memory');
        $this->execute($task, $state, null);
    }

    public function testOnMissOutputNullByDefault(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'missing']);

        self::assertNull($this->execute($task, $state, null));
        self::assertFalse($state->isSkipped());
        self::assertFalse($state->hasErrorOutput());
    }

    public function testInvalidOnMissAtInitialization(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'on_miss' => 'ignore']);
    }

    public function testUndefinedOptionAtInitialization(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => 'value1']);
    }

    public function testInvalidOptionTypeAtInitialization(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "adapter" with value 1 is expected to be of type "string", but is of type "int".');
        $this->createTask(['adapter' => 1, 'key' => 'key1']);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $context
     *
     * @return array{GetTask, ProcessState}
     */
    private function createTask(array $options, array $context = []): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext($context);
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
