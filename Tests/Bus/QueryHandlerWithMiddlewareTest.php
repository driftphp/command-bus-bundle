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

namespace Drift\Bus\Tests\Bus;

use Drift\Bus\Middleware\HandlerMiddleware;
use Drift\Bus\Tests\BusFunctionalTest;
use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\Middleware\Middleware1;
use Drift\Bus\Tests\Middleware\Middleware2;
use Drift\Bus\Tests\Query\GetAnotherThing;
use Drift\Bus\Tests\Query\GetAThing;
use Drift\Bus\Tests\QueryHandler\GetAnotherThingHandler;
use Drift\Bus\Tests\QueryHandler\GetAThingHandler;
use function Clue\React\Block\await;

/**
 * Class QueryHandlerWithMiddleware.
 */
class QueryHandlerWithMiddleware extends BusFunctionalTest
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

        $configuration['bus'] = [
            'query_bus' => [
                'middlewares' => [
                    Middleware1::class.'::anotherMethod',
                    Middleware2::class,
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
