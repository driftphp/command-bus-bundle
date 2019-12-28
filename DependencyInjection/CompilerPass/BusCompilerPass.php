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

use Drift\Bus\Async\AMQPAdapter;
use Drift\Bus\Async\AsyncAdapter;
use Drift\Bus\Async\InMemoryAdapter;
use Drift\Bus\Async\RedisAdapter;
use Drift\Bus\Bus\CommandBus;
use Drift\Bus\Bus\InlineCommandBus;
use Drift\Bus\Bus\QueryBus;
use Drift\Bus\Console\CommandConsumer;
use Drift\Bus\Exception\InvalidMiddlewareException;
use Drift\Bus\Middleware\AsyncMiddleware;
use Drift\Bus\Middleware\HandlerMiddleware;
use Drift\Bus\Middleware\Middleware;
use ReflectionClass;
use ReflectionException;
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

        if ($asyncBus) {
            $this->createCommandConsumer($container);
        }

        $this->createQueryBus($container);
        $this->createCommandBus($container, $asyncBus);
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
            case 'amqp':
                $this->createAMQPAsyncAdapter($container, $adapter);
                break;
            case 'in_memory':
                $this->createInMemoryAsyncAdapter($container);
                break;
            case 'redis':
                $this->createRedisAsyncAdapter($container, $adapter);
                break;
            default:
                return false;
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
                $this->createMiddlewaresArray(
                    $container,
                    'query',
                    false
                ),
            ]
        ));

        $container->setAlias(QueryBus::class, 'drift.query_bus');
    }

    /**
     * Create command bus.
     *
     * @param ContainerBuilder $container
     * @param bool             $asyncBus
     */
    public function createCommandBus(
        ContainerBuilder $container,
        bool $asyncBus
    ) {
        $container->setDefinition('drift.command_bus', new Definition(
            CommandBus::class, [
                $this->createMiddlewaresArray(
                    $container,
                    'command',
                    $asyncBus
                ),
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
            InlineCommandBus::class, [
                $this->createMiddlewaresArray(
                    $container,
                    'command',
                    false
                ),
            ]
        ));

        $container->setAlias(InlineCommandBus::class, 'drift.inline_command_bus');
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

            try {
                $reflectionMethod = $reflectionClass->getMethod($handler[0]['method'] ?? 'handle');
            } catch (ReflectionException $exception) {
                throw new InvalidMiddlewareException();
            }

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
     * ADAPTERS.
     */

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

    /**
     * Create amqp async adapter.
     *
     * @param ContainerBuilder $container
     * @param array            $adapter
     */
    private function createAMQPAsyncAdapter(
        ContainerBuilder $container,
        array $adapter
    ) {
        $container->setDefinition(
            AsyncAdapter::class,
            new Definition(AMQPAdapter::class, [
                new Reference('amqp.'.$adapter['client'].'_channel'),
                new Reference('reactphp.event_loop'),
                $adapter['queue'] ?? 'commands',
            ])
        );
    }
}
