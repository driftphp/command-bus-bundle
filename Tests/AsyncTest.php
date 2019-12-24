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

namespace Drift\Bus\Tests;

use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\Bus\Tests\Middleware\Middleware1;
use Drift\Bus\Tests\Middleware\Middleware3;
use function Clue\React\Block\await;

/**
 * Class AsyncTest.
 */
class AsyncTest extends BusFunctionalTest
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
            ['resource' => __DIR__.'/autowiring.yml'],
        ];

        $configuration['bus'] = [
            'command_bus' => [
                'async_adapter' => [
                    'in_memory' => [],
                ],
                'middlewares' => [
                    Middleware1::class.'::anotherMethod',
                    '@async',
                    Middleware3::class,
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
    public function testQueryBus()
    {
        $promise = $this
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        await($promise, $this->getLoop());

        $this->assertTrue($this->getContextValue('middleware1'));
    }
}
