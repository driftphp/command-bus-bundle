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

namespace Drift\CommandBus\Tests\CommandHandler;

use Drift\CommandBus\Tests\Command\NotRecoverableCommand;

class NotRecoverableCommandHandler
{
    /**
     * @param NotRecoverableCommand $notRecoverableCommand
     *
     * @throws \Exception
     */
    public function handle(NotRecoverableCommand $notRecoverableCommand)
    {
        throw new \Exception();
    }
}
