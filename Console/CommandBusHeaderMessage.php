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
final class CommandBusHeaderMessage
{
    private $elapsedTime;
    private $message;

    /**
     * ConsumerMessage constructor.
     *
     * @param string $elapsedTime
     * @param string $message
     */
    public function __construct(
        string $elapsedTime,
        string $message
    ) {
        $this->elapsedTime = $elapsedTime;
        $this->message = $message;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        $color = '32';

        $outputPrinter->print("\033[01;{$color}mBUS\033[0m ");
        $outputPrinter->print("(\e[00;37m".$this->elapsedTime.' | '.((int) (memory_get_usage() / 1000000))." MB\e[0m)");
        $outputPrinter->print(" {$this->message}");
        $outputPrinter->printLine();
    }
}
