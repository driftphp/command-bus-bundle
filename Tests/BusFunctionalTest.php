<?php

namespace Drift\Bus\Tests;

use Drift\Bus\BusBundle;
use Drift\Bus\CommandBus;
use Drift\Bus\QueryBus;
use Mmoreram\BaseBundle\Kernel\DriftBaseKernel;
use Mmoreram\BaseBundle\Tests\BaseFunctionalTest;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class BusFunctionalTest
 */
abstract class BusFunctionalTest extends BaseFunctionalTest
{
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
                    'alias' => 'drift.command_bus'
                ],

                'drift.query_bus.test' => [
                    'alias' => 'drift.query_bus'
                ],
            ],
        ];

        return new DriftBaseKernel(
            static::decorateBundles([
                FrameworkBundle::class,
                BusBundle::class,
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
     * Get command bus
     *
     * @return CommandBus
     */
    protected function getCommandBus() : CommandBus
    {
        return $this->get('drift.command_bus.test');
    }

    /**
     * Get query bus
     *
     * @return QueryBus
     */
    protected function getQueryBus() : QueryBus
    {
        return $this->get('drift.query_bus.test');
    }

    /**
     * Get loop
     *
     * @return LoopInterface
     */
    protected function getLoop() : LoopInterface
    {
        return $this->get('reactphp.event_loop');
    }

    /**
     * Get context value
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function getContextValue(string $value)
    {
        return $this->get(Context::class)->values[$value] ?? null;
    }
}