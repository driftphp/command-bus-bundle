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

namespace Drift\CommandBus\Tests;

use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Bus\InlineCommandBus;
use Drift\CommandBus\Bus\QueryBus;

/**
 * Class Service.
 */
class Service
{
    private CommandBus $commandBus;
    private InlineCommandBus $inlineCommandBus;
    private QueryBus $queryBus;

    /**
     * @param CommandBus       $commandBus
     * @param InlineCommandBus $inlineCommandBus
     * @param QueryBus         $queryBus
     */
    public function __construct(
        CommandBus $commandBus,
        InlineCommandBus $inlineCommandBus,
        QueryBus $queryBus
    ) {
        $this->commandBus = $commandBus;
        $this->inlineCommandBus = $inlineCommandBus;
        $this->queryBus = $queryBus;
    }
}
