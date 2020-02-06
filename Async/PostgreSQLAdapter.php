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

namespace Drift\CommandBus\Async;

use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Console\CommandBusLineMessage;
use Drift\CommandBus\Exception\InvalidCommandException;
use Drift\Console\OutputPrinter;
use function Clue\React\Block\await;
use PgAsync\Client;
use PgAsync\Message\NotificationResponse;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * Class PostgreSQLAdapter.
 */
class PostgreSQLAdapter extends AsyncAdapter
{
    /**
     * @var Client
     */
    private $postgres;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var string
     */
    private $key;

    /**
     * PostgreSQLAdapter constructor.
     *
     * @param Client        $postgres
     * @param LoopInterface $loop
     * @param string        $key
     */
    public function __construct(
        Client $postgres,
        LoopInterface $loop,
        string $key
    ) {
        $this->postgres = $postgres;
        $this->loop = $loop;
        $this->key = $key;
    }

    /**
     * Get adapter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'PostgreSQL';
    }

    /**
     * Create infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    public function createInfrastructure(OutputPrinter $outputPrinter): PromiseInterface
    {
        $createQuerySQL = sprintf(<<<'SQL'
CREATE TABLE IF NOT EXISTS %1$s (id VARCHAR, added_at TIMESTAMP, payload TEXT);
SQL
            , $this->key);

        return $this
            ->postgres
            ->query($createQuerySQL)
            ->toPromise()
            ->then(function () use ($outputPrinter) {
                $procedureSQL = sprintf(<<<'SQL'
LOCK TABLE %1$s;
-- create trigger function
CREATE OR REPLACE FUNCTION notify_%1$s() RETURNS TRIGGER AS $$
	BEGIN
		PERFORM pg_notify('%1$s', '1');
		RETURN NEW;
    END;
$$ LANGUAGE plpgsql;
-- register trigger
DROP TRIGGER IF EXISTS notify_trigger ON %1$s;
CREATE TRIGGER notify_trigger
AFTER INSERT
ON %1$s
FOR EACH ROW EXECUTE PROCEDURE notify_%1$s();
SQL
                    , $this->key);

                return $this
                    ->postgres
                    ->query($procedureSQL)
                    ->toPromise()
                    ->then(function () use ($outputPrinter) {
                        (new CommandBusLineMessage(sprintf('Queue with name %s created properly', $this->key)))->print($outputPrinter);
                    }, function (\Exception $exception) use ($outputPrinter) {
                        (new CommandBusLineMessage(sprintf(
                            'Table with name %s could not be created. Reason - %s',
                            $this->key,
                            $exception->getMessage()
                        )))->print($outputPrinter);
                    });
            });
    }

    /**
     * Drop infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    public function dropInfrastructure(OutputPrinter $outputPrinter): PromiseInterface
    {
        $dropQuerySQL = sprintf(<<<'SQL'
DROP TABLE IF EXISTS %1$s;
SQL
            , $this->key);

        return $this
            ->postgres
            ->query($dropQuerySQL)
            ->toPromise()
            ->then(function () use ($outputPrinter) {
                (new CommandBusLineMessage(sprintf('Table with name %s deleted properly', $this->key)))->print($outputPrinter);
            }, function (\Exception $exception) use ($outputPrinter) {
                (new CommandBusLineMessage(sprintf(
                    'Table with name %s was impossible to be deleted. Reason - %s',
                    $this->key,
                    $exception->getMessage()
                )))->print($outputPrinter);
            });
    }

    /**
     * Check infrastructure.
     *
     * @param OutputPrinter $outputPrinter
     *
     * @return PromiseInterface
     */
    public function checkInfrastructure(OutputPrinter $outputPrinter): PromiseInterface
    {
        $selectSQL = sprintf(<<<'SQL'
SELECT COUNT(*) FROM %1$s;
SQL
            , $this->key);

        return $this
            ->postgres
            ->query($selectSQL)
            ->toPromise()
            ->then(function ($number) use ($outputPrinter) {
                (new CommandBusLineMessage(sprintf('Table with name %s exists', $this->key)))->print($outputPrinter);
            }, function (\Exception $exception) use ($outputPrinter) {
                (new CommandBusLineMessage(sprintf(
                    'Table with name %s does not exist. Reason - %s',
                    $this->key,
                    $exception->getMessage()
                )))->print($outputPrinter);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function enqueue($command): PromiseInterface
    {
        list($usec, $sec) = explode(' ', microtime());
        $usec = str_replace('0.', '.', $usec);
        $date = date('Y-m-d H:i:s', intval($sec)).$usec;

        return $this
            ->postgres
            ->query(sprintf("INSERT INTO %s VALUES('%s', '%s', '%s')", $this->key, rand(1, 9999999999999), $date, addslashes(serialize($command))))
            ->toPromise();
    }

    /**
     * Consume.
     *
     * @param CommandBus    $bus
     * @param int           $limit
     * @param OutputPrinter $outputPrinter
     *
     * @throws InvalidCommandException
     */
    public function consume(
        CommandBus $bus,
        int $limit,
        OutputPrinter $outputPrinter
    ) {
        $this->resetIterations($limit);
        $keepChecking = $this->consumeAvailableElements($bus, $outputPrinter);

        if (!$keepChecking) {
            return;
        }

        $this
            ->postgres
            ->listen($this->key)
            ->subscribe(function (NotificationResponse $message) use ($bus, $outputPrinter) {
                return $this
                    ->getAndDeleteKeyFromQueue()
                    ->then(function ($message) use ($bus, $outputPrinter) {
                        if (empty($message)) {
                            return true;
                        }

                        return $this->executeCommand(
                            $bus,
                            unserialize(stripslashes($message['payload'])),
                            $outputPrinter,
                            function () {},
                            function () {},
                            function () {
                                $this
                                    ->loop
                                    ->stop();

                                return false;
                            }
                        );
                    });
            });

        $this
            ->loop
            ->run();
    }

    /**
     * Consume elements in database.
     *
     * @param CommandBus    $bus
     * @param OutputPrinter $outputPrinter
     *
     * @return bool
     */
    private function consumeAvailableElements(
        CommandBus $bus,
        OutputPrinter $outputPrinter
    ): bool {
        $checkAgain = true;

        while ($checkAgain) {
            $promise = $this
                ->getAndDeleteKeyFromQueue()
                ->then(function ($message) use ($bus, $outputPrinter) {
                    if (empty($message)) {
                        return true;
                    }

                    return $this->executeCommand(
                        $bus,
                        unserialize(stripslashes($message['payload'])),
                        $outputPrinter,
                        function () {
                        },
                        function () {
                        },
                        function () {
                            return false;
                        }
                    );
                });

            /*
             * true => We can keep listening in mode PUB/SUB
             * false => stop listening, we reached the maximum
             * null => keel doing
             */

            $result = await($promise, $this->loop);
            if (true === $result) {
                return true;
            } elseif (false === $result) {
                $checkAgain = false;
            }
        }

        return false;
    }

    /**
     * Get and Delete first element from queue.
     *
     * @return PromiseInterface
     */
    private function getAndDeleteKeyFromQueue(): PromiseInterface
    {
        return $this
            ->postgres
            ->query(sprintf('DELETE FROM %s WHERE id = (SELECT id FROM %s ORDER BY added_at FOR UPDATE SKIP LOCKED LIMIT 1) RETURNING *', $this->key, $this->key))
            ->toPromise();
    }
}

/*
 *
 *
DROP TABLE IF EXISTS commands;
CREATE TABLE commands (id VARCHAR, added_at TIMESTAMP, payload TEXT);
SELECT * FROM commands;

CREATE OR REPLACE FUNCTION notify_commands() RETURNS TRIGGER AS $$
    BEGIN
        PERFORM pg_notify('commands', '1');
        RETURN NEW;
    END;
$$ LANGUAGE plpgsql;
DROP TRIGGER IF EXISTS notify_trigger ON commands;
CREATE TRIGGER notify_trigger
AFTER INSERT
ON commands
FOR EACH ROW EXECUTE PROCEDURE notify_commands();
*/
