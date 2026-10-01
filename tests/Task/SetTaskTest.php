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
use CleverAge\CacheProcessBundle\Task\SetTask;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface as SymfonyAdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[CoversClass(SetTask::class)]
#[UsesClass(Adapter::class)]
#[UsesClass(AdapterRegistry::class)]
#[UsesClass(MissingAdapterException::class)]
class SetTaskTest extends TestCase
{
    private Adapter $adapter;

    private ?CacheItemInterface $savedItem = null;

    protected function setUp(): void
    {
        // Keep the last saved item, to check its expiry
        $onSave = function (CacheItemInterface $item): void {
            $this->savedItem = $item;
        };
        $this->adapter = new class(new ArrayAdapter(), 'memory', $onSave) extends Adapter {
            public function __construct(SymfonyAdapterInterface $adapter, string $code, private readonly \Closure $onSave)
            {
                parent::__construct($adapter, $code);
            }

            #[\Override]
            public function save(CacheItemInterface $item): bool
            {
                ($this->onSave)($item);

                return parent::save($item);
            }
        };
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

    public function testNoOutput(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => 'value1']);

        $this->execute($task, $state, ['sku' => 'ABC-001']);

        self::assertNull($state->getOutput());
    }

    public function testExistingItemIsOverwritten(): void
    {
        $this->adapter->save($this->adapter->getItem('key1')->set('old'));
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => 'new']);

        $this->execute($task, $state, null);

        self::assertSame('new', $this->adapter->getItem('key1')->get());
    }

    public function testNullValueIsStored(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => null]);

        $this->execute($task, $state, null);

        self::assertTrue($this->adapter->hasItem('key1'));
        self::assertNull($this->adapter->getItem('key1')->get());
    }

    public function testKeyFromContext(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '{{ sku }}', 'value' => 'value1'], ['sku' => 'ABC-001']);

        $this->execute($task, $state, null);

        self::assertSame('value1', $this->adapter->getItem('ABC-001')->get());
    }

    public function testMissingAdapter(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'missing', 'key' => 'key1', 'value' => 'value1']);

        $this->expectException(MissingAdapterException::class);
        $this->expectExceptionMessage('Adapter missing is missing');
        $this->execute($task, $state, null);
    }

    public function testOptionsFromInputOnly(): void
    {
        [$task, $state] = $this->createTask([]);

        $this->execute($task, $state, ['adapter' => 'memory', 'key' => 'key1', 'value' => 'value1']);

        self::assertSame('value1', $this->adapter->getItem('key1')->get());
    }

    public function testRequiredOptionsOnExecution(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory']);

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "value" is missing.');
        $this->execute($task, $state, ['key' => 'key1']);
    }

    /**
     * Rejected by the task, also when assertions are disabled (Symfony adapters only validate the keys with assert()).
     */
    public function testInvalidKey(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => '']);

        $this->expectException(InvalidArgumentException::class);
        $this->execute($task, $state, ['value' => 'value1']);
    }

    public function testExpiresAfter(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => 'value1', 'expires_after' => 60]);

        $this->execute($task, $state, null);

        self::assertEqualsWithDelta(time() + 60, $this->getSavedExpiry(), 2);
    }

    public function testExpiresAfterFromInput(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'value' => 'value1']);

        $this->execute($task, $state, ['key' => 'key1', 'expires_after' => 3600]);

        self::assertEqualsWithDelta(time() + 3600, $this->getSavedExpiry(), 2);
    }

    public function testNoExpirationByDefault(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => 'value1']);

        $this->execute($task, $state, null);

        // Default lifetime of the adapter
        self::assertNull($this->getSavedExpiry());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidExpiresAfter(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-60];
        yield 'string' => ['60'];
    }

    #[DataProvider('provideInvalidExpiresAfter')]
    public function testInvalidExpiresAfterAtInitialization(mixed $expiresAfter): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => 'value1', 'expires_after' => $expiresAfter]);
    }

    public function testUndefinedOptionAtInitialization(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->createTask(['adapter' => 'memory', 'key' => 'key1', 'value' => 'value1', 'ttl' => 60]);
    }

    /**
     * Expiry timestamp of the last item saved by the task (the PSR-6 items do not expose it).
     */
    private function getSavedExpiry(): float|int|null
    {
        self::assertInstanceOf(CacheItem::class, $this->savedItem);

        /** @var float|int|null $expiry */
        $expiry = (new \ReflectionProperty(CacheItem::class, 'expiry'))->getValue($this->savedItem);

        return $expiry;
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $context
     *
     * @return array{SetTask, ProcessState}
     */
    private function createTask(array $options, array $context = []): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext($context);
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
