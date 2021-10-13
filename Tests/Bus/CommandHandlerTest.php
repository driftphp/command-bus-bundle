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
use Drift\CommandBus\Middleware\HandlerMiddleware;
use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\Command\ChangeAThing;
use Drift\CommandBus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\CommandBus\Tests\Context;
use Drift\CommandBus\Tests\Service;

/**
 * Class CommandHandlerTest.
 */
class CommandHandlerTest extends BusFunctionalTest
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
        $configuration['services'][ChangeAThingHandler::class] = [
            'tags' => [
                ['name' => 'command_handler', 'method' => 'handle'],
                ['name' => 'another_tag', 'method' => 'anotherMethod'],
            ],
        ];

        $configuration['command_bus']['command_bus']['distribution'] = self::distributedBus()
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
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        $value = await($promise, $this->getLoop());

        $this->assertEquals('thing', $this->getContextValue('thing'));
        $this->assertNull($value);
        $this->assertEquals([
            [
                'class' => HandlerMiddleware::class,
                'method' => 'execute',
                'handlers' => [
                    ChangeAThing::class => [
                        'handler' => ChangeAThingHandler::class,
                        'method' => 'handle',
                    ],
                ],
            ],
        ], $this
            ->getCommandBus()
            ->getMiddlewareList()
        );
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
