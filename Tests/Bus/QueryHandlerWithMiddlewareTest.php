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
use Drift\CommandBus\Middleware\HandlerMiddleware;
use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\Command\ChangeAThing;
use Drift\CommandBus\Tests\Context;
use Drift\CommandBus\Tests\Middleware\DiscriminableMiddleware1;
use Drift\CommandBus\Tests\Middleware\Middleware1;
use Drift\CommandBus\Tests\Middleware\Middleware2;
use Drift\CommandBus\Tests\Query\GetAnotherThing;
use Drift\CommandBus\Tests\Query\GetAThing;
use Drift\CommandBus\Tests\QueryHandler\GetAnotherThingHandler;
use Drift\CommandBus\Tests\QueryHandler\GetAThingHandler;

/**
 * Class QueryHandlerWithMiddleware.
 */
class QueryHandlerWithMiddlewareTest extends BusFunctionalTest
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
        $configuration['services'][GetAThingHandler::class] = [
            'tags' => [
                ['name' => 'query_handler'],
            ],
        ];

        $configuration['services'][GetAnotherThingHandler::class] = [
            'tags' => [
                ['name' => 'query_handler', 'method' => 'another'],
            ],
        ];

        $configuration['imports'] = [
            ['resource' => __DIR__.'/../autowiring.yml'],
        ];

        $configuration['command_bus'] = [
            'query_bus' => [
                'middlewares' => [
                    Middleware1::class.'::anotherMethod',
                    Middleware2::class,
                    DiscriminableMiddleware1::class,
                ],
            ],
        ];

        return $configuration;
    }

    /**
     * Test AThing.
     */
    public function testAThing()
    {
        $promise = $this
            ->getQueryBus()
            ->ask(new GetAThing('thing'));

        $value = await($promise, $this->getLoop());

        $this->assertEquals('super thing OK changed', $value);

        $this->assertEquals([
            [
                'class' => Middleware1::class,
                'method' => 'anotherMethod',
            ],
            [
                'class' => Middleware2::class,
                'method' => 'execute',
            ],
            [
                'class' => DiscriminableMiddleware1::class,
                'method' => 'execute',
                'handled_objects' => [
                    ChangeAThing::class,
                ],
            ],
            [
                'class' => HandlerMiddleware::class,
                'method' => 'execute',
                'handlers' => [
                    GetAThing::class => [
                        'handler' => GetAThingHandler::class,
                        'method' => 'handle',
                    ],
                    GetAnotherThing::class => [
                        'handler' => GetAnotherThingHandler::class,
                        'method' => 'another',
                    ],
                ],
            ],
        ], $this
            ->getQueryBus()
            ->getMiddlewareList()
        );
    }

    /**
     * Test AThing.
     */
    public function testAnotherThing()
    {
        $promise = $this
            ->getQueryBus()
            ->ask(new GetAnotherThing('another thing'));

        $value = await($promise, $this->getLoop());

        $this->assertEquals('super another thing OK!! changed', $value);
    }
}
