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

use Drift\Redis\RedisBundle;

/**
 * Class RedisAsyncAdapterTest.
 */
class RedisAsyncAdapterTest extends AsyncAdapterTest
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
        $bundles[] = RedisBundle::class;

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

        $configuration['redis'] = [
            'clients' => [
                'redis_1' => [
                    'host' => '127.0.0.1',
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
            'redis' => [
                'client' => 'redis_1',
                'key' => 'commands',
            ],
        ];
    }
}
