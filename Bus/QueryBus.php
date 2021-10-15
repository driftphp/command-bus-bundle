<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\CommandBus\Bus;

use Drift\CommandBus\Exception\InvalidCommandException;
use React\Promise\PromiseInterface;
use function React\Promise\reject;

/**
 * Class QueryBus.
 */
class QueryBus extends Bus
{
    /**
     * Ask query.
     *
     * @param object $query
     *
     * @return PromiseInterface
     *
     * @throws InvalidCommandException
     */
    public function ask($query): PromiseInterface
    {
        try {
            return $this->handle($query);
        } catch (\Exception $exception) {
            return reject($exception);
        }
    }
}
