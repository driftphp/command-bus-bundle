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

namespace Drift\CommandBus\Tests;

use Drift\CommandBus\Bus\CommandBus;
use Drift\CommandBus\Bus\QueryBus;
use Drift\CommandBus\CommandBusBundle;
use Mmoreram\BaseBundle\Kernel\DriftBaseKernel;
use Mmoreram\BaseBundle\Tests\BaseFunctionalTest;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class BusFunctionalTest.
 */
abstract class BusFunctionalTest extends BaseFunctionalTest
{
    /**
     * @var BufferedOutput
     */
    private static $output;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass()
    {
        self::$output = new BufferedOutput();
        parent::setUpBeforeClass();
    }

    /**
     * Get kernel.
     *
     * @return KernelInterface
     */
    protected static function getKernel(): KernelInterface
    {
        $configuration = [
            'parameters' => [
                'kernel.secret' => 'gdfgfdgd',
            ],
            'framework' => [
                'test' => true,
            ],
            'services' => [
                '_defaults' => [
                    'autowire' => true,
                    'autoconfigure' => false,
                    'public' => true,
                ],

                'reactphp.event_loop' => [
                    'class' => LoopInterface::class,
                    'public' => true,
                    'factory' => [
                        Factory::class,
                        'create',
                    ],
                ],

                'drift.command_bus.test' => [
                    'alias' => 'drift.command_bus',
                ],

                'drift.inline_command_bus.test' => [
                    'alias' => 'drift.inline_command_bus',
                ],

                'drift.query_bus.test' => [
                    'alias' => 'drift.query_bus',
                ],
            ],
        ];

        return new DriftBaseKernel(
            static::decorateBundles([
                FrameworkBundle::class,
                CommandBusBundle::class,
            ]),
            static::decorateConfiguration($configuration),
            [],
            static::environment(), static::debug()
        );
    }

    /**
     * Decorate bundles.
     *
     * @param array $bundles
     *
     * @return array
     */
    protected static function decorateBundles(array $bundles): array
    {
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
        return $configuration;
    }

    /**
     * Kernel in debug mode.
     *
     * @return bool
     */
    protected static function debug(): bool
    {
        return false;
    }

    /**
     * Kernel in debug mode.
     *
     * @return string
     */
    protected static function environment(): string
    {
        return 'dev';
    }

    /**
     * Get command bus.
     *
     * @return CommandBus
     */
    protected function getCommandBus(): CommandBus
    {
        return $this->get('drift.command_bus.test');
    }

    /**
     * Get inline command bus.
     *
     * @return CommandBus
     */
    protected function getInlineCommandBus(): CommandBus
    {
        return $this->get('drift.command_bus.test');
    }

    /**
     * Get query bus.
     *
     * @return QueryBus
     */
    protected function getQueryBus(): QueryBus
    {
        return $this->get('drift.query_bus.test');
    }

    /**
     * Get loop.
     *
     * @return LoopInterface
     */
    protected function getLoop(): LoopInterface
    {
        return $this->get('reactphp.event_loop');
    }

    /**
     * Get context value.
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function getContextValue(string $value)
    {
        return $this->get(Context::class)->values[$value] ?? null;
    }

    /**
     * Reset context.
     *
     * @return mixed
     */
    protected function resetContext()
    {
        return $this->get(Context::class)->values = [];
    }

    /**
     * Runs a command and returns its output as a string value.
     *
     * @param array $command
     *
     * @return string
     */
    protected static function runCommand(array $command): string
    {
        $input = new ArrayInput($command);

        static::$application->run(
            $input,
            self::$output
        );

        return self::$output->fetch();
    }
}
