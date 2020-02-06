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

namespace Drift\CommandBus\Tests\Async;

use Drift\Postgresql\PostgresqlBundle;

/**
 * Class PostgreSQLAsyncTest.
 */
class PostgreSQLAsyncTest extends AsyncAdapterTest
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
        $bundles[] = PostgresqlBundle::class;

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

        $configuration['postgresql'] = [
            'clients' => [
                'postgresql_1' => [
                    'host' => '127.0.0.1',
                    'database' => 'commands',
                    'user' => 'root',
                    'password' => 'root',
                ],
            ],
        ];

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected static function getAsyncConfiguration(): array
    {
        return [
            'postgresql' => [
                'client' => 'postgresql_1',
                'channel' => 'commands',
            ],
        ];
    }
}
