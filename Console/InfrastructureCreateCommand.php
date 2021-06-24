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

use Clue\React\Block;
use Drift\CommandBus\Async\AsyncAdapter;
use Drift\Console\OutputPrinter;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InfrastructureCreateCommand.
 */
class InfrastructureCreateCommand extends Command
{
    /**
     * @var AsyncAdapter
     */
    private $asyncAdapter;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * ConsumeCommand constructor.
     *
     * @param AsyncAdapter  $asyncAdapter
     * @param LoopInterface $loop
     */
    public function __construct(
        AsyncAdapter $asyncAdapter,
        LoopInterface $loop
    ) {
        parent::__construct();

        $this->asyncAdapter = $asyncAdapter;
        $this->loop = $loop;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setDescription('Creates the infrastructure to make the command bus work asynchronously');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force the action'
        );
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
        $outputPrinter = new OutputPrinter($output, false, false);
        if (!$input->getOption('force')) {
            (new CommandBusHeaderMessage('', 'Please, use the flag --force'))->print($outputPrinter);

            return 1;
        }

        $adapterName = $this->asyncAdapter->getName();
        (new CommandBusHeaderMessage('', 'Started building infrastructure...'))->print($outputPrinter);
        (new CommandBusHeaderMessage('', 'Using adapter '.$adapterName))->print($outputPrinter);

        $promise = $this
            ->asyncAdapter
            ->createInfrastructure($outputPrinter);

        Block\await($promise, $this->loop);
        (new CommandBusHeaderMessage('', 'Infrastructure built'))->print($outputPrinter);

        return 0;
    }
}
