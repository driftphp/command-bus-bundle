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
 * Class CommandBusLineMessage.
 */
final class CommandBusLineMessage
{
    /**
     * @var string
     */
    const OK = 'OK ';

    /**
     * @var string
     */
    const INFO = 'INF';

    /**
     * @var string
     */
    const ERROR = 'ERR';

    private $text;
    private $status;

    /**
     * ConsumerMessage constructor.
     *
     * @param string $text
     * @param string $status
     */
    public function __construct(
        string $text,
        string $status = self::INFO
    ) {
        $this->text = $text;
        $this->status = $status;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        $color = '32';
        if (self::INFO === $this->status) {
            $color = '36';
        } elseif (self::ERROR === $this->status) {
            $color = '31';
        }

        $outputPrinter->print("\033[01;{$color}m{$this->status} \033[0m");
        $outputPrinter->print("(\e[00;37m | ".((int) (memory_get_usage() / 1000000))." MB\e[0m)");
        $outputPrinter->print(" {$this->text} ");
        $outputPrinter->printLine();
    }
}
