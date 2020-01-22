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

namespace Drift\CommandBus\Tests\Console;

use Drift\AMQP\AMQPBundle;
use Drift\CommandBus\Tests\BusFunctionalTest;

/**
 * Class CommandsListTest.
 */
class CommandsListTest extends BusFunctionalTest
{
    /**
     * Decorate bundles.
     *
     * @param array $bundles
     *
     * @return array
     */
    protected static function decorateBundles(array $bundles): array
    {
        $bundles[] = AMQPBundle::class;

        return $bundles;
    }

    /**
     * Decorate configuration.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected static function decorateConfiguration(array $configuration): array
    {
        $configuration = parent::decorateConfiguration($configuration);

        $configuration['amqp'] = [
            'clients' => [
                'amqp_1' => [
                    'host' => '127.0.0.99',
                ],
            ],
        ];

        $configuration['command_bus'] = [
            'command_bus' => [
                'async_adapter' => [
                    'amqp' => [
                        'client' => 'amqp_1',
                        'queue' => 'commands',
                    ],
                ],
            ],
        ];

        return $configuration;
    }

    /**
     * Test that simple commands list works as expected.
     */
    public function testCommandsList()
    {
        $output = $this->runCommand([
            '',
        ]);

        $this->assertContains('debug:command-bus', $output);
        $this->assertContains('bus:consume-commands', $output);
    }
}
