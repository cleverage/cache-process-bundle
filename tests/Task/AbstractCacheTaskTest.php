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

use CleverAge\CacheProcessBundle\Registry\AdapterRegistry;
use CleverAge\CacheProcessBundle\Task\AbstractCacheTask;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom cache tasks extending AbstractCacheTask get their own options from the input.
 */
#[CoversClass(AbstractCacheTask::class)]
class AbstractCacheTaskTest extends TestCase
{
    public function testCustomOptionFromInput(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1']);

        // Resolved options: the order of the keys is not relevant
        self::assertEquals(
            ['adapter' => 'memory', 'key' => 'key2', 'ttl' => 60],
            $this->execute($task, $state, ['key' => 'key2', 'ttl' => 60, 'sku' => 'ignored'])
        );
        self::assertEquals(['adapter' => 'memory', 'key' => 'key1', 'ttl' => null], $this->execute($task, $state, null));
    }

    public function testCustomOptionFromInputIsValidated(): void
    {
        [$task, $state] = $this->createTask(['adapter' => 'memory', 'key' => 'key1']);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "ttl" with value "60" is expected to be of type "int" or "null", but is of type "string".');
        $this->execute($task, $state, ['ttl' => '60']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{AbstractCacheTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('custom', AbstractCacheTask::class, $options));

        $task = new class(new AdapterRegistry()) extends AbstractCacheTask {
            public function execute(ProcessState $state): void
            {
                $state->setOutput($this->getMergedOptions($state));
            }

            #[\Override]
            protected function configureOptions(OptionsResolver $resolver): void
            {
                parent::configureOptions($resolver);

                $resolver->setDefault('ttl', null);
                $resolver->setAllowedTypes('ttl', ['int', 'null']);
            }
        };
        $task->initialize($state);

        return [$task, $state];
    }

    private function execute(AbstractCacheTask $task, ProcessState $state, mixed $input): mixed
    {
        $state->reset(false);
        $state->setInput($input);
        $task->execute($state);

        return $state->getOutput();
    }
}
