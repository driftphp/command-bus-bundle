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

use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\Command\ChangeAnotherThing;
use Drift\CommandBus\Tests\Command\ChangeAThing;
use Drift\CommandBus\Tests\CommandHandler\ChangeAnotherThingHandler;
use Drift\CommandBus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\CommandBus\Tests\Context;
use Drift\CommandBus\Tests\Middleware\DiscriminableMiddleware1;
use function Clue\React\Block\awaitAll;

/**
 * Class DiscriminableBusTest.
 */
class DiscriminableBusTest extends BusFunctionalTest
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
        $configuration['services'][Context::class] = [];
        $configuration['services'][ChangeAThingHandler::class] = [
            'tags' => ['command_handler'],
        ];
        $configuration['services'][ChangeAnotherThingHandler::class] = [
            'tags' => ['command_handler'],
        ];

        $configuration['command_bus'] = [
            'command_bus' => [
                'middlewares' => [
                    DiscriminableMiddleware1::class,
                ],
            ],
        ];

        $configuration['imports'] = [
            ['resource' => __DIR__.'/../autowiring.yml'],
        ];

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
        $commandBus = $this->getCommandBus();
        $this->resetContext();
        awaitAll([
            $commandBus->execute(new ChangeAThing('x')),
            $commandBus->execute(new ChangeAnotherThing('x')),
        ], $this->getLoop());

        $this->assertNotNull($this->getContextValue(ChangeAThing::class));
        $this->assertNull($this->getContextValue(ChangeAnotherThing::class));
    }
}
