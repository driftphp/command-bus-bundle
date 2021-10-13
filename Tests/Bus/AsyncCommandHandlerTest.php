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

use Drift\CommandBus\Tests\AsyncService;
use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\CommandBus\Tests\Context;
use Drift\CommandBus\Tests\Service;

/**
 * Class AsyncCommandHandlerTest.
 */
class AsyncCommandHandlerTest extends BusFunctionalTest
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
        $configuration['services'][AsyncService::class] = [];
        $configuration['services'][Service::class] = [];
        $configuration['services'][Context::class] = [];
        $configuration['services'][ChangeAThingHandler::class] = [
            'tags' => [
                ['name' => 'command_handler', 'method' => 'handle'],
                ['name' => 'another_tag', 'method' => 'anotherMethod'],
            ],
        ];

        $configuration['command_bus']['command-bus']['async_adapter'] = [
            'adapter' => 'in_memory',
            'in_memory' => [],
        ];

        return $configuration;
    }

    /**
     * Buses injection.
     */
    public function testBusesInjection()
    {
        $this->expectNotToPerformAssertions();
        $this->get(Service::class);
        $this->get(AsyncService::class);
    }
}
