<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\CacheProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * Class DeleterTask
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class DeleterTask extends AbstractCacheTask
{
    /**
     * @param ProcessState $state
     *
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $keyValue = $this->getKeyCache($state);
        $input = $state->getInput();

        $this->getCache()->deleteItem($keyValue);

        $state->setOutput($input);
    }
}
