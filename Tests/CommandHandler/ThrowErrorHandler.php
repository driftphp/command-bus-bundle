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

use Drift\CommandBus\Tests\Command\ThrowError;

/**
 * Class ThrowErrorHandler.
 */
class ThrowErrorHandler
{
    /**
     * Handle.
     */
    public function handle(ThrowError $throwError)
    {
        var_dump('N');
        (function (string $a) {
        })(1);
    }
}
