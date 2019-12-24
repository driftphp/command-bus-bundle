<?php


namespace Drift\Bus\Tests;


use Drift\Redis\RedisBundle;

/**
 * Class RedisAsyncAdapterTest
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
                ]
            ]
        ];

        return $configuration;
    }

    /**
     * @inheritDoc
     */
    static protected function getAsyncConfiguration(): array
    {
        return [
            'redis' => [
                'client' => 'redis_1',
                'key' => 'commands'
            ]
        ];
    }
}