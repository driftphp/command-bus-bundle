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

use Drift\CommandBus\Tests\BusFunctionalTest;

/**
 * Class CommandsListTest.
 */
class CommandsListTest extends BusFunctionalTest
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
        $configuration = parent::decorateConfiguration($configuration);

        $configuration['command_bus'] = [
            'command_bus' => [
                'async_adapter' => [
                    'amqp' => [
                        'host' => '127.0.0.99',
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

        $this->assertStringContainsString('debug:command-bus', $output);
        $this->assertStringContainsString('bus:consume-commands', $output);
    }
}
