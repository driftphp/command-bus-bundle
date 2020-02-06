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

use Drift\CommandBus\Bus\Bus;
use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Bus\InlineCommandBus;
use Drift\CommandBus\Bus\QueryBus;
use Drift\CommandBus\Middleware\HandlerMiddleware;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DebugCommandBusCommand.
 */
class DebugCommandBusCommand extends Command
{
    protected static $defaultName = 'debug:command-bus';
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var InlineCommandBus
     */
    private $inlineCommandBus;

    /**
     * @var QueryBus
     */
    private $queryBus;

    /**
     * BusDebugger constructor.
     *
     * @param CommandBus       $commandBus
     * @param InlineCommandBus $commandBus
     * @param QueryBus         $queryBus
     */
    public function __construct(
        CommandBus $commandBus,
        InlineCommandBus $inlineCommandBus,
        QueryBus $queryBus
    ) {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->inlineCommandBus = $inlineCommandBus;
        $this->queryBus = $queryBus;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setDescription('Dumps the command bus configuration, including middlewares and handlers');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $this->printBus('Query', $this->queryBus, $output);
        $this->printBus('Command', $this->commandBus, $output);
        $this->printBus('Inline Command', $this->inlineCommandBus, $output);

        return 0;
    }

    /**
     * Print bus.
     *
     * @param string          $name
     * @param Bus             $bus
     * @param OutputInterface $output
     */
    private function printBus(
        string $name,
        Bus $bus,
        OutputInterface $output
    ) {
        $output->writeln("  $name Bus  ");
        $output->writeln('----------------------');
        $middlewareList = $bus->getMiddlewareList();

        foreach ($middlewareList as $middleware) {
            $output->writeln('  - '.$middleware['class'].'::'.$middleware['method']);

            if (HandlerMiddleware::class === $middleware['class']) {
                foreach ($middleware['handlers'] as $object => $handler) {
                    $output->writeln('      > '.$object.' -> '.$handler['handler'].'::'.$middleware['method']);
                }
            }
        }

        $output->writeln('');
        $output->writeln('');
    }
}
