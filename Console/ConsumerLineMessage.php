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

namespace Drift\Bus\Console;

use Drift\Console\OutputPrinter;

/**
 * Class ConsumerLineMessage.
 */
final class ConsumerLineMessage
{
    /**
     * @var string
     */
    const CONSUMED = 'Consumed';

    /**
     * @var string
     */
    const IGNORED = 'Ignored ';

    /**
     * @var string
     */
    const REJECTED = 'Rejected';

    private $class;
    private $elapsedTime;
    private $status;

    /**
     * ConsumerMessage constructor.
     *
     * @param object $command
     * @param string $elapsedTime
     * @param string $status
     */
    public function __construct(
        $command,
        string $elapsedTime,
        string $status
    ) {
        $this->class = $this->getCommandName($command);
        $this->elapsedTime = $elapsedTime;
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
        if (self::IGNORED === $this->status) {
            $color = '36';
        } elseif (self::REJECTED === $this->status) {
            $color = '31';
        }

        $outputPrinter->print("\033[01;{$color}m{$this->status}\033[0m");
        $outputPrinter->print(" {$this->class} ");
        $outputPrinter->print("(\e[00;37m".$this->elapsedTime.' | '.((int) (memory_get_usage() / 1000000))." MB\e[0m)");
        $outputPrinter->printLine();
    }

    /**
     * Get command name.
     *
     * @param object $command
     *
     * @return string
     */
    private function getCommandName($command): string
    {
        $commandNamespace = get_class($command);
        $commandParts = explode('\\', $commandNamespace);

        return end($commandParts);
    }
}
