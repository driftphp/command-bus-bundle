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

namespace Drift\Bus;

use React\Promise\PromiseInterface;

/**
 * Class QueryBus.
 */
class QueryBus
{
    /**
     * @var Bus
     */
    private $bus;

    /**
     * QueryBus constructor.
     *
     * @param Bus $bus
     */
    public function __construct(Bus $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Ask query.
     *
     * @param object $query
     *
     * @return PromiseInterface
     */
    public function ask($query): PromiseInterface
    {
        return $this
            ->bus
            ->handle($query);
    }
}
