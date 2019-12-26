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

use Drift\Bus\Tests\BusFunctionalTest;
use Drift\Bus\Tests\Command\ChangeAThing;
use Drift\Bus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\Bus\Tests\Context;
use Drift\Bus\Tests\Middleware\Middleware1;
use function Clue\React\Block\await;

/**
 * Class AsyncAdapterTest.
 */
abstract class AsyncAdapterTest extends BusFunctionalTest
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
                'async_adapter' => static::getAsyncConfiguration(),
                'middlewares' => [
                    Middleware1::class.'::anotherMethod',
                ],
            ],
        ];

        return $configuration;
    }

    /**
     * Get async configuration.
     *
     * @return array
     */
    abstract protected static function getAsyncConfiguration(): array;

    /**
     * Test buses are being built.
     *
     * @group async
     */
    public function testQueryBus()
    {
        @unlink('/tmp/a.thing');
        $promise = $this
            ->getCommandBus()
            ->execute(new ChangeAThing('thing'));

        await($promise, $this->getLoop());

        $this->assertNull($this->getContextValue('middleware1'));
        $this->assertFalse(file_exists('/tmp/a.thing'));
        $output = $this->runCommand([
            'bus:consume-commands',
            '--limit' => 1,
        ]);

        $this->assertContains('Command <ChangeAThing> consumed', $output);
        $this->assertTrue($this->getContextValue('middleware1'));
        $this->assertTrue(file_exists('/tmp/a.thing'));
    }
}
