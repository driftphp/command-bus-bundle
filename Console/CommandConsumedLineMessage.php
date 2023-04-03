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
 * Class ConsumerLineMessage.
 */
final class CommandConsumedLineMessage extends CommandMessage
{
    /**
     * @var string
     */
    const CONSUMED = '[CON]';

    /**
     * @var string
     */
    const IGNORED = '[IGN]';

    /**
     * @var string
     */
    const REJECTED = '[REJ]';

    private string $class;
    private string $elapsedTime;
    private string $status;

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

        $performance = $this->styledPerformance($this->elapsedTime);
        $outputPrinter->print("\033[01;32mBUS\033[0m $performance \033[01;{$color}m{$this->status}\033[0m {$this->class}");
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
