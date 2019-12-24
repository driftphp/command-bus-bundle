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

namespace Drift\Bus\DependencyInjection\CompilerPass;

use Drift\Bus\Adapter\TacticianBus;
use Drift\Bus\Async\FilesystemAdapter;
use Drift\Bus\Async\InMemoryAdapter;
use Drift\Bus\Async\RedisAdapter;
use Drift\Bus\AsyncAdapter;
use Drift\Bus\AsyncMiddleware;
use Drift\Bus\CommandBus;
use Drift\Bus\Console\CommandConsumer;
use Drift\Bus\Middleware\HandlerMiddleware;
use Drift\Bus\Middleware\Middleware;
use Drift\Bus\QueryBus;
use League\Tactician\CommandBus as TacticianCommandBus;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class BusCompilerPass.
 */
class BusCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $asyncBus = $this->createAsyncMiddleware($container);

        if ('tactician' === $container->getParameter('bus.adapter')) {
            $this->createTacticianQueryBusAdapter($container);
            $this->createTacticianCommandBusAdapter($container, '', $asyncBus);
            $this->createTacticianCommandBusAdapter($container, 'inline_', false);
        }

        if ($asyncBus) {
            $this->createCommandConsumer($container);
        }

        $this->createQueryBus($container);
        $this->createCommandBus($container);
        $this->createInlineCommandBus($container);
    }

    /**
     * Check for async middleware needs and return if has been created.
     *
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    public function createAsyncMiddleware(ContainerBuilder $container): bool
    {
        $asyncAdapters = $container->getParameter('bus.command_bus.async_adapter');
        if (false === $asyncAdapters) {
            return false;
        }

        $adapterType = array_key_first($asyncAdapters);
        $adapter = $asyncAdapters[$adapterType];

        switch ($adapterType) {
            case 'filesystem':
                $this->createFilesystemAsyncAdapter($container, $adapter);
                break;
            case 'in_memory':
                $this->createInMemoryAsyncAdapter($container);
                break;
            case 'redis':
                $this->createRedisAsyncAdapter($container, $adapter);
                break;
        }

        $container->setDefinition(AsyncMiddleware::class,
            new Definition(
                AsyncMiddleware::class, [
                    new Reference(AsyncAdapter::class),
                ]
            )
        );

        $container->setDefinition(AsyncMiddleware::class.'\Wrapper',
            new Definition(
                Middleware::class, [
                    new Reference(AsyncMiddleware::class),
                    'execute',
                ]
            )
        );

        return true;
    }

    /**
     * Create query bus.
     *
     * @param ContainerBuilder $container
     */
    public function createQueryBus(ContainerBuilder $container)
    {
        $container->setDefinition('drift.query_bus', new Definition(
            QueryBus::class, [
                new Reference('drift.query_bus.adapter'),
            ]
        ));
        $container->setAlias(QueryBus::class, 'drift.query_bus');
    }

    /**
     * Create command bus.
     *
     * @param ContainerBuilder $container
     */
    public function createCommandBus(ContainerBuilder $container)
    {
        $container->setDefinition('drift.command_bus', new Definition(
            CommandBus::class, [
                new Reference('drift.command_bus.adapter'),
            ]
        ));
        $container->setAlias(CommandBus::class, 'drift.command_bus');
    }

    /**
     * Create command bus.
     *
     * @param ContainerBuilder $container
     */
    public function createInlineCommandBus(ContainerBuilder $container)
    {
        $container->setDefinition('drift.inline_command_bus', new Definition(
            CommandBus::class, [
                new Reference('drift.inline_command_bus.adapter'),
            ]
        ));
        $container->setAlias(CommandBus::class, 'drift.inline_command_bus');
    }

    /**
     * Create array of middlewares by configuration.
     *
     * @param ContainerBuilder $container
     * @param string           $type
     * @param bool             $isAsync
     *
     * @return array
     */
    private function createMiddlewaresArray(
        ContainerBuilder $container,
        string $type,
        bool $isAsync = false
    ) {
        $middlewares = [];
        $asyncMiddlewareFound = false;
        $asyncMiddlewareId = AsyncMiddleware::class.'\Wrapper';

        foreach ($container->getParameter("bus.{$type}_bus.middlewares") as $middleware) {
            if ('@async' === $middleware) {
                if (
                    true === $isAsync &&
                    'command' === $type
                ) {
                    $middlewares[] = new Reference($asyncMiddlewareId);
                    $asyncMiddlewareFound = true;
                }

                continue;
            }

            $method = 'execute';
            $splitted = explode('::', $middleware, 2);
            if (2 === count($splitted)) {
                $middleware = $splitted[0];
                $method = $splitted[1];
            }

            if (!$container->has($middleware)) {
                $container->setDefinition($middleware, new Definition($middleware));
            }

            $middlewareWrapperName = "{$middleware}\\Wrapper";
            $middlewareWrapper = new Definition(Middleware::class, [
                new Reference($middleware),
                $method,
            ]);

            $container->setDefinition($middlewareWrapperName, $middlewareWrapper);
            $middlewares[] = new Reference($middlewareWrapperName);
        }

        $handlerMiddlewareId = "bus.{$type}_bus.handler_middleware";
        if (!$container->has($handlerMiddlewareId)) {
            $handlerMiddleware = new Definition(HandlerMiddleware::class);
            $handlerMap = $this->createHandlersMap($container, "{$type}_handler");
            foreach ($handlerMap as $command => list($reference, $method)) {
                $handlerMiddleware->addMethodCall('addHandler', [
                    $command, $reference, $method,
                ]);
            }
            $container->setDefinition($handlerMiddlewareId, $handlerMiddleware);
        }

        $middlewares[] = new Reference($handlerMiddlewareId);

        if (
            true === $isAsync &&
            'command' === $type &&
            !$asyncMiddlewareFound
        ) {
            array_unshift($middlewares, new Reference($asyncMiddlewareId));
        }

        return $middlewares;
    }

    /**
     * Create handlers map.
     *
     * @param ContainerBuilder $container
     * @param string           $tag
     *
     * @return array
     */
    private function createHandlersMap(
        ContainerBuilder $container,
        string $tag
    ): array {
        $handlers = $container->findTaggedServiceIds($tag);
        $handlersMap = [];
        foreach ($handlers as $serviceId => $handler) {
            $serviceNamespace = $container->getDefinition($serviceId)->getClass();
            $reflectionClass = new ReflectionClass($serviceNamespace);
            $reflectionMethod = $reflectionClass->getMethod($handler[0]['method'] ?? 'handle');
            $reflectionParameter = $reflectionMethod->getParameters()[0];
            $handlersMap[$reflectionParameter->getClass()->getName()] = [new Reference($serviceId), $reflectionMethod->getName()];
        }

        return $handlersMap;
    }

    /**
     * Create command consumer.
     *
     * @param ContainerBuilder $container
     */
    private function createCommandConsumer(ContainerBuilder $container)
    {
        $consumer = new Definition(CommandConsumer::class, [
            new Reference(AsyncAdapter::class),
            new Reference('drift.inline_command_bus'),
            new Reference('reactphp.event_loop'),
        ]);

        $consumer->addTag('console.command', [
            'command' => 'bus:consume-commands',
        ]);

        $container->setDefinition(CommandConsumer::class, $consumer);
    }

    /**
     * Tactician.
     */

    /**
     * Create tactician query bus adapter.
     *
     * @param ContainerBuilder $container
     */
    public function createTacticianQueryBusAdapter(ContainerBuilder $container)
    {
        $container->setDefinition(
            'tactician.query_bus',
            new Definition(
                TacticianCommandBus::class, [
                    $this->createMiddlewaresArray(
                        $container,
                        'query'
                    ),
                ]
            )
        );

        $container->setDefinition(
            'drift.query_bus.adapter',
            new Definition(
                TacticianBus::class, [
                    new Reference('tactician.query_bus'),
                ]
            )
        );
    }

    /**
     * Create tactician command bus adapter.
     *
     * @param ContainerBuilder $container
     * @param bool             $isAsync
     */
    public function createTacticianCommandBusAdapter(
        ContainerBuilder $container,
        string $prefix = '',
        bool $isAsync = false
    ) {
        $container->setDefinition(
            "tactician.{$prefix}command_bus",
            new Definition(
                TacticianCommandBus::class, [
                    $this->createMiddlewaresArray(
                        $container,
                        'command',
                        $isAsync
                    ),
                ]
            )
        );

        $container->setDefinition(
            "drift.{$prefix}command_bus.adapter",
            new Definition(
                TacticianBus::class, [
                    new Reference("tactician.{$prefix}command_bus"),
                ]
            )
        );
    }

    /**
     * ADAPTERS.
     */

    /**
     * Create filesystem async adapter.
     *
     * @param ContainerBuilder $container
     * @param array            $adapter
     */
    private function createFilesystemAsyncAdapter(
        ContainerBuilder $container,
        array $adapter
    ) {
        $container->setDefinition(
            AsyncAdapter::class,
            new Definition(
                FilesystemAdapter::class, [
                    new Reference('reactphp.event_loop'),
                    new Reference('drift.filesystem'),
                    $adapter['file'],
                ]
            )
        );
    }

    /**
     * Create in_memory async adapter.
     *
     * @param ContainerBuilder $container
     */
    private function createInMemoryAsyncAdapter(ContainerBuilder $container)
    {
        $container->setDefinition(
            AsyncAdapter::class,
            new Definition(InMemoryAdapter::class)
        );
    }

    /**
     * Create redis async adapter.
     *
     * @param ContainerBuilder $container
     * @param array            $adapter
     */
    private function createRedisAsyncAdapter(
        ContainerBuilder $container,
        array $adapter
    ) {
        $container->setDefinition(
            AsyncAdapter::class,
            new Definition(RedisAdapter::class, [
                new Reference('redis.'.$adapter['client'].'_client'),
                new Reference('reactphp.event_loop'),
                $adapter['key'] ?? 'commands',
            ])
        );
    }
}
