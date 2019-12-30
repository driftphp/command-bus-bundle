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

namespace Drift\Bus\Tests\Async;

use Drift\Bus\Middleware\AsyncMiddleware;
use Drift\Bus\Tests\BusFunctionalTest;
use Drift\Bus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\Bus\Tests\Context;

/**
 * Class DefaultAsyncTest.
 */
class DefaultAsyncTest extends BusFunctionalTest
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
            'tags' => [
                ['name' => 'command_handler', 'method' => 'handle'],
                ['name' => 'another_tag', 'method' => 'anotherMethod'],
            ],
        ];

        $configuration['imports'] = [
            ['resource' => __DIR__.'/../autowiring.yml'],
        ];

        $configuration['bus'] = [
            'command_bus' => [
                'async_adapter' => [
                    'in_memory' => [],
                ],
            ],
        ];

        return $configuration;
    }

    /**
     * Test buses are being built.
     *
     * @group async
     */
    public function testCommandBusMiddlewares()
    {
        $this->assertEquals([
            [
                'class' => AsyncMiddleware::class,
                'method' => 'execute',
            ],
        ], $this
            ->getCommandBus()
            ->getMiddlewareList()
        );
    }
}
