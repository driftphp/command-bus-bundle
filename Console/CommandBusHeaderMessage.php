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

namespace Drift\CommandBus\Console;

use Drift\Console\OutputPrinter;

/**
 * Class ConsumerHeaderMessage.
 */
final class CommandBusHeaderMessage extends CommandMessage
{
    private string $message;

    /**
     * ConsumerMessage constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        $outputPrinter->print("\033[01;32mBUS\033[0m {$this->message}");
        $outputPrinter->printLine();
    }
}
