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

namespace Drift\CommandBus\Tests\Bus;

use function Clue\React\Block\await;
use Drift\CommandBus\Bus\Bus;
use Drift\CommandBus\Exception\InvalidCommandException;
use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\Context;
use Drift\CommandBus\Tests\Query\GetAThing;
use Drift\CommandBus\Tests\QueryHandler\GetAThingHandler;
use Drift\CommandBus\Tests\Service;

/**
 * Class QueryHandlerTest.
 */
class QueryHandlerTest extends BusFunctionalTest
{
    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration['services'][Service::class] = [];
        $configuration['services'][Context::class] = [];
        $configuration['services'][GetAThingHandler::class] = [
            'tags' => [
                ['name' => 'query_handler', 'method' => 'handle'],
                ['name' => 'another_tag', 'method' => 'anotherMethod'],
            ],
        ];

        $configuration['command_bus']['query_bus']['distribution'] = self::distributedBus()
            ? Bus::DISTRIBUTION_NEXT_TICK
            : Bus::DISTRIBUTION_INLINE;

        return $configuration;
    }

    /**
     * Create distributed bus.
     *
     * @return bool
     */
    protected static function distributedBus(): bool
    {
        return false;
    }

    /**
     * Test buses are being built.
     */
    public function testQueryBus()
    {
        $promise = $this
            ->getQueryBus()
            ->ask(new GetAThing('thing'));

        $value = await($promise, $this->getLoop());

        $this->assertEquals('thing', $this->getContextValue('thing'));
        $this->assertEquals('thing OK', $value);
    }

    /**
     * Test bad command.
     */
    public function testBadCommand()
    {
        $this->expectException(InvalidCommandException::class);
        $promise = $this
            ->getQueryBus()
            ->ask('something');

        await($promise, $this->getLoop());
    }

    /**
     * Buses injection.
     */
    public function testBusesInjection()
    {
        $this->expectNotToPerformAssertions();
        $this->get(Service::class);
    }
}
