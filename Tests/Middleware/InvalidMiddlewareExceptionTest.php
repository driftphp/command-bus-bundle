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

namespace Drift\CommandBus\Tests\Middleware;

use Drift\CommandBus\Tests\BusFunctionalTest;
use Drift\CommandBus\Tests\CommandHandler\ChangeAThingHandler;
use Drift\CommandBus\Tests\Context;
use RuntimeException;

/**
 * Class BadMiddlewareExceptionTest.
 */
class InvalidMiddlewareExceptionTest extends BusFunctionalTest
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
                ['name' => 'command_handler', 'method' => 'non_existing'],
            ],
        ];

        return $configuration;
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        try {
            parent::setUpBeforeClass();
            self::fail('Exception expected');
        } catch (RuntimeException $exception) {
            // Ok
        }
    }

    /**
     * Test that test can run. Asserting this true===true is a success.
     */
    public function testRuns()
    {
        $this->assertTrue(true);
    }
}
