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

use Drift\CommandBus\Async\AsyncAdapter;
use Drift\CommandBus\Bus\InlineCommandBus;
use Drift\Console\OutputPrinter;
use Drift\EventBus\Bus\EventBus;
use Drift\EventBus\Subscriber\EventBusSubscriber;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CommandConsumer.
 */
class CommandConsumerCommand extends Command
{
    private AsyncAdapter $asyncAdapter;
    private InlineCommandBus $commandBus;
    private ?EventBusSubscriber $eventBusSubscriber;

    /**
     * ConsumeCommand constructor.
     *
     * @param AsyncAdapter            $asyncAdapter
     * @param InlineCommandBus        $commandBus
     * @param EventBusSubscriber|null $eventBusSubscriber
     */
    public function __construct(
        AsyncAdapter $asyncAdapter,
        InlineCommandBus $commandBus,
        ?EventBusSubscriber $eventBusSubscriber
    ) {
        parent::__construct();

        $this->asyncAdapter = $asyncAdapter;
        $this->commandBus = $commandBus;
        $this->eventBusSubscriber = $eventBusSubscriber;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setDescription('Start consuming asynchronous commands from the command bus');
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of jobs to handle before dying',
            0
        );

        /*
         * If we have the EventBus loaded, we can add listeners as well
         */
        if (class_exists(EventBus::class)) {
            $this->addOption(
                'exchange',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exchanges to listen'
            );
        }
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
        $adapterName = $this->asyncAdapter->getName();
        (new CommandBusHeaderMessage('', 'Consumer built'))->print($outputPrinter);
        (new CommandBusHeaderMessage('', 'Using adapter '.$adapterName))->print($outputPrinter);
        (new CommandBusHeaderMessage('', 'Started listening...'))->print($outputPrinter);

        $exchanges = self::buildQueueArray($input);
        if (
            class_exists(EventBusSubscriber::class) &&
            !empty($exchanges) &&
            !is_null($this->eventBusSubscriber)
        ) {
            (new CommandBusHeaderMessage('', 'Kernel connected to exchanges.'))->print($outputPrinter);
            $this
                ->eventBusSubscriber
                ->subscribeToExchanges(
                    $exchanges,
                    $outputPrinter
                );
        }

        $this
            ->asyncAdapter
            ->consume(
                $this->commandBus,
                \intval($input->getOption('limit')),
                $outputPrinter
            );

        return 0;
    }

    /**
     * Build queue architecture from array of strings.
     *
     * @param InputInterface $input
     *
     * @return array
     */
    private static function buildQueueArray(InputInterface $input): array
    {
        if (!$input->hasOption('exchange')) {
            return [];
        }

        $exchanges = [];
        foreach ($input->getOption('exchange') as $exchange) {
            $exchangeParts = explode(':', $exchange, 2);
            $exchanges[$exchangeParts[0]] = $exchangeParts[1] ?? '';
        }

        return $exchanges;
    }
}
