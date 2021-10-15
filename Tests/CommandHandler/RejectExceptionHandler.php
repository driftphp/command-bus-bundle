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

use Drift\CommandBus\Tests\Command\RejectException;
use function React\Promise\reject;

/**
 * Class RejectExceptionHandler.
 */
class RejectExceptionHandler
{
    /**
     * Handle.
     */
    public function handle(RejectException $rejectException)
    {
        return reject(new \Exception());
    }
}
